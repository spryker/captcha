<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha;

use Generated\Shared\Transfer\CaptchaLoginFormTransfer;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Yves\Kernel\AbstractBundleConfig;

class CaptchaConfig extends AbstractBundleConfig
{
    /**
     * @uses \SprykerShop\Yves\CustomerPage\Form\LoginForm::FORM_NAME
     * @uses \SprykerShop\Yves\AgentPage\Form\AgentLoginForm::FORM_NAME
     */
    protected const string LOGIN_FORM_NAME = 'loginForm';

    /**
     * @uses \SprykerShop\Yves\CustomerPage\Form\LoginForm::FIELD_EMAIL
     * @uses \SprykerShop\Yves\AgentPage\Form\AgentLoginForm::FIELD_EMAIL
     */
    protected const string LOGIN_FORM_FIELD_EMAIL = 'email';

    /**
     * @uses \SprykerShop\Yves\CustomerPage\Formatter\LoginCheckUrlFormatter::ROUTE_CHECK_PATH
     */
    protected const string CUSTOMER_LOGIN_CHECK_ROUTE_NAME = 'login_check';

    /**
     * @uses \SprykerShop\Yves\AgentPage\Formatter\LoginCheckUrlFormatter::ROUTE_CHECK_PATH
     */
    protected const string AGENT_LOGIN_CHECK_ROUTE_NAME = 'agent_login_check';

    protected const string CUSTOMER_LOGIN_URL = '/login';

    protected const string AGENT_LOGIN_URL = '/agent/login';

    protected const string CHALLENGE_ROUTE_NAME = 'captcha-challenge';

    /**
     * Specification:
     * - Returns the CAPTCHA mode of the login forms from the Back Office setting `security:captcha:login:mode`, read from the storage.
     * - Returns `after_failed_attempts` when the setting has no value.
     *
     * @api
     */
    public function getCaptchaMode(): string
    {
        return (string)$this->getModuleConfig(SharedCaptchaConfig::CONFIGURATION_KEY_CAPTCHA_MODE, SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS);
    }

    /**
     * Specification:
     * - Returns the Storefront login forms whose submission is checked for a CAPTCHA before the firewall authenticates it.
     * - The customer and the agent login form share the form name, so a submission is matched to a form by its login-check route:
     *   the route name equals `loginCheckRouteName` or ends with `_` followed by it (store and locale prefixes), the longest name first.
     * - `loginUrl` is the page a submission rejected for its CAPTCHA is redirected to when the request has no same-host referer.
     *
     * @api
     *
     * @return list<\Generated\Shared\Transfer\CaptchaLoginFormTransfer>
     */
    public function getLoginForms(): array
    {
        return [
            $this->createLoginForm(SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN, static::CUSTOMER_LOGIN_CHECK_ROUTE_NAME, static::CUSTOMER_LOGIN_URL),
            $this->createLoginForm(SharedCaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN, static::AGENT_LOGIN_CHECK_ROUTE_NAME, static::AGENT_LOGIN_URL),
        ];
    }

    /**
     * Specification:
     * - Returns the name of the route of `CaptchaRouteProviderPlugin` from which the widget of a provider that implements
     *   `CaptchaChallengeUrlProviderPluginInterface` fetches its challenge.
     *
     * @api
     */
    public function getChallengeRouteName(): string
    {
        return static::CHALLENGE_ROUTE_NAME;
    }

    protected function createLoginForm(string $context, string $loginCheckRouteName, string $loginUrl): CaptchaLoginFormTransfer
    {
        return (new CaptchaLoginFormTransfer())
            ->setFormName(static::LOGIN_FORM_NAME)
            ->setContext($context)
            ->setUsernameFieldName(static::LOGIN_FORM_FIELD_EMAIL)
            ->setLoginCheckRouteName($loginCheckRouteName)
            ->setLoginUrl($loginUrl);
    }
}
