<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha;

use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\ZedUi\ZedUiFactoryInterface;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CaptchaDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_CAPTCHA = 'CLIENT_CAPTCHA';

    /**
     * @uses \Spryker\Zed\Http\Communication\Plugin\Application\HttpApplicationPlugin::SERVICE_REQUEST_STACK
     */
    public const string SERVICE_REQUEST_STACK = 'request_stack';

    public const string PLUGINS_CAPTCHA_REQUIREMENT = 'PLUGINS_CAPTCHA_REQUIREMENT';

    public const string FACADE_MESSENGER = 'FACADE_MESSENGER';

    /**
     * @uses \Spryker\Zed\ZedUi\Communication\Plugin\Application\ZedUiApplicationPlugin::SERVICE_ZED_UI_FACTORY
     */
    public const string SERVICE_ZED_UI_FACTORY = 'SERVICE_ZED_UI_FACTORY';

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addCaptchaClient($container);
        $container = $this->addRequestStackService($container);
        $container = $this->addCaptchaRequirementPlugins($container);
        $container = $this->addMessengerFacade($container);
        $container = $this->addZedUiFactory($container);

        return $container;
    }

    protected function addMessengerFacade(Container $container): Container
    {
        $container->set(static::FACADE_MESSENGER, function (Container $container): MessengerFacadeInterface {
            return $container->getLocator()->messenger()->facade();
        });

        return $container;
    }

    protected function addZedUiFactory(Container $container): Container
    {
        $container->set(static::SERVICE_ZED_UI_FACTORY, function (Container $container): ?ZedUiFactoryInterface {
            // The Back Office application has no ZedUi factory; only the Merchant Portal registers it.
            if (!$container->hasApplicationService(static::SERVICE_ZED_UI_FACTORY)) {
                return null;
            }

            return $container->getApplicationService(static::SERVICE_ZED_UI_FACTORY);
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
