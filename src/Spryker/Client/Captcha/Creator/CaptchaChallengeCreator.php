<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Creator;

use Exception;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Client\Captcha\Resolver\CaptchaProviderPluginResolverInterface;
use Spryker\Client\Captcha\Validator\CaptchaWidgetValidatorInterface;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaChallengeUrlProviderPluginInterface;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Shared\Captcha\CaptchaConfig;

class CaptchaChallengeCreator implements CaptchaChallengeCreatorInterface
{
    protected const string LOG_MESSAGE_PROVIDER_FAILED = 'CAPTCHA provider %s could not create a challenge: %s';

    protected const string LOG_MESSAGE_UNSAFE_WIDGET = 'CAPTCHA provider %s returned a widget the form themes cannot render safely: %s';

    protected const string LOG_CONTEXT_EXCEPTION = 'exception';

    public function __construct(
        protected CaptchaProviderPluginResolverInterface $captchaProviderPluginResolver,
        protected CaptchaWidgetValidatorInterface $captchaWidgetValidator,
        protected LoggerInterface $logger,
    ) {
    }

    public function createChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        $captchaChallengeRequestTransfer->requireContext();

        $captchaProviderPlugin = $this->captchaProviderPluginResolver->findCaptchaProviderPlugin();

        if ($captchaProviderPlugin === null) {
            return $this->createUnavailableResponse();
        }

        try {
            $captchaChallengeResponseTransfer = $this->createProviderChallenge($captchaProviderPlugin, $captchaChallengeRequestTransfer);
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf(static::LOG_MESSAGE_PROVIDER_FAILED, $captchaProviderPlugin::class, $exception->getMessage()),
                [static::LOG_CONTEXT_EXCEPTION => $exception],
            );

            return $this->createUnavailableResponse();
        }

        $captchaWidgetTransfer = $captchaChallengeResponseTransfer->getWidget();
        $violations = $captchaWidgetTransfer !== null ? $this->captchaWidgetValidator->validate($captchaWidgetTransfer) : [];

        if ($violations === []) {
            return $captchaChallengeResponseTransfer;
        }

        $this->logger->error(sprintf(static::LOG_MESSAGE_UNSAFE_WIDGET, $captchaProviderPlugin::class, implode(' ', $violations)));

        return $this->createUnavailableResponse();
    }

    protected function createProviderChallenge(
        CaptchaProviderPluginInterface $captchaProviderPlugin,
        CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer
    ): CaptchaChallengeResponseTransfer {
        if ($captchaProviderPlugin instanceof CaptchaChallengeUrlProviderPluginInterface && (string)$captchaChallengeRequestTransfer->getChallengeUrl() !== '') {
            return $captchaProviderPlugin->createChallengeUrlWidget($captchaChallengeRequestTransfer);
        }

        return $captchaProviderPlugin->createChallenge($captchaChallengeRequestTransfer);
    }

    protected function createUnavailableResponse(): CaptchaChallengeResponseTransfer
    {
        return (new CaptchaChallengeResponseTransfer())
            ->setIsSuccessful(false)
            ->addMessage((new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE));
    }
}
