<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha;

use Generated\Shared\Transfer\RedisConfigurationTransfer;
use Generated\Shared\Transfer\RedisCredentialsTransfer;
use Spryker\Client\Kernel\AbstractBundleConfig;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\Captcha\CaptchaConstants;

class CaptchaConfig extends AbstractBundleConfig
{
    protected const bool DEFAULT_IS_CAPTCHA_ENABLED = false;

    protected const string REDIS_CONNECTION_KEY = 'CAPTCHA_REDIS';

    protected const string DEFAULT_REDIS_SCHEME = 'tcp';

    protected const int CHALLENGE_RATE_LIMIT_WINDOW_SECONDS = 60;

    protected const int CHALLENGE_RATE_LIMIT_PER_IP = 30;

    protected const int CHALLENGE_RATE_WARNING_THRESHOLD_PER_CONTEXT = 600;

    /**
     * Specification:
     * - Returns the Back Office setting `security:captcha:login:is_enabled`, `false` when it has no value.
     * - An enabled CAPTCHA without a wired provider plugin rejects every challenge and verification (fail closed).
     *
     * @api
     */
    public function isCaptchaEnabled(): bool
    {
        return (bool)filter_var(
            $this->getModuleConfig(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED, static::DEFAULT_IS_CAPTCHA_ENABLED),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    /**
     * Specification:
     * - Returns the key of the Redis connection that stores spent CAPTCHA challenges and the challenge request counters.
     *
     * @api
     */
    public function getRedisConnectionKey(): string
    {
        return static::REDIS_CONNECTION_KEY;
    }

    /**
     * Specification:
     * - Returns the configuration of the Redis connection that stores spent CAPTCHA challenges and the challenge request counters,
     *   from the `CaptchaConstants::REDIS_*` constants.
     *
     * @api
     */
    public function getRedisConnectionConfiguration(): RedisConfigurationTransfer
    {
        return (new RedisConfigurationTransfer())
            ->setDataSourceNames($this->getDataSourceNames())
            ->setConnectionCredentials($this->getConnectionCredentials())
            ->setClientOptions($this->getConnectionOptions());
    }

    /**
     * Specification:
     * - Returns the length in seconds of the window in which `createChallengeData()` counts the challenge requests of an IP and of
     *   a context; a window starts with its first request.
     * - Values below `1` count as `1`.
     *
     * @api
     */
    public function getChallengeRateLimitWindowSeconds(): int
    {
        return static::CHALLENGE_RATE_LIMIT_WINDOW_SECONDS;
    }

    /**
     * Specification:
     * - Returns the number of challenges `createChallengeData()` creates per window for one IP and one context; further requests
     *   are rejected as rate-limited.
     * - The requests of an IPv6 address count for its /64 network, the requests of an IPv4-mapped IPv6 address for its IPv4 address.
     * - Values below `1` count as `1`.
     *
     * @api
     */
    public function getChallengeRateLimitPerIp(): int
    {
        return static::CHALLENGE_RATE_LIMIT_PER_IP;
    }

    /**
     * Specification:
     * - Returns the number of challenge requests of one context, from all IPs together, at which `createChallengeData()` logs a
     *   warning, once per window.
     * - Never rejects a request: a limit for all visitors of a login form would let a few hundred anonymous requests lock every
     *   visitor out of it. Limit a flood from many IPs before the application, for example in the load balancer.
     * - `0` disables the count of the context.
     *
     * @api
     */
    public function getChallengeRateWarningThresholdPerContext(): int
    {
        return static::CHALLENGE_RATE_WARNING_THRESHOLD_PER_CONTEXT;
    }

    /**
     * @return array<string>
     */
    protected function getDataSourceNames(): array
    {
        return $this->get(CaptchaConstants::REDIS_DATA_SOURCE_NAMES, []);
    }

    protected function getConnectionCredentials(): RedisCredentialsTransfer
    {
        return (new RedisCredentialsTransfer())
            ->setScheme($this->get(CaptchaConstants::REDIS_SCHEME, static::DEFAULT_REDIS_SCHEME))
            ->setHost($this->get(CaptchaConstants::REDIS_HOST))
            ->setPort($this->get(CaptchaConstants::REDIS_PORT))
            ->setUsername($this->get(CaptchaConstants::REDIS_USER, false))
            ->setDatabase($this->get(CaptchaConstants::REDIS_DATABASE))
            ->setPassword($this->get(CaptchaConstants::REDIS_PASSWORD, false))
            ->setIsPersistent($this->get(CaptchaConstants::REDIS_PERSISTENT_CONNECTION, false));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConnectionOptions(): array
    {
        return $this->get(CaptchaConstants::REDIS_CONNECTION_OPTIONS, []);
    }
}
