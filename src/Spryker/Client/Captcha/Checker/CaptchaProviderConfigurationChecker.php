<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Checker;

use Exception;
use Psr\Log\LoggerInterface;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;

class CaptchaProviderConfigurationChecker implements CaptchaProviderConfigurationCheckerInterface
{
    protected const string LOG_MESSAGE_PROVIDER_NOT_WIRED = 'No CAPTCHA provider plugin is wired in CaptchaDependencyProvider::getCaptchaProviderPlugin(), so the CAPTCHA cannot be turned on.';

    protected const string LOG_MESSAGE_PROVIDER_FAILED = 'CAPTCHA provider %s could not check its settings: %s';

    protected const string LOG_CONTEXT_EXCEPTION = 'exception';

    public function __construct(
        protected LoggerInterface $logger,
        protected ?CaptchaProviderPluginInterface $captchaProviderPlugin,
    ) {
    }

    public function isCaptchaProviderConfigured(): bool
    {
        if ($this->captchaProviderPlugin === null) {
            $this->logger->warning(static::LOG_MESSAGE_PROVIDER_NOT_WIRED);

            return false;
        }

        try {
            return $this->captchaProviderPlugin->isConfigured();
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf(static::LOG_MESSAGE_PROVIDER_FAILED, $this->captchaProviderPlugin::class, $exception->getMessage()),
                [static::LOG_CONTEXT_EXCEPTION => $exception],
            );

            return false;
        }
    }
}
