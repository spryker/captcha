<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\EventSubscriber;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaLoginFormTransfer;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\Kernel\StrategyResolverInterface;
use Spryker\Yves\Captcha\Builder\CaptchaRejectionResponseBuilderInterface;
use Spryker\Yves\Captcha\CaptchaConfig;
use Spryker\Yves\Captcha\Checker\CaptchaRequirementCheckerInterface;
use Spryker\Yves\Captcha\Resolver\LoginFormResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Verifies the CAPTCHA of a Storefront customer or agent login form submission before anything else handles it.
 *
 * Priority 10 runs after the blocked check of the login blockers (11), so a blocked login is answered before its CAPTCHA is
 * used, and before their counting (9) and the firewall (8). A rejection sets the response, which stops the event, so a
 * submission without a valid CAPTCHA is neither counted as a failed login nor authenticated.
 */
class LoginCaptchaEventSubscriber implements EventSubscriberInterface
{
    protected const int KERNEL_REQUEST_SUBSCRIBER_PRIORITY = 10;

    /**
     * @param \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Yves\Captcha\Checker\CaptchaRequirementCheckerInterface> $captchaRequirementCheckerStrategyResolver
     */
    public function __construct(
        protected CaptchaClientInterface $captchaClient,
        protected StrategyResolverInterface $captchaRequirementCheckerStrategyResolver,
        protected CaptchaConfig $captchaConfig,
        protected LoginFormResolverInterface $loginFormResolver,
        protected CaptchaRejectionResponseBuilderInterface $captchaRejectionResponseBuilder
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

        $captchaLoginFormTransfers = $this->loginFormResolver->resolveSubmittedLoginForms($request);

        if ($captchaLoginFormTransfers === []) {
            return;
        }

        $captchaRequirementChecker = $this->captchaRequirementCheckerStrategyResolver->get($this->captchaConfig->getCaptchaMode());

        if (count($captchaLoginFormTransfers) > 1) {
            $this->rejectAmbiguousLoginAttempt($event, $captchaLoginFormTransfers, $captchaRequirementChecker);

            return;
        }

        $this->verifyLoginCaptcha($event, $captchaLoginFormTransfers[0], $captchaRequirementChecker);
    }

    protected function verifyLoginCaptcha(
        RequestEvent $event,
        CaptchaLoginFormTransfer $captchaLoginFormTransfer,
        CaptchaRequirementCheckerInterface $captchaRequirementChecker
    ): void {
        $request = $event->getRequest();
        $loginIdentifier = $this->findSubmittedField($request, $captchaLoginFormTransfer->getFormNameOrFail(), $captchaLoginFormTransfer->getUsernameFieldNameOrFail());

        if (!$this->isCaptchaRequired($request, $captchaLoginFormTransfer->getContextOrFail(), $loginIdentifier, $captchaRequirementChecker)) {
            return;
        }

        $captchaVerificationResponseTransfer = $this->captchaClient->verifyCaptcha(
            (new CaptchaVerificationRequestTransfer())
                ->setPayload((string)$this->findCaptchaPayload($request, $captchaLoginFormTransfer))
                ->setContext($captchaLoginFormTransfer->getContextOrFail())
                ->setIp((string)$request->getClientIp()),
        );

        if ($captchaVerificationResponseTransfer->getIsValid()) {
            return;
        }

        $this->rejectLoginAttempt($event, $captchaLoginFormTransfer, $captchaVerificationResponseTransfer, $loginIdentifier);
    }

    /**
     * The customer and the agent login form share their form name, so a submission to a route that is no login check route
     * cannot be tied to one form. A token verified for one form's context would let the other form's authenticator run without
     * the CAPTCHA its own context requires, so the submission is rejected while any of the forms requires a CAPTCHA.
     *
     * @param list<\Generated\Shared\Transfer\CaptchaLoginFormTransfer> $captchaLoginFormTransfers
     */
    protected function rejectAmbiguousLoginAttempt(
        RequestEvent $event,
        array $captchaLoginFormTransfers,
        CaptchaRequirementCheckerInterface $captchaRequirementChecker
    ): void {
        $request = $event->getRequest();
        $captchaLoginFormTransfer = $captchaLoginFormTransfers[0];
        $loginIdentifier = $this->findSubmittedField($request, $captchaLoginFormTransfer->getFormNameOrFail(), $captchaLoginFormTransfer->getUsernameFieldNameOrFail());

        foreach ($captchaLoginFormTransfers as $submittedCaptchaLoginFormTransfer) {
            if ($this->isCaptchaRequired($request, $submittedCaptchaLoginFormTransfer->getContextOrFail(), $loginIdentifier, $captchaRequirementChecker)) {
                $this->rejectLoginAttempt($event, $captchaLoginFormTransfer, $this->createInvalidCaptchaVerificationResponseTransfer(), $loginIdentifier);

                return;
            }
        }
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

        $event->setResponse($this->captchaRejectionResponseBuilder->createCaptchaRejectionResponse(
            $request,
            $captchaVerificationResponseTransfer,
            $captchaLoginFormTransfer->getLoginUrlOrFail(),
        ));
    }

    protected function createInvalidCaptchaVerificationResponseTransfer(): CaptchaVerificationResponseTransfer
    {
        return (new CaptchaVerificationResponseTransfer())
            ->setIsValid(false)
            ->addMessage((new MessageTransfer())->setValue(SharedCaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID));
    }

    protected function isCaptchaRequired(
        Request $request,
        string $context,
        ?string $loginIdentifier,
        CaptchaRequirementCheckerInterface $captchaRequirementChecker
    ): bool {
        if (!$this->captchaClient->isCaptchaEnabled((new CaptchaChallengeRequestTransfer())->setContext($context))) {
            return false;
        }

        return $captchaRequirementChecker->isCaptchaRequired(
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
