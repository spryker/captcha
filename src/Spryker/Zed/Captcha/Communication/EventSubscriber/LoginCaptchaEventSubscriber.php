<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\EventSubscriber;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaLoginFormTransfer;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\Kernel\StrategyResolverInterface;
use Spryker\Zed\Captcha\CaptchaConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Verifies the CAPTCHA of a Back Office or Merchant Portal login form submission before anything else handles it.
 *
 * Priority 10 runs after the blocked check of the login blockers (11), so a blocked login is answered before its CAPTCHA is
 * used, and before their counting (9) and the firewall (8). A rejection sets the response, which stops the event, so a
 * submission without a valid CAPTCHA is neither counted as a failed login nor authenticated.
 */
class LoginCaptchaEventSubscriber implements EventSubscriberInterface
{
    protected const int KERNEL_REQUEST_SUBSCRIBER_PRIORITY = 10;

    /**
     * @param \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Zed\Captcha\Communication\Checker\CaptchaRequirementCheckerInterface> $captchaRequirementCheckerStrategyResolver
     * @param \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Zed\Captcha\Communication\Builder\CaptchaRejectionResponseBuilderInterface> $captchaRejectionResponseBuilderStrategyResolver
     */
    public function __construct(
        protected CaptchaClientInterface $captchaClient,
        protected StrategyResolverInterface $captchaRequirementCheckerStrategyResolver,
        protected StrategyResolverInterface $captchaRejectionResponseBuilderStrategyResolver,
        protected CaptchaConfig $captchaConfig
    ) {
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', static::KERNEL_REQUEST_SUBSCRIBER_PRIORITY],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->isMethod(Request::METHOD_POST)) {
            return;
        }

        $captchaLoginFormTransfers = $this->getSubmittedLoginForms($request);

        if ($captchaLoginFormTransfers === []) {
            return;
        }

        if (count($captchaLoginFormTransfers) > 1) {
            $this->rejectLoginAttempt($event, $captchaLoginFormTransfers[0], $this->createInvalidCaptchaVerificationResponseTransfer(), null);

            return;
        }

        $this->verifyLoginCaptcha($event, $captchaLoginFormTransfers[0]);
    }

    protected function verifyLoginCaptcha(RequestEvent $event, CaptchaLoginFormTransfer $captchaLoginFormTransfer): void
    {
        $request = $event->getRequest();
        $context = $captchaLoginFormTransfer->getContextOrFail();
        $loginIdentifier = $this->findSubmittedField($request, $captchaLoginFormTransfer->getFormNameOrFail(), $captchaLoginFormTransfer->getUsernameFieldNameOrFail());

        if (!$this->isCaptchaRequired($request, $context, $loginIdentifier)) {
            return;
        }

        $captchaVerificationResponseTransfer = $this->captchaClient->verifyCaptcha(
            (new CaptchaVerificationRequestTransfer())
                ->setPayload((string)$this->findCaptchaPayload($request, $captchaLoginFormTransfer))
                ->setContext($context)
                ->setIp((string)$request->getClientIp()),
        );

        if ($captchaVerificationResponseTransfer->getIsValid()) {
            return;
        }

        $this->rejectLoginAttempt($event, $captchaLoginFormTransfer, $captchaVerificationResponseTransfer, $loginIdentifier);
    }

    protected function rejectLoginAttempt(
        RequestEvent $event,
        CaptchaLoginFormTransfer $captchaLoginFormTransfer,
        CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer,
        ?string $loginIdentifier
    ): void {
        $request = $event->getRequest();

        if ($loginIdentifier !== null && $request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $loginIdentifier);
        }

        $event->setResponse(
            $this->captchaRejectionResponseBuilderStrategyResolver
                ->get($captchaLoginFormTransfer->getRejectionResponseTypeOrFail())
                ->createCaptchaRejectionResponse($request, $captchaVerificationResponseTransfer, $captchaLoginFormTransfer->getLoginUrlOrFail()),
        );
    }

    /**
     * No browser submits two login forms at once; a request that does is rejected, because choosing one form would let the
     * other form's authenticator run without the CAPTCHA its own context requires.
     *
     * @return list<\Generated\Shared\Transfer\CaptchaLoginFormTransfer>
     */
    protected function getSubmittedLoginForms(Request $request): array
    {
        return array_values(array_filter(
            $this->captchaConfig->getLoginForms(),
            fn (CaptchaLoginFormTransfer $captchaLoginFormTransfer): bool => is_array($request->request->all()[$captchaLoginFormTransfer->getFormNameOrFail()] ?? null),
        ));
    }

    protected function createInvalidCaptchaVerificationResponseTransfer(): CaptchaVerificationResponseTransfer
    {
        return (new CaptchaVerificationResponseTransfer())
            ->setIsValid(false)
            ->addMessage((new MessageTransfer())->setValue(SharedCaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID));
    }

    protected function isCaptchaRequired(Request $request, string $context, ?string $loginIdentifier): bool
    {
        if (!$this->captchaClient->isCaptchaEnabled((new CaptchaChallengeRequestTransfer())->setContext($context))) {
            return false;
        }

        return $this->captchaRequirementCheckerStrategyResolver
            ->get($this->captchaConfig->getCaptchaMode())
            ->isCaptchaRequired(
                (new CaptchaRequirementRequestTransfer())
                    ->setContext($context)
                    ->setIp((string)$request->getClientIp())
                    ->setLoginIdentifier($loginIdentifier),
            );
    }

    protected function findCaptchaPayload(Request $request, CaptchaLoginFormTransfer $captchaLoginFormTransfer): ?string
    {
        $captchaResponseFieldName = $this->captchaClient->findResponseFieldName();

        if ($captchaResponseFieldName === null) {
            return $this->findSubmittedField($request, $captchaLoginFormTransfer->getFormNameOrFail(), SharedCaptchaConfig::FORM_FIELD_NAME_CAPTCHA);
        }

        $captchaPayload = $request->request->all()[$captchaResponseFieldName] ?? null;

        return is_string($captchaPayload) ? $captchaPayload : null;
    }

    protected function findSubmittedField(Request $request, string $formName, string $fieldName): ?string
    {
        $loginForm = $request->request->all()[$formName] ?? null;
        $value = is_array($loginForm) ? ($loginForm[$fieldName] ?? null) : null;

        return is_string($value) ? $value : null;
    }
}
