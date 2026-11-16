<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Verifier;

use Exception;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Client\Captcha\ReplayStore\CaptchaReplayStoreInterface;
use Spryker\Client\Captcha\Resolver\CaptchaProviderPluginResolverInterface;
use Spryker\Shared\Captcha\CaptchaConfig;

class CaptchaVerifier implements CaptchaVerifierInterface
{
    protected const int MINIMUM_REPLAY_TTL = 1;

    protected const string LOG_MESSAGE_PROVIDER_FAILED = 'CAPTCHA payload could not be verified with provider %s: %s';

    protected const string LOG_CONTEXT_EXCEPTION = 'exception';

    public function __construct(
        protected CaptchaProviderPluginResolverInterface $captchaProviderPluginResolver,
        protected CaptchaReplayStoreInterface $captchaReplayStore,
        protected LoggerInterface $logger,
    ) {
    }

    public function verifyCaptcha(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer
    {
        if ((string)$captchaVerificationRequestTransfer->getPayload() === '') {
            return $this->createInvalidResponse(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_REQUIRED, CaptchaConfig::ERROR_CODE_REQUIRED);
        }

        $captchaProviderPlugin = $this->captchaProviderPluginResolver->findCaptchaProviderPlugin();

        if ($captchaProviderPlugin === null) {
            return $this->createUnavailableResponse();
        }

        try {
            return $this->acceptOnlyOnce($captchaProviderPlugin->verify($captchaVerificationRequestTransfer));
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf(static::LOG_MESSAGE_PROVIDER_FAILED, $captchaProviderPlugin::class, $exception->getMessage()),
                [static::LOG_CONTEXT_EXCEPTION => $exception],
            );

            return $this->createUnavailableResponse();
        }
    }

    protected function acceptOnlyOnce(CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer): CaptchaVerificationResponseTransfer
    {
        $replayKey = (string)$captchaVerificationResponseTransfer->getReplayKey();

        if (!$captchaVerificationResponseTransfer->getIsValid() || $replayKey === '') {
            return $captchaVerificationResponseTransfer;
        }

        if ($this->captchaReplayStore->markChallengeAsSpent($replayKey, $this->calculateReplayTtl($captchaVerificationResponseTransfer))) {
            return $captchaVerificationResponseTransfer;
        }

        return $this->createInvalidResponse(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID, CaptchaConfig::ERROR_CODE_REPLAYED);
    }

    protected function calculateReplayTtl(CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer): int
    {
        return max(static::MINIMUM_REPLAY_TTL, $captchaVerificationResponseTransfer->getReplayExpiresAtOrFail() - time());
    }

    protected function createUnavailableResponse(): CaptchaVerificationResponseTransfer
    {
        return $this->createInvalidResponse(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE, CaptchaConfig::ERROR_CODE_UNAVAILABLE)
            ->setIsProviderUnavailable(true);
    }

    protected function createInvalidResponse(string $glossaryKey, string $errorCode): CaptchaVerificationResponseTransfer
    {
        return (new CaptchaVerificationResponseTransfer())
            ->setIsValid(false)
            ->setIsExpired(false)
            ->setErrorCode($errorCode)
            ->addMessage((new MessageTransfer())->setValue($glossaryKey));
    }
}
