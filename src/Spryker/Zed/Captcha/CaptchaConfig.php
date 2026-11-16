<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha;

use Generated\Shared\Transfer\CaptchaLoginFormTransfer;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Zed\Kernel\AbstractBundleConfig;

class CaptchaConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Value of `CaptchaLoginForm.rejectionResponseType` for a login form whose rejected submission is redirected to the login
     *   page with an error message, and answered with JSON for the multi-factor authentication script of the Back Office.
     *
     * @api
     */
    public const string REJECTION_RESPONSE_TYPE_REDIRECT = 'redirect';

    /**
     * Specification:
     * - Value of `CaptchaLoginForm.rejectionResponseType` for a login form of the Merchant Portal UI: a JSON submission is answered
     *   with a ZedUi redirect action and a notification, any other submission is redirected like `redirect`.
     *
     * @api
     */
    public const string REJECTION_RESPONSE_TYPE_ZED_UI = 'zed_ui';

    /**
     * @uses \Spryker\Zed\SecurityGui\Communication\Form\LoginForm::FORM_NAME
     */
    protected const string BACKOFFICE_USER_LOGIN_FORM_NAME = 'auth';

    /**
     * @uses \Spryker\Zed\SecurityMerchantPortalGui\Communication\Form\MerchantLoginForm::FORM_NAME
     */
    protected const string MERCHANT_PORTAL_USER_LOGIN_FORM_NAME = 'security-merchant-portal-gui';

    /**
     * @uses \Spryker\Zed\AgentSecurityMerchantPortalGui\Communication\Form\AgentMerchantLoginForm::FORM_NAME
     */
    protected const string MERCHANT_PORTAL_AGENT_LOGIN_FORM_NAME = 'agent-security-merchant-portal-gui';

    /**
     * @uses \Spryker\Zed\SecurityGui\Communication\Form\LoginForm::FIELD_USERNAME
     */
    protected const string LOGIN_FORM_FIELD_USERNAME = 'username';

    /**
     * @uses \Spryker\Zed\SecurityGui\SecurityGuiConfig::LOGIN_PATH
     */
    protected const string BACKOFFICE_USER_LOGIN_URL = '/security-gui/login';

    /**
     * @uses \Spryker\Zed\SecurityMerchantPortalGui\SecurityMerchantPortalGuiConfig::LOGIN_URL
     */
    protected const string MERCHANT_PORTAL_USER_LOGIN_URL = '/security-merchant-portal-gui/login';

    /**
     * @uses \Spryker\Zed\AgentSecurityMerchantPortalGui\AgentSecurityMerchantPortalGuiConfig::PATH_LOGIN
     */
    protected const string MERCHANT_PORTAL_AGENT_LOGIN_URL = '/agent-security-merchant-portal-gui/login';

    /**
     * @uses \Spryker\Zed\Captcha\Communication\Controller\ChallengeController::indexAction()
     */
    protected const string BACKOFFICE_CHALLENGE_URL = '/captcha/challenge';

    /**
     * @uses \Spryker\Zed\Captcha\Communication\Controller\MerchantPortalChallengeController::indexAction()
     */
    protected const string MERCHANT_PORTAL_CHALLENGE_URL = '/captcha/merchant-portal-challenge';

    /**
     * Specification:
     * - Returns the CAPTCHA mode of the login forms from the Back Office setting `security:captcha:login:mode`, read from the database.
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
     * - Returns the Back Office and Merchant Portal login forms whose submission is checked for a CAPTCHA before the firewall authenticates it.
     * - A form is recognized by its name in the POST body; `usernameFieldName` names its username field and `loginUrl` is the page
     *   a submission rejected for its CAPTCHA is redirected to.
     * - `rejectionResponseType` is one of the `REJECTION_RESPONSE_TYPE_*` values; a rejected submission of a form with another value
     *   fails with an exception.
     * - `challengeUrl` is the path of the challenge endpoint of the application that renders the form; the widget of a provider that
     *   implements `CaptchaChallengeUrlProviderPluginInterface` fetches its challenge from it. Without it the form creates its
     *   challenge while the page renders. A challenge endpoint creates challenges only for the contexts of the forms whose
     *   `challengeUrl` is its own path.
     * - A project adds a login form by overriding this method and appending a `CaptchaLoginFormTransfer` with its own context.
     *
     * @api
     *
     * @return list<\Generated\Shared\Transfer\CaptchaLoginFormTransfer>
     */
    public function getLoginForms(): array
    {
        return [
            $this->createLoginForm(
                static::BACKOFFICE_USER_LOGIN_FORM_NAME,
                SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN,
                static::BACKOFFICE_USER_LOGIN_URL,
                static::REJECTION_RESPONSE_TYPE_REDIRECT,
                static::BACKOFFICE_CHALLENGE_URL,
            ),
            $this->createLoginForm(
                static::MERCHANT_PORTAL_USER_LOGIN_FORM_NAME,
                SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_USER_LOGIN,
                static::MERCHANT_PORTAL_USER_LOGIN_URL,
                static::REJECTION_RESPONSE_TYPE_ZED_UI,
                static::MERCHANT_PORTAL_CHALLENGE_URL,
            ),
            $this->createLoginForm(
                static::MERCHANT_PORTAL_AGENT_LOGIN_FORM_NAME,
                SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_AGENT_LOGIN,
                static::MERCHANT_PORTAL_AGENT_LOGIN_URL,
                static::REJECTION_RESPONSE_TYPE_ZED_UI,
                static::MERCHANT_PORTAL_CHALLENGE_URL,
            ),
        ];
    }

    protected function createLoginForm(
        string $formName,
        string $context,
        string $loginUrl,
        string $rejectionResponseType,
        string $challengeUrl
    ): CaptchaLoginFormTransfer {
        return (new CaptchaLoginFormTransfer())
            ->setFormName($formName)
            ->setContext($context)
            ->setUsernameFieldName(static::LOGIN_FORM_FIELD_USERNAME)
            ->setLoginUrl($loginUrl)
            ->setRejectionResponseType($rejectionResponseType)
            ->setChallengeUrl($challengeUrl);
    }
}
