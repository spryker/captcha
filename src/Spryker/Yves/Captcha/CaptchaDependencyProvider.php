<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha;

use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;
use Spryker\Yves\Messenger\FlashMessenger\FlashMessengerInterface;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CaptchaDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_CAPTCHA = 'CLIENT_CAPTCHA';

    /**
     * @uses \Spryker\Yves\Http\Plugin\Application\HttpApplicationPlugin::SERVICE_REQUEST_STACK
     */
    public const string SERVICE_REQUEST_STACK = 'request_stack';

    public const string PLUGINS_CAPTCHA_REQUIREMENT = 'PLUGINS_CAPTCHA_REQUIREMENT';

    /**
     * @uses \Spryker\Yves\Messenger\Plugin\Application\FlashMessengerApplicationPlugin::SERVICE_FLASH_MESSENGER
     */
    public const string SERVICE_FLASH_MESSENGER = 'flash_messenger';

    /**
     * @uses \Spryker\Yves\Router\Plugin\Application\RouterApplicationPlugin::SERVICE_ROUTER
     */
    public const string SERVICE_ROUTER = 'routers';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addCaptchaClient($container);
        $container = $this->addRequestStackService($container);
        $container = $this->addCaptchaRequirementPlugins($container);
        $container = $this->addFlashMessenger($container);
        $container = $this->addRouterService($container);

        return $container;
    }

    protected function addRouterService(Container $container): Container
    {
        $container->set(static::SERVICE_ROUTER, function (Container $container): ChainRouterInterface {
            return $container->getApplicationService(static::SERVICE_ROUTER);
        });

        return $container;
    }

    protected function addFlashMessenger(Container $container): Container
    {
        $container->set(static::SERVICE_FLASH_MESSENGER, function (Container $container): FlashMessengerInterface {
            return $container->getApplicationService(static::SERVICE_FLASH_MESSENGER);
        });

        return $container;
    }

    protected function addCaptchaRequirementPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_CAPTCHA_REQUIREMENT, function (): array {
            return $this->getCaptchaRequirementPlugins();
        });

        return $container;
    }

    /**
     * @return list<\Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface>
     */
    protected function getCaptchaRequirementPlugins(): array
    {
        return [];
    }

    protected function addCaptchaClient(Container $container): Container
    {
        $container->set(static::CLIENT_CAPTCHA, function (Container $container): CaptchaClientInterface {
            return $container->getLocator()->captcha()->client();
        });

        return $container;
    }

    protected function addRequestStackService(Container $container): Container
    {
        $container->set(static::SERVICE_REQUEST_STACK, function (Container $container): RequestStack {
            return $container->getApplicationService(static::SERVICE_REQUEST_STACK);
        });

        return $container;
    }
}
