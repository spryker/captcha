<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Captcha;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class CaptchaConfig extends AbstractSharedConfig
{
    /**
     * Specification:
     * - Name of the form field that carries the CAPTCHA payload, posted as `<form name>[captcha]`.
     *
     * @api
     */
    public const string FORM_FIELD_NAME_CAPTCHA = 'captcha';

    /**
     * Specification:
     * - Option of the Yves and Zed `CaptchaType` that carries the context the challenge is bound to (one of the `CONTEXT_*` login contexts or a context of its own).
     *
     * @api
     */
    public const string FORM_OPTION_CAPTCHA_CONTEXT = 'captcha_context';

    /**
     * Specification:
     * - CAPTCHA context of the Storefront customer login form.
     *
     * @api
     */
    public const string CONTEXT_STOREFRONT_CUSTOMER_LOGIN = 'storefront_customer_login';

    /**
     * Specification:
     * - CAPTCHA context of the Storefront agent login form.
     *
     * @api
     */
    public const string CONTEXT_STOREFRONT_AGENT_LOGIN = 'storefront_agent_login';

    /**
     * Specification:
     * - CAPTCHA context of the Back Office login form.
     *
     * @api
     */
    public const string CONTEXT_BACKOFFICE_USER_LOGIN = 'backoffice_user_login';

    /**
     * Specification:
     * - CAPTCHA context of the Merchant Portal login form.
     *
     * @api
     */
    public const string CONTEXT_MERCHANT_PORTAL_USER_LOGIN = 'merchant_portal_user_login';

    /**
     * Specification:
     * - CAPTCHA context of the Merchant Portal agent login form.
     *
     * @api
     */
    public const string CONTEXT_MERCHANT_PORTAL_AGENT_LOGIN = 'merchant_portal_agent_login';

    /**
     * Specification:
     * - Query parameter of the challenge endpoints that carries the CAPTCHA context, as in `/captcha/challenge?context=<context>`.
     *
     * @api
     */
    public const string CHALLENGE_URL_QUERY_PARAMETER_CONTEXT = 'context';

    /**
     * Specification:
     * - Key of the JSON error answer of the challenge endpoints, as in `{"error":"rate_limited"}`.
     *
     * @api
     */
    public const string CHALLENGE_RESPONSE_KEY_ERROR = 'error';

    /**
     * Specification:
     * - Error of a challenge endpoint answering 404: the context is unknown or belongs to another application.
     *
     * @api
     */
    public const string CHALLENGE_RESPONSE_ERROR_NOT_FOUND = 'not_found';

    /**
     * Specification:
     * - Error of a challenge endpoint answering 405: the request method is neither `GET` nor `HEAD`.
     *
     * @api
     */
    public const string CHALLENGE_RESPONSE_ERROR_METHOD_NOT_ALLOWED = 'method_not_allowed';

    /**
     * Specification:
     * - Error of a challenge endpoint answering 429: the rate limit of the challenge requests is reached.
     *
     * @api
     */
    public const string CHALLENGE_RESPONSE_ERROR_RATE_LIMITED = 'rate_limited';

    /**
     * Specification:
     * - Error of a challenge endpoint answering 503: no challenge can be created.
     *
     * @api
     */
    public const string CHALLENGE_RESPONSE_ERROR_UNAVAILABLE = 'unavailable';

    /**
     * Specification:
     * - Key of the Back Office setting that switches the CAPTCHA on or off, off by default.
     * - Covers the login forms and every other form that uses the `CaptchaType`.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_IS_CAPTCHA_ENABLED = 'security:captcha:login:is_enabled';

    /**
     * Specification:
     * - Key of the Back Office setting that decides when the login forms ask for a CAPTCHA.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_CAPTCHA_MODE = 'security:captcha:login:mode';

    /**
     * Specification:
     * - CAPTCHA mode in which every login form asks for a CAPTCHA on every login.
     *
     * @api
     */
    public const string CAPTCHA_MODE_ALWAYS = 'always';

    /**
     * Specification:
     * - CAPTCHA mode in which a login form asks for a CAPTCHA only while a `CaptchaRequirementPluginInterface` plugin requires one,
     *   for example after failed login attempts.
     *
     * @api
     */
    public const string CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS = 'after_failed_attempts';

    /**
     * Specification:
     * - Block prefix of the Yves and Zed `CaptchaType`; the form themes render it in the `captcha_widget` block.
     *
     * @api
     */
    public const string FORM_TYPE_BLOCK_PREFIX = 'captcha';

    /**
     * Specification:
     * - Form view variable holding the `CaptchaWidgetTransfer` to render, `null` when no challenge could be created.
     *
     * @api
     */
    public const string FORM_VIEW_VARIABLE_CAPTCHA_WIDGET = 'captchaWidget';

    /**
     * Specification:
     * - Form view variable holding the messages to show next to the widget.
     *
     * @api
     */
    public const string FORM_VIEW_VARIABLE_CAPTCHA_MESSAGES = 'captchaMessages';

    /**
     * Specification:
     * - Glossary key of the message returned when a CAPTCHA is required but no payload was submitted.
     *
     * @api
     */
    public const string GLOSSARY_KEY_CAPTCHA_ERROR_REQUIRED = 'captcha.error.required';

    /**
     * Specification:
     * - Glossary key of the message returned when a submitted CAPTCHA payload is not valid.
     *
     * @api
     */
    public const string GLOSSARY_KEY_CAPTCHA_ERROR_INVALID = 'captcha.error.invalid';

    /**
     * Specification:
     * - Glossary key of the message returned when the challenge of a submitted CAPTCHA payload has expired.
     *
     * @api
     */
    public const string GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED = 'captcha.error.expired';

    /**
     * Specification:
     * - Glossary key of the message returned when CAPTCHA is disabled, no provider is wired or the provider cannot create or verify challenges.
     *
     * @api
     */
    public const string GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE = 'captcha.error.unavailable';

    /**
     * Specification:
     * - Value of `CaptchaVerificationResponse.errorCode` when a CAPTCHA is required but no payload was submitted.
     *
     * @api
     */
    public const string ERROR_CODE_REQUIRED = 'required';

    /**
     * Specification:
     * - Value of `CaptchaVerificationResponse.errorCode` when the payload of an already accepted challenge is submitted again.
     *
     * @api
     */
    public const string ERROR_CODE_REPLAYED = 'replayed';

    /**
     * Specification:
     * - Value of `CaptchaVerificationResponse.errorCode` when no provider is wired, or the provider or the replay store cannot be reached.
     *
     * @api
     */
    public const string ERROR_CODE_UNAVAILABLE = 'unavailable';
}
