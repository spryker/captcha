<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha\Integration;

use Codeception\Stub;
use Codeception\Test\Unit;
use Spryker\Client\Captcha\CaptchaConfig;
use Spryker\Client\Captcha\ReplayStore\CaptchaReplayStore;
use Spryker\Client\Captcha\ReplayStore\CaptchaReplayStoreInterface;
use Spryker\Client\Redis\RedisClientInterface;
use SprykerTest\Client\Captcha\CaptchaClientTester;

/**
 * Proves the replay protection against a real Redis server on both adapters: the in-memory double cannot show how
 * each adapter reports an `NX` collision (phpredis `false`, predis `null`), and a wrong report would silently accept
 * every replayed payload.
 *
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group Integration
 * @group CaptchaReplayStoreRealRedisTest
 * Add your own group annotations below this line
 */
class CaptchaReplayStoreRealRedisTest extends Unit
{
    protected const string CONNECTION_KEY_PATTERN = 'CAPTCHA_REDIS_REAL_REDIS_TEST_%s';

    protected const string SPENT_KEY_PATTERN = 'captcha:spent:%s';

    protected const int TTL = 60;

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
    public function testAcceptsChallengeOnceAndRefusesReplay(bool $usePhpredis): void
    {
        // Arrange
        $redisClient = $this->createRedisClient($usePhpredis);
        $connectionKey = $this->buildConnectionKey($usePhpredis);
        $captchaReplayStore = $this->createCaptchaReplayStore($redisClient, $connectionKey);
        $replayKey = bin2hex(random_bytes(32));
        $spentKey = sprintf(static::SPENT_KEY_PATTERN, hash('sha256', $replayKey));

        // Act
        $isFirstAttemptMarkedAsSpent = $captchaReplayStore->markChallengeAsSpent($replayKey, static::TTL);
        $isReplayMarkedAsSpent = $captchaReplayStore->markChallengeAsSpent($replayKey, static::TTL);

        // Assert
        $this->assertTrue($isFirstAttemptMarkedAsSpent);
        $this->assertFalse($isReplayMarkedAsSpent);
        $this->assertSame('1', $redisClient->get($connectionKey, $spentKey));

        $redisClient->del($connectionKey, [$spentKey]);
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

    protected function createCaptchaReplayStore(RedisClientInterface $redisClient, string $connectionKey): CaptchaReplayStoreInterface
    {
        /** @var \Spryker\Client\Captcha\CaptchaConfig $captchaConfig */
        $captchaConfig = Stub::make(CaptchaConfig::class, ['getRedisConnectionKey' => $connectionKey]);

        return new CaptchaReplayStore($redisClient, $captchaConfig);
    }

    protected function buildConnectionKey(bool $usePhpredis): string
    {
        return sprintf(static::CONNECTION_KEY_PATTERN, $usePhpredis ? 'PHPREDIS' : 'PREDIS');
    }
}
