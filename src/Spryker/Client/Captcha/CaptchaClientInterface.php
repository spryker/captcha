<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha;

use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;

interface CaptchaClientInterface
{
    /**
     * Specification:
     * - Requires `CaptchaChallengeRequestTransfer.context`: one of the `CaptchaConfig::CONTEXT_*` login form contexts, or a context of its own for another form.
     * - Returns the Back Office setting `security:captcha:login:is_enabled`, `false` when it has no value.
     * - Does not check that a provider plugin is wired: an enabled CAPTCHA without a provider fails closed in `createChallenge()` and `verifyCaptcha()`.
     *
     * @api
     *
     * @throws \Spryker\Shared\Kernel\Transfer\Exception\RequiredTransferPropertyException
     */
    public function isCaptchaEnabled(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): bool;

    /**
     * Specification:
     * - Returns whether the provider plugin wired in `CaptchaDependencyProvider::getCaptchaProviderPlugin()` is set up, through `CaptchaProviderPluginInterface::isConfigured()`, whether CAPTCHA is enabled or not.
     * - Returns `false` and logs a warning when no provider plugin is wired.
     * - Returns `false` and logs an error when the provider plugin throws an exception.
     * - The Back Office refuses to turn the CAPTCHA on (`security:captcha:login:is_enabled`) while it returns `false`.
     *
     * @api
     */
    public function isCaptchaProviderConfigured(): bool;

    /**
     * Specification:
     * - Requires `CaptchaChallengeRequestTransfer.context`.
     * - Returns `isSuccessful=false` with the `captcha.error.unavailable` message when CAPTCHA is disabled.
     * - Returns `isSuccessful=false` with the `captcha.error.unavailable` message and logs an error when CAPTCHA is enabled but no provider plugin is wired (fail closed).
     * - Otherwise delegates to the provider plugin and returns its response unchanged; the provider returns `isSuccessful=false` with `captcha.error.unavailable` when it cannot sign challenges (fail closed).
     * - Returns `isSuccessful=false` with the `captcha.error.unavailable` message and logs an error when the provider throws an exception.
     * - Returns `isSuccessful=false` with the `captcha.error.unavailable` message and logs an error when the widget of the provider has a tag name or loader tag name that does not match `^[a-z][a-z0-9-]*$`, a name attribute or attribute name that does not match it or starts with `on`, or a script URL that is not an absolute `https://` URL.
     * - When `CaptchaChallengeRequestTransfer.challengeUrl` is set and the provider plugin implements `CaptchaChallengeUrlProviderPluginInterface`,
     *   returns the widget of `createChallengeUrlWidget()` instead: the widget fetches its challenge from `challengeUrl`, so no challenge
     *   is created while the page renders.
     *
     * @api
     *
     * @throws \Spryker\Shared\Kernel\Transfer\Exception\RequiredTransferPropertyException
     */
    public function createChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer;

    /**
     * Specification:
     * - Returns `isValid=false` with `errorCode=required` and the `captcha.error.required` message for an empty `CaptchaVerificationRequestTransfer.payload`, without calling the provider plugin.
     * - Returns `isValid=false` with `isProviderUnavailable=true`, `errorCode=unavailable` and the `captcha.error.unavailable` message when CAPTCHA is disabled or no provider plugin is wired; logs an error when CAPTCHA is enabled but no provider plugin is wired (fail closed).
     * - Otherwise delegates to the provider plugin with payload, context and IP unchanged.
     * - Accepts a payload at most once: when the provider returns a valid response with `CaptchaVerificationResponseTransfer.replayKey`, marks the key as spent in Redis until `CaptchaVerificationResponseTransfer.replayExpiresAt`, and returns `isValid=false` with `errorCode=replayed` and the `captcha.error.invalid` message when the key was already spent.
     * - Returns `isValid=false` with `isProviderUnavailable=true`, `errorCode=unavailable` and the `captcha.error.unavailable` message, and logs an error, when the provider plugin or the Redis replay store throws an exception (fail closed).
     * - Returns the provider response unchanged otherwise.
     *
     * @api
     */
    public function verifyCaptcha(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer;

    /**
     * Specification:
     * - Returns the name of the top-level request parameter in which the widget of the provider plugin posts its token, for example `g-recaptcha-response`.
     * - Returns `null` when the widget posts its token in the CAPTCHA form field (`<form name>[captcha]`), when CAPTCHA is disabled, or when no provider plugin is wired.
     *
     * @api
     */
    public function findResponseFieldName(): ?string;

    /**
     * Specification:
     * - Requires `CaptchaChallengeDataRequestTransfer.context`; `CaptchaChallengeDataRequestTransfer.ip` is the IP of the requesting browser.
     * - Creates the challenge a widget of `createChallenge()` fetches from its challenge URL, through
     *   `CaptchaChallengeUrlProviderPluginInterface::createChallengeData()`, and returns its JSON document in
     *   `CaptchaChallengeDataResponseTransfer.challengeData`.
     * - Counts the request per context and IP in Redis, the request of an IPv6 address per context and /64 network; returns
     *   `isSuccessful=false` with `isRateLimited=true`, without creating a challenge, above `CaptchaConfig::getChallengeRateLimitPerIp()`
     *   requests per `CaptchaConfig::getChallengeRateLimitWindowSeconds()`; `retryAfterSeconds` is then the window length (at least `1`),
     *   the latest time after which the window of the request has ended.
     * - Counts the requests of the context from all IPs that pass their IP limit, and logs a warning once per window when they reach
     *   `CaptchaConfig::getChallengeRateWarningThresholdPerContext()`; the count of the context never rejects a request.
     * - Sets the expiry of the window on every counter it creates, and on a counter it finds without one.
     * - Keeps the IP out of the Redis keys.
     * - Returns `isSuccessful=false` with the `captcha.error.unavailable` message when CAPTCHA is disabled or no provider plugin is wired.
     * - Returns `isSuccessful=false` with the `captcha.error.unavailable` message and logs an error when the provider plugin does not
     *   implement `CaptchaChallengeUrlProviderPluginInterface`, when the Redis counters cannot be updated or when the provider throws.
     *
     * @api
     *
     * @throws \Spryker\Shared\Kernel\Transfer\Exception\RequiredTransferPropertyException
     */
    public function createChallengeData(
        CaptchaChallengeDataRequestTransfer $captchaChallengeDataRequestTransfer
    ): CaptchaChallengeDataResponseTransfer;
}
