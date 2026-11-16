<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha\ReplayStore;

use Codeception\Test\Unit;
use Spryker\Client\Captcha\CaptchaConfig;
use Spryker\Client\Captcha\Exception\CaptchaReplayStoreException;
use Spryker\Client\Captcha\ReplayStore\CaptchaReplayStore;
use Spryker\Client\Redis\RedisClientInterface;
use SprykerTest\Client\Captcha\CaptchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group ReplayStore
 * @group CaptchaReplayStoreTest
 * Add your own group annotations below this line
 */
class CaptchaReplayStoreTest extends Unit
{
    protected const string CONNECTION_KEY = 'CAPTCHA_REDIS';

    protected const string SPENT_KEY_PATTERN = 'captcha:spent:%s';

    protected const int TTL = 600;

    protected CaptchaClientTester $tester;

    public function testMarksUnspentChallengeAsSpentUnderHashedReplayKeyWithTtl(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $expectedKey = sprintf(static::SPENT_KEY_PATTERN, hash('sha256', CaptchaClientTester::REPLAY_KEY));

        // Act
        $isMarkedAsSpent = $this->tester->createCaptchaFactory()
            ->createCaptchaReplayStore()
            ->markChallengeAsSpent(CaptchaClientTester::REPLAY_KEY, static::TTL);

        // Assert
        $this->assertTrue($isMarkedAsSpent);
        $this->assertSame([$expectedKey], $inMemoryCaptchaRedisClient->getStoredKeys(static::CONNECTION_KEY));
        $this->assertEqualsWithDelta(time() + static::TTL, $inMemoryCaptchaRedisClient->getExpiresAt(static::CONNECTION_KEY, $expectedKey), 1);
    }

    public function testRefusesChallengeThatIsAlreadySpent(): void
    {
        // Arrange
        $captchaReplayStore = $this->tester->createCaptchaFactory()->createCaptchaReplayStore();
        $captchaReplayStore->markChallengeAsSpent(CaptchaClientTester::REPLAY_KEY, static::TTL);

        // Act
        $isMarkedAsSpent = $captchaReplayStore->markChallengeAsSpent(CaptchaClientTester::REPLAY_KEY, static::TTL);

        // Assert
        $this->assertFalse($isMarkedAsSpent);
    }

    /**
     * phpredis answers a refused write (read-only replica, out of memory) with `false` instead of throwing, the same
     * value `NX` returns for a spent challenge. Reporting it as a replay would reject every challenged login silently.
     */
    public function testFailsLoudlyWhenTheChallengeCannotBeRecorded(): void
    {
        // Arrange
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->method('set')->willReturn(false);
        $redisClientMock->method('get')->willReturn(null);

        // Assert
        $this->expectException(CaptchaReplayStoreException::class);

        // Act
        (new CaptchaReplayStore($redisClientMock, new CaptchaConfig()))->markChallengeAsSpent(CaptchaClientTester::REPLAY_KEY, static::TTL);
    }

    public function testOpensItsOwnRedisConnection(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();

        // Act
        $this->tester->createCaptchaFactory()
            ->createCaptchaReplayStore()
            ->markChallengeAsSpent(CaptchaClientTester::REPLAY_KEY, static::TTL);

        // Assert
        $this->assertNotNull($inMemoryCaptchaRedisClient->findConnectionConfiguration(static::CONNECTION_KEY));
    }
}
