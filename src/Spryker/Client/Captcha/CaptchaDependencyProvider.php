<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha;

use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use Spryker\Client\Redis\RedisClientInterface;

class CaptchaDependencyProvider extends AbstractDependencyProvider
{
    public const string PLUGIN_CAPTCHA_PROVIDER = 'PLUGIN_CAPTCHA_PROVIDER';

    public const string CLIENT_REDIS = 'CLIENT_REDIS';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addCaptchaProviderPlugin($container);
        $container = $this->addRedisClient($container);

        return $container;
    }

    protected function addCaptchaProviderPlugin(Container $container): Container
    {
        $container->set(static::PLUGIN_CAPTCHA_PROVIDER, function (): ?CaptchaProviderPluginInterface {
            return $this->getCaptchaProviderPlugin();
        });

        return $container;
    }

    protected function addRedisClient(Container $container): Container
    {
        $container->set(static::CLIENT_REDIS, function (Container $container): RedisClientInterface {
            return $container->getLocator()->redis()->client();
        });

        return $container;
    }

    protected function getCaptchaProviderPlugin(): ?CaptchaProviderPluginInterface
    {
        return null;
    }
}
