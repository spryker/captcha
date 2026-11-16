<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;

/**
 * Adds the GET route `/captcha/challenge?context=<context>` the CAPTCHA widget fetches its challenge from. It answers the JSON
 * document of a new challenge for a Storefront login form context, `404` for another context, `429` above the rate limit and `503`
 * when no challenge can be created; every answer is `Cache-Control: no-store`.
 *
 * @method \Spryker\Yves\Captcha\CaptchaFactory getFactory()
 * @method \Spryker\Yves\Captcha\CaptchaConfig getConfig()
 */
class CaptchaRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    protected const string CHALLENGE_PATH = '/captcha/challenge';

    protected const string MODULE_NAME = 'Captcha';

    protected const string CONTROLLER_NAME_CHALLENGE = 'Challenge';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection->add(
            $this->getConfig()->getChallengeRouteName(),
            $this->buildGetRoute(static::CHALLENGE_PATH, static::MODULE_NAME, static::CONTROLLER_NAME_CHALLENGE),
        );

        return $routeCollection;
    }
}
