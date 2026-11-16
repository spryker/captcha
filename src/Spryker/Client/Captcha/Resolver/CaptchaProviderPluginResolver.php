<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Resolver;

use Psr\Log\LoggerInterface;
use Spryker\Client\Captcha\CaptchaConfig;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;

class CaptchaProviderPluginResolver implements CaptchaProviderPluginResolverInterface
{
    protected const string LOG_MESSAGE_PROVIDER_NOT_WIRED = 'CAPTCHA is enabled, but no CAPTCHA provider plugin is wired in CaptchaDependencyProvider::getCaptchaProviderPlugin(): every challenge and verification is rejected.';

    public function __construct(
        protected CaptchaConfig $captchaConfig,
        protected LoggerInterface $logger,
        protected ?CaptchaProviderPluginInterface $captchaProviderPlugin,
    ) {
    }

    public function findCaptchaProviderPlugin(): ?CaptchaProviderPluginInterface
    {
        if (!$this->captchaConfig->isCaptchaEnabled()) {
            return null;
        }

        if ($this->captchaProviderPlugin === null) {
            $this->logger->error(static::LOG_MESSAGE_PROVIDER_NOT_WIRED);
        }

        return $this->captchaProviderPlugin;
    }
}
