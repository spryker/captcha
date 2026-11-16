<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Client\Captcha\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Spryker\Client\Captcha\CaptchaDependencyProvider;
use Spryker\Client\Redis\RedisClientInterface;
use Spryker\Shared\Captcha\CaptchaConstants;
use SprykerTest\Client\Testify\Helper\ConfigHelper as ClientConfigHelper;
use SprykerTest\Shared\Testify\Helper\ConfigHelper;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

/**
 * Gives the CAPTCHA replay store somewhere to keep spent challenges without a Redis server.
 *
 * Binds {@see \SprykerTest\Client\Captcha\Helper\InMemoryCaptchaRedisClient}, which keeps the `SET NX EX` semantics the
 * replay protection relies on. The binding is not scoped to the Captcha client factory: a Client suite builds the
 * factory container through `DependencyProviderHelper::getModuleContainer()`, which applies only unscoped overrides,
 * and a factory-scoped override would hide every unscoped one, the provider plugin included.
 */
class CaptchaRedisHelper extends Module
{
    use DependencyHelperTrait;

    protected const string CONFIG_KEY_BIND_BEFORE_EACH_TEST = 'bindBeforeEachTest';

    /**
     * A Client suite enables the Client variant, a provider suite of another module may enable the Shared one.
     *
     * @var array<string>
     */
    protected const array CONFIG_HELPER_CLASS_NAMES = [
        ClientConfigHelper::class,
        ConfigHelper::class,
    ];

    protected const string PLACEHOLDER_REDIS_HOST = 'in-memory';

    protected const int PLACEHOLDER_REDIS_PORT = 6379;

    protected const int PLACEHOLDER_REDIS_DATABASE = 0;

    /**
     * @var array<string, mixed>
     */
    protected array $config = [
        self::CONFIG_KEY_BIND_BEFORE_EACH_TEST => true,
    ];

    /**
     * A fresh client per test, so one test's spent challenges cannot reject the next test's payloads.
     */
    public function _before(TestInterface $test): void // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    {
        if (!$this->config[static::CONFIG_KEY_BIND_BEFORE_EACH_TEST]) {
            return;
        }

        $this->haveInMemoryCaptchaRedisClient();
    }

    /**
     * Binds a fresh in-memory client and returns it, so a test can assert the stored keys and their expiry.
     * The connection settings are placeholders: the replay store builds its connection configuration from them, the
     * in-memory client never connects.
     */
    public function haveInMemoryCaptchaRedisClient(): InMemoryCaptchaRedisClient
    {
        $this->getConfigHelper()->setConfig(CaptchaConstants::REDIS_HOST, static::PLACEHOLDER_REDIS_HOST);
        $this->getConfigHelper()->setConfig(CaptchaConstants::REDIS_PORT, static::PLACEHOLDER_REDIS_PORT);
        $this->getConfigHelper()->setConfig(CaptchaConstants::REDIS_DATABASE, static::PLACEHOLDER_REDIS_DATABASE);

        $inMemoryCaptchaRedisClient = new InMemoryCaptchaRedisClient();
        $this->haveCaptchaRedisClient($inMemoryCaptchaRedisClient);

        return $inMemoryCaptchaRedisClient;
    }

    public function haveCaptchaRedisClient(RedisClientInterface $redisClient): void
    {
        $this->getDependencyHelper()->setDependency(CaptchaDependencyProvider::CLIENT_REDIS, $redisClient);
    }

    protected function getConfigHelper(): ConfigHelper
    {
        foreach (static::CONFIG_HELPER_CLASS_NAMES as $configHelperClassName) {
            if ($this->hasModule('\\' . $configHelperClassName)) {
                /** @var \SprykerTest\Shared\Testify\Helper\ConfigHelper $configHelper */
                $configHelper = $this->getModule('\\' . $configHelperClassName);

                return $configHelper;
            }
        }

        /** @var \SprykerTest\Shared\Testify\Helper\ConfigHelper $configHelper */
        $configHelper = $this->getModule('\\' . ConfigHelper::class);

        return $configHelper;
    }
}
