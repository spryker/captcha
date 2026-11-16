<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha\Integration;

use Codeception\Stub;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;
use Spryker\Client\Captcha\CaptchaConfig;
use Spryker\Client\Captcha\RateLimiter\CaptchaChallengeRateLimiter;
use Spryker\Client\Captcha\RateLimiter\CaptchaChallengeRateLimiterInterface;
use Spryker\Client\Redis\RedisClientInterface;
use SprykerTest\Client\Captcha\CaptchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group Integration
 * @group CaptchaChallengeRateLimiterRealRedisTest
 * Add your own group annotations below this line
 */
class CaptchaChallengeRateLimiterRealRedisTest extends Unit
{
    protected const string CONNECTION_KEY_PATTERN = 'CAPTCHA_REDIS_REAL_REDIS_TEST_%s';

    protected const string IP_COUNTER_KEY_PATTERN = 'captcha:challenge-rate:{%s}:ip:%s';

    protected const string CONTEXT_COUNTER_KEY_PATTERN = 'captcha:challenge-rate:{%s}:all';

    protected const int LIMIT_PER_IP = 2;

    protected const int WARNING_THRESHOLD_PER_CONTEXT = 100;

    protected const string COUNTER_VALUE_WITHOUT_EXPIRY = '1';

    protected const int WINDOW_SECONDS = 60;

    protected CaptchaClientTester $tester;

    protected function _before(): void
    {
        parent::_before();

        if (!$this->tester->isRealRedisReachable()) {
            $this->markTestSkipped(CaptchaClientTester::REAL_REDIS_SKIP_MESSAGE);
        }

        $this->tester->haveRealRedisConnectionConfiguration();
    }

    /**
     * @dataProvider provideRedisAdapters
     */
    public function testRateLimitsTheRequestAboveTheLimitOfTheIpAndExpiresTheCounters(bool $usePhpredis): void
    {
        // Arrange
        $redisClient = $this->createRedisClient($usePhpredis);
        $connectionKey = $this->buildConnectionKey($usePhpredis);
        $captchaChallengeRateLimiter = $this->createCaptchaChallengeRateLimiter($redisClient, $connectionKey);
        $context = sprintf('test_%s', bin2hex(random_bytes(8)));
        $counterKeys = [
            sprintf(static::IP_COUNTER_KEY_PATTERN, $context, hash('sha256', CaptchaClientTester::IP)),
            sprintf(static::CONTEXT_COUNTER_KEY_PATTERN, $context),
        ];

        // Act
        $rateLimitDecisions = [
            $captchaChallengeRateLimiter->isRateLimited($context, CaptchaClientTester::IP),
            $captchaChallengeRateLimiter->isRateLimited($context, CaptchaClientTester::IP),
            $captchaChallengeRateLimiter->isRateLimited($context, CaptchaClientTester::IP),
        ];

        // Assert
        $this->assertSame([false, false, true], $rateLimitDecisions);

        foreach ($counterKeys as $counterKey) {
            $timeToLive = $redisClient->ttl($connectionKey, $counterKey);
            $this->assertGreaterThan(0, $timeToLive);
            $this->assertLessThanOrEqual(static::WINDOW_SECONDS, $timeToLive);
        }

        $redisClient->del($connectionKey, $counterKeys);
    }

    /**
     * A plain `SET` drops the expiry, which leaves the counter as `INCR` leaves it when its key expired between two calls.
     *
     * @dataProvider provideRedisAdapters
     */
    public function testRestoresTheExpiryOfARequestCounterWithoutOne(bool $usePhpredis): void
    {
        // Arrange
        $redisClient = $this->createRedisClient($usePhpredis);
        $connectionKey = $this->buildConnectionKey($usePhpredis);
        $captchaChallengeRateLimiter = $this->createCaptchaChallengeRateLimiter($redisClient, $connectionKey);
        $context = sprintf('test_%s', bin2hex(random_bytes(8)));
        $ipCounterKey = sprintf(static::IP_COUNTER_KEY_PATTERN, $context, hash('sha256', CaptchaClientTester::IP));
        $captchaChallengeRateLimiter->isRateLimited($context, CaptchaClientTester::IP);
        $redisClient->set($connectionKey, $ipCounterKey, static::COUNTER_VALUE_WITHOUT_EXPIRY);

        // Act
        $captchaChallengeRateLimiter->isRateLimited($context, CaptchaClientTester::IP);

        // Assert
        $timeToLive = $redisClient->ttl($connectionKey, $ipCounterKey);
        $this->assertGreaterThan(0, $timeToLive);
        $this->assertLessThanOrEqual(static::WINDOW_SECONDS, $timeToLive);

        $redisClient->del($connectionKey, [$ipCounterKey, sprintf(static::CONTEXT_COUNTER_KEY_PATTERN, $context)]);
    }

    /**
     * @return array<string, array<bool>>
     */
    public function provideRedisAdapters(): array
    {
        return [
            'phpredis' => [true],
            'predis' => [false],
        ];
    }

    protected function createRedisClient(bool $usePhpredis): RedisClientInterface
    {
        if (!$this->tester->isRedisAdapterAvailable($usePhpredis)) {
            $this->markTestSkipped(CaptchaClientTester::PHPREDIS_SKIP_MESSAGE);
        }

        return $this->tester->createRealRedisClient($usePhpredis);
    }

    protected function createCaptchaChallengeRateLimiter(RedisClientInterface $redisClient, string $connectionKey): CaptchaChallengeRateLimiterInterface
    {
        /** @var \Spryker\Client\Captcha\CaptchaConfig $captchaConfig */
        $captchaConfig = Stub::make(CaptchaConfig::class, [
            'getRedisConnectionKey' => $connectionKey,
            'getChallengeRateLimitPerIp' => static::LIMIT_PER_IP,
            'getChallengeRateWarningThresholdPerContext' => static::WARNING_THRESHOLD_PER_CONTEXT,
            'getChallengeRateLimitWindowSeconds' => static::WINDOW_SECONDS,
        ]);

        return new CaptchaChallengeRateLimiter(
            $redisClient,
            $captchaConfig,
            $this->tester->createCaptchaFactory()->createIpNetworkResolver(),
            new NullLogger(),
        );
    }

    protected function buildConnectionKey(bool $usePhpredis): string
    {
        return sprintf(static::CONNECTION_KEY_PATTERN, $usePhpredis ? 'PHPREDIS' : 'PREDIS');
    }
}
