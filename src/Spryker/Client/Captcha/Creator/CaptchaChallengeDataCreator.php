<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Creator;

use Exception;
use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Client\Captcha\RateLimiter\CaptchaChallengeRateLimiterInterface;
use Spryker\Client\Captcha\Resolver\CaptchaProviderPluginResolverInterface;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaChallengeUrlProviderPluginInterface;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Shared\Captcha\CaptchaConfig;

class CaptchaChallengeDataCreator implements CaptchaChallengeDataCreatorInterface
{
    protected const string LOG_MESSAGE_PROVIDER_WITHOUT_CHALLENGE_URL = 'CAPTCHA provider %s does not implement CaptchaChallengeUrlProviderPluginInterface, so its widget cannot fetch a challenge from the challenge URL.';

    protected const string LOG_MESSAGE_RATE_LIMITER_FAILED = 'The CAPTCHA challenge request counters could not be updated, so no challenge is created: %s';

    protected const string LOG_MESSAGE_PROVIDER_FAILED = 'CAPTCHA provider %s could not create a challenge: %s';

    protected const string LOG_CONTEXT_EXCEPTION = 'exception';

    public function __construct(
        protected CaptchaProviderPluginResolverInterface $captchaProviderPluginResolver,
        protected CaptchaChallengeRateLimiterInterface $captchaChallengeRateLimiter,
        protected LoggerInterface $logger,
    ) {
    }

    public function createChallengeData(CaptchaChallengeDataRequestTransfer $captchaChallengeDataRequestTransfer): CaptchaChallengeDataResponseTransfer
    {
        $captchaChallengeDataRequestTransfer->requireContext();

        $context = (string)$captchaChallengeDataRequestTransfer->getContext();
        $captchaProviderPlugin = $this->captchaProviderPluginResolver->findCaptchaProviderPlugin();

        if ($captchaProviderPlugin === null) {
            return $this->createUnavailableResponse();
        }

        if (!$captchaProviderPlugin instanceof CaptchaChallengeUrlProviderPluginInterface) {
            $this->logger->error(sprintf(static::LOG_MESSAGE_PROVIDER_WITHOUT_CHALLENGE_URL, $captchaProviderPlugin::class));

            return $this->createUnavailableResponse();
        }

        try {
            $isRateLimited = $this->captchaChallengeRateLimiter->isRateLimited($context, (string)$captchaChallengeDataRequestTransfer->getIp());
        } catch (Exception $exception) {
            $this->logger->error(sprintf(static::LOG_MESSAGE_RATE_LIMITER_FAILED, $exception->getMessage()), [static::LOG_CONTEXT_EXCEPTION => $exception]);

            return $this->createUnavailableResponse();
        }

        if ($isRateLimited) {
            return (new CaptchaChallengeDataResponseTransfer())
                ->setIsSuccessful(false)
                ->setIsRateLimited(true)
                ->setRetryAfterSeconds($this->captchaChallengeRateLimiter->getWindowSeconds());
        }

        return $this->createProviderChallengeData($captchaProviderPlugin, $context);
    }

    protected function createProviderChallengeData(
        CaptchaProviderPluginInterface&CaptchaChallengeUrlProviderPluginInterface $captchaProviderPlugin,
        string $context
    ): CaptchaChallengeDataResponseTransfer {
        try {
            return $captchaProviderPlugin->createChallengeData((new CaptchaChallengeRequestTransfer())->setContext($context));
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf(static::LOG_MESSAGE_PROVIDER_FAILED, $captchaProviderPlugin::class, $exception->getMessage()),
                [static::LOG_CONTEXT_EXCEPTION => $exception],
            );

            return $this->createUnavailableResponse();
        }
    }

    protected function createUnavailableResponse(): CaptchaChallengeDataResponseTransfer
    {
        return (new CaptchaChallengeDataResponseTransfer())
            ->setIsSuccessful(false)
            ->setIsRateLimited(false)
            ->addMessage((new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE));
    }
}
