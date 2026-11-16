<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha;

use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaWidgetTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Client\Captcha\CaptchaDependencyProvider;
use Spryker\Client\Captcha\CaptchaFactory;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Client\Redis\RedisClient;
use Spryker\Client\Redis\RedisClientInterface;
use Spryker\Client\Redis\RedisConfig;
use Spryker\Client\Redis\RedisFactory;
use Spryker\Shared\Captcha\CaptchaConstants;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class CaptchaClientTester extends Actor
{
    use _generated\CaptchaClientTesterActions;

    public const string CONTEXT = 'customer';

    public const string PAYLOAD = 'solved-payload';

    public const string IP = '192.0.2.10';

    public const string REPLAY_KEY = 'solved-challenge-signature';

    public const int REPLAY_TTL = 300;

    public const string WIDGET_TAG_NAME = 'test-widget';

    public const string WIDGET_ATTRIBUTE_CHALLENGE = 'challenge';

    public const string REAL_REDIS_SKIP_MESSAGE = 'Needs a reachable Redis server: set SPRYKER_KEY_VALUE_STORE_HOST (and SPRYKER_KEY_VALUE_STORE_PORT) to it, e.g. run the suite through "docker/sdk cli" or "docker/sdk testing".';

    public const string PHPREDIS_SKIP_MESSAGE = 'Needs the phpredis extension ("redis") to test the phpredis adapter.';

    protected const string ENV_REDIS_HOST = 'SPRYKER_KEY_VALUE_STORE_HOST';

    protected const string ENV_REDIS_PORT = 'SPRYKER_KEY_VALUE_STORE_PORT';

    protected const string ENV_REDIS_PASSWORD = 'SPRYKER_KEY_VALUE_PASSWORD';

    protected const string REAL_REDIS_SCHEME = 'tcp';

    protected const int DEFAULT_REDIS_PORT = 6379;

    protected const int REAL_REDIS_DATABASE = 7;

    protected const string PHPREDIS_EXTENSION = 'redis';

    public function haveCaptchaProviderPlugin(CaptchaProviderPluginInterface $captchaProviderPlugin): void
    {
        $this->setDependency(CaptchaDependencyProvider::PLUGIN_CAPTCHA_PROVIDER, $captchaProviderPlugin);
    }

    public function haveCaptchaLogger(LoggerInterface $logger): void
    {
        $this->mockFactoryMethod('getLogger', $logger);
    }

    public function getCaptchaClient(): CaptchaClientInterface
    {
        /** @var \Spryker\Client\Captcha\CaptchaClientInterface $captchaClient */
        $captchaClient = $this->getClient();

        return $captchaClient;
    }

    public function createCaptchaFactory(): CaptchaFactory
    {
        return new CaptchaFactory();
    }

    public function createCaptchaChallengeRequest(): CaptchaChallengeRequestTransfer
    {
        return (new CaptchaChallengeRequestTransfer())->setContext(static::CONTEXT);
    }

    public function createCaptchaVerificationRequest(?string $payload): CaptchaVerificationRequestTransfer
    {
        return (new CaptchaVerificationRequestTransfer())
            ->setPayload($payload)
            ->setContext(static::CONTEXT)
            ->setIp(static::IP);
    }

    public function createCaptchaChallengeDataRequest(string $ip = self::IP): CaptchaChallengeDataRequestTransfer
    {
        return (new CaptchaChallengeDataRequestTransfer())
            ->setContext(static::CONTEXT)
            ->setIp($ip);
    }

    public function haveChallengeRateLimits(int $limitPerIp, int $warningThresholdPerContext, int $windowSeconds = 60): void
    {
        $this->mockConfigMethod('getChallengeRateLimitPerIp', $limitPerIp);
        $this->mockConfigMethod('getChallengeRateWarningThresholdPerContext', $warningThresholdPerContext);
        $this->mockConfigMethod('getChallengeRateLimitWindowSeconds', $windowSeconds);
    }

    public function createCaptchaWidget(string $challenge): CaptchaWidgetTransfer
    {
        return (new CaptchaWidgetTransfer())
            ->setTagName(static::WIDGET_TAG_NAME)
            ->setNameAttribute('name')
            ->setAttributes([static::WIDGET_ATTRIBUTE_CHALLENGE => $challenge]);
    }

    public function getRedisConnectionKey(): string
    {
        /** @var \Spryker\Client\Captcha\CaptchaConfig $captchaConfig */
        $captchaConfig = $this->getModuleConfig();

        return $captchaConfig->getRedisConnectionKey();
    }

    public function isRealRedisReachable(): bool
    {
        $redisHost = (string)getenv(static::ENV_REDIS_HOST);

        if ($redisHost === '') {
            return false;
        }

        return filter_var($redisHost, FILTER_VALIDATE_IP) !== false || gethostbyname($redisHost) !== $redisHost;
    }

    public function isRedisAdapterAvailable(bool $usePhpredis): bool
    {
        return !$usePhpredis || extension_loaded(static::PHPREDIS_EXTENSION);
    }

    /**
     * Points the CAPTCHA Redis connection at the Redis server of the environment, on a database of its own.
     */
    public function haveRealRedisConnectionConfiguration(): void
    {
        $this->setConfig(CaptchaConstants::REDIS_SCHEME, static::REAL_REDIS_SCHEME);
        $this->setConfig(CaptchaConstants::REDIS_HOST, (string)getenv(static::ENV_REDIS_HOST));
        $this->setConfig(CaptchaConstants::REDIS_PORT, (int)(getenv(static::ENV_REDIS_PORT) ?: static::DEFAULT_REDIS_PORT));
        $this->setConfig(CaptchaConstants::REDIS_PASSWORD, getenv(static::ENV_REDIS_PASSWORD) ?: false);
        $this->setConfig(CaptchaConstants::REDIS_DATABASE, static::REAL_REDIS_DATABASE);
    }

    public function createRealRedisClient(bool $usePhpredis): RedisClientInterface
    {
        $redisFactory = new RedisFactory();
        $redisFactory->setConfig(Stub::make(RedisConfig::class, ['usePhpredis' => $usePhpredis]));

        $redisClient = new RedisClient();
        $redisClient->setFactory($redisFactory);

        return $redisClient;
    }
}
