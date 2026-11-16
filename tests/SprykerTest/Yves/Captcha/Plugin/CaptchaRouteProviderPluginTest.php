<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Yves\Captcha\Plugin;

use Codeception\Test\Unit;
use Spryker\Yves\Captcha\CaptchaConfig;
use Spryker\Yves\Captcha\Controller\ChallengeController;
use Spryker\Yves\Captcha\Plugin\Router\CaptchaRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;
use SprykerTest\Yves\Captcha\CaptchaYvesTester;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Yves
 * @group Captcha
 * @group Plugin
 * @group CaptchaRouteProviderPluginTest
 * Add your own group annotations below this line
 */
class CaptchaRouteProviderPluginTest extends Unit
{
    protected CaptchaYvesTester $tester;

    public function testAddsTheGetRouteOfTheChallengeEndpoint(): void
    {
        // Arrange
        $captchaRouteProviderPlugin = new CaptchaRouteProviderPlugin();

        // Act
        $routeCollection = $captchaRouteProviderPlugin->addRoutes(new RouteCollection());

        // Assert
        $route = $routeCollection->get((new CaptchaConfig())->getChallengeRouteName());
        $this->assertNotNull($route);
        $this->assertSame('/captcha/challenge', $route->getPath());
        $this->assertSame([Request::METHOD_GET], $route->getMethods());
        $this->assertSame(ChallengeController::class, $route->getDefault('_controller')[0]);
    }
}
