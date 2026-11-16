<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Expander;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Zed\Captcha\Communication\Checker\CaptchaRequirementCheckerInterface;
use Spryker\Zed\Captcha\Communication\Form\CaptchaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class LoginFormCaptchaExpander implements LoginFormCaptchaExpanderInterface
{
    public function __construct(
        protected CaptchaClientInterface $captchaClient,
        protected CaptchaRequirementCheckerInterface $captchaRequirementChecker,
        protected RequestStack $requestStack
    ) {
    }

    /**
     * @param array<string, mixed> $captchaTypeOptions
     */
    public function expand(FormBuilderInterface $builder, string $context, array $captchaTypeOptions = []): FormBuilderInterface
    {
        if (!$this->captchaClient->isCaptchaEnabled((new CaptchaChallengeRequestTransfer())->setContext($context))) {
            return $builder;
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request === null || !$this->captchaRequirementChecker->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer($request, $context))) {
            return $builder;
        }

        return $builder->add(
            SharedCaptchaConfig::FORM_FIELD_NAME_CAPTCHA,
            CaptchaType::class,
            [CaptchaType::OPTION_CAPTCHA_CONTEXT => $context] + $captchaTypeOptions,
        );
    }

    protected function createCaptchaRequirementRequestTransfer(Request $request, string $context): CaptchaRequirementRequestTransfer
    {
        $lastLoginIdentifier = $request->hasSession() ? $request->getSession()->get(SecurityRequestAttributes::LAST_USERNAME) : null;

        return (new CaptchaRequirementRequestTransfer())
            ->setContext($context)
            ->setIp((string)$request->getClientIp())
            ->setLoginIdentifier(is_string($lastLoginIdentifier) ? $lastLoginIdentifier : null);
    }
}
