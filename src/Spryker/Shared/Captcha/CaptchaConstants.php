<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Captcha;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface CaptchaConstants
{
    /**
     * Specification:
     * - Defines the scheme (tcp:// or redis://) of the Redis connection that stores spent CAPTCHA challenges and the
     *   counters of the challenge rate limit.
     *
     * @api
     */
    public const string REDIS_SCHEME = 'CAPTCHA:REDIS_SCHEME';

    /**
     * Specification:
     * - Defines the host of the Redis connection that stores spent CAPTCHA challenges and the counters of the challenge
     *   rate limit.
     *
     * @api
     */
    public const string REDIS_HOST = 'CAPTCHA:REDIS_HOST';

    /**
     * Specification:
     * - Defines the port of the Redis connection that stores spent CAPTCHA challenges and the counters of the challenge
     *   rate limit.
     *
     * @api
     */
    public const string REDIS_PORT = 'CAPTCHA:REDIS_PORT';

    /**
     * Specification:
     * - Defines the username for Redis ACL authentication of the connection that stores spent CAPTCHA challenges and
     *   the counters of the challenge rate limit.
     *
     * @api
     */
    public const string REDIS_USER = 'CAPTCHA:REDIS_USER';

    /**
     * Specification:
     * - Defines the password of the Redis connection that stores spent CAPTCHA challenges and the counters of the
     *   challenge rate limit.
     *
     * @api
     */
    public const string REDIS_PASSWORD = 'CAPTCHA:REDIS_PASSWORD';

    /**
     * Specification:
     * - Defines the Redis database that stores spent CAPTCHA challenges and the counters of the challenge rate limit.
     *
     * @api
     */
    public const string REDIS_DATABASE = 'CAPTCHA:REDIS_DATABASE';

    /**
     * Specification:
     * - Enables or disables a persistent Redis connection for spent CAPTCHA challenges and the counters of the
     *   challenge rate limit.
     *
     * @api
     */
    public const string REDIS_PERSISTENT_CONNECTION = 'CAPTCHA:REDIS_PERSISTENT_CONNECTION';

    /**
     * Specification:
     * - Defines an array of DSN strings for a multi-instance cluster or replication Redis setup that stores spent
     *   CAPTCHA challenges and the counters of the challenge rate limit.
     *
     * @api
     */
    public const string REDIS_DATA_SOURCE_NAMES = 'CAPTCHA:REDIS_DATA_SOURCE_NAMES';

    /**
     * Specification:
     * - Defines an array of Redis client options of the connection that stores spent CAPTCHA challenges and the
     *   counters of the challenge rate limit.
     *
     * @api
     */
    public const string REDIS_CONNECTION_OPTIONS = 'CAPTCHA:REDIS_CONNECTION_OPTIONS';
}
