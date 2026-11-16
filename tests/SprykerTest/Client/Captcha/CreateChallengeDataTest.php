<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaChallengeUrlProviderPluginInterface;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Client\Redis\RedisClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use Spryker\Shared\Kernel\Transfer\Exception\RequiredTransferPropertyException;
use SprykerTest\Client\Captcha\Helper\InMemoryCaptchaRedisClient;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group CreateChallengeDataTest
 * Add your own group annotations below this line
 */
class CreateChallengeDataTest extends Unit
{
    protected const string CHALLENGE_DATA = '{"parameters":{"algorithm":"ARGON2ID"},"signature":"signed"}';

    protected const string OTHER_IP = '192.0.2.20';

    protected const string THIRD_IP = '192.0.2.30';

    protected const string FOURTH_IP = '192.0.2.40';

    protected const string IPV6_NETWORK_FIRST_ADDRESS = '2001:db8:1:2::1';

    protected const string IPV6_NETWORK_SECOND_ADDRESS = '2001:db8:1:2:ffff:ffff:ffff:ffff';

    protected const string IPV6_NETWORK_THIRD_ADDRESS = '2001:db8:1:2:aaaa::3';

    protected const string IPV6_OTHER_NETWORK_ADDRESS = '2001:db8:1:3::1';

    protected const int WINDOW_SECONDS = 90;

    protected const string COUNTER_VALUE_WITHOUT_EXPIRY = '1';

    protected CaptchaClientTester $tester;

    public function testReturnsTheChallengeDataOfTheProvider(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();

        // Act
        $captchaChallengeDataResponseTransfer = $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $this->assertTrue($captchaChallengeDataResponseTransfer->getIsSuccessful());
        $this->assertFalse((bool)$captchaChallengeDataResponseTransfer->getIsRateLimited());
        $this->assertSame(static::CHALLENGE_DATA, $captchaChallengeDataResponseTransfer->getChallengeData());
    }

    public function testCreatesTheChallengeForTheRequestedContext(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $captchaProviderPluginMock = $this->haveChallengeUrlProvider();

        // Expect
        $captchaProviderPluginMock->expects($this->once())
            ->method('createChallengeData')
            ->with($this->callback(fn (CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): bool => $captchaChallengeRequestTransfer->getContext() === CaptchaClientTester::CONTEXT));

        // Act
        $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());
    }

    public function testRejectsTheRequestsOfAnIpAboveItsLimitAndServesOtherIps(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(2, 100);
        $captchaClient = $this->tester->getCaptchaClient();

        // Act
        $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest());
        $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest());
        $limitedCaptchaChallengeDataResponseTransfer = $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest());
        $otherIpCaptchaChallengeDataResponseTransfer = $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest(static::OTHER_IP));

        // Assert
        $this->assertRateLimited($limitedCaptchaChallengeDataResponseTransfer);
        $this->assertTrue($otherIpCaptchaChallengeDataResponseTransfer->getIsSuccessful());
    }

    /**
     * A context-wide limit would let a few hundred anonymous requests from a handful of IPs lock every visitor out of the
     * login form, so the requests of a context from all IPs only raise a warning.
     */
    public function testServesEveryIpAndLogsOneWarningWhenTheRequestsOfAContextPassItsWarningThreshold(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(100, 2);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $this->tester->haveCaptchaLogger($loggerMock);

        // Expect
        $loggerMock->expects($this->once())->method('warning');

        // Act
        $captchaChallengeDataResponseTransfers = $this->createChallengeDataForIps([static::OTHER_IP, static::THIRD_IP, static::FOURTH_IP, CaptchaClientTester::IP]);

        // Assert
        foreach ($captchaChallengeDataResponseTransfers as $captchaChallengeDataResponseTransfer) {
            $this->assertTrue($captchaChallengeDataResponseTransfer->getIsSuccessful());
        }
    }

    public function testDoesNotCountTheRequestsOfAContextWhenItsWarningThresholdIsZero(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(100, 0);

        // Act
        $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $this->assertCount(1, $inMemoryCaptchaRedisClient->getStoredKeys($this->tester->getRedisConnectionKey()));
    }

    /**
     * An IPv6 subscriber usually gets a whole /64 network, so a limit per address would give one client 2^64 budgets.
     */
    public function testCountsTheAddressesOfOneIpv6NetworkTogether(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(2, 100);
        $this->createChallengeDataForIps([static::IPV6_NETWORK_FIRST_ADDRESS, static::IPV6_NETWORK_SECOND_ADDRESS]);

        // Act
        [$limitedCaptchaChallengeDataResponseTransfer, $otherNetworkCaptchaChallengeDataResponseTransfer] = $this->createChallengeDataForIps([
            static::IPV6_NETWORK_THIRD_ADDRESS,
            static::IPV6_OTHER_NETWORK_ADDRESS,
        ]);

        // Assert
        $this->assertRateLimited($limitedCaptchaChallengeDataResponseTransfer);
        $this->assertTrue($otherNetworkCaptchaChallengeDataResponseTransfer->getIsSuccessful());
    }

    public function testCountsEachContextOnItsOwn(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(1, 100);
        $captchaClient = $this->tester->getCaptchaClient();
        $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Act
        $captchaChallengeDataResponseTransfer = $captchaClient->createChallengeData(
            $this->tester->createCaptchaChallengeDataRequest()->setContext(CaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN),
        );

        // Assert
        $this->assertTrue($captchaChallengeDataResponseTransfer->getIsSuccessful());
    }

    public function testDoesNotCreateAChallengeForARateLimitedRequest(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $captchaProviderPluginMock = $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(1, 100);
        $captchaClient = $this->tester->getCaptchaClient();

        // Expect
        $captchaProviderPluginMock->expects($this->once())->method('createChallengeData');

        // Act
        $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest());
        $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest());
    }

    /**
     * @dataProvider provideRateLimitWindowsWithTheirRetryAfter
     */
    public function testReturnsTheRateLimitWindowAsTheRetryAfterOfARateLimitedRequest(int $windowSeconds, int $expectedRetryAfterSeconds): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(1, 100, $windowSeconds);
        $captchaClient = $this->tester->getCaptchaClient();
        $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Act
        $captchaChallengeDataResponseTransfer = $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $this->assertRateLimited($captchaChallengeDataResponseTransfer);
        $this->assertSame($expectedRetryAfterSeconds, $captchaChallengeDataResponseTransfer->getRetryAfterSeconds());
    }

    /**
     * @return array<string, list<int>>
     */
    public static function provideRateLimitWindowsWithTheirRetryAfter(): array
    {
        return [
            'configured window' => [static::WINDOW_SECONDS, static::WINDOW_SECONDS],
            'window below one second' => [0, 1],
        ];
    }

    public function testReturnsNoRetryAfterForAServedRequest(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(1, 100, static::WINDOW_SECONDS);

        // Act
        $captchaChallengeDataResponseTransfer = $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $this->assertTrue($captchaChallengeDataResponseTransfer->getIsSuccessful());
        $this->assertNull($captchaChallengeDataResponseTransfer->getRetryAfterSeconds());
    }

    public function testExpiresTheRequestCountersAfterTheRateLimitWindow(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(5, 5, static::WINDOW_SECONDS);

        // Act
        $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $storedKeys = $inMemoryCaptchaRedisClient->getStoredKeys($this->tester->getRedisConnectionKey());
        $this->assertCount(2, $storedKeys);

        foreach ($storedKeys as $storedKey) {
            $this->assertSame(static::WINDOW_SECONDS, $inMemoryCaptchaRedisClient->ttl($this->tester->getRedisConnectionKey(), $storedKey));
        }
    }

    /**
     * A counter left without an expiry, for example because it expired between two Redis calls or the process stopped
     * before its expiry was set, would never reset and would reject its IP for good.
     */
    public function testRestoresTheExpiryOfARequestCounterWithoutOne(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();
        $this->tester->haveChallengeRateLimits(5, 5, static::WINDOW_SECONDS);
        $this->haveRequestCountersWithoutExpiry($inMemoryCaptchaRedisClient);

        // Act
        $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $storedKeys = $inMemoryCaptchaRedisClient->getStoredKeys($this->tester->getRedisConnectionKey());
        $this->assertCount(2, $storedKeys);

        foreach ($storedKeys as $storedKey) {
            $this->assertSame(static::WINDOW_SECONDS, $inMemoryCaptchaRedisClient->ttl($this->tester->getRedisConnectionKey(), $storedKey));
        }
    }

    public function testKeepsTheIpOutOfTheRedisKeys(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();

        // Act
        $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        foreach ($inMemoryCaptchaRedisClient->getStoredKeys($this->tester->getRedisConnectionKey()) as $storedKey) {
            $this->assertStringNotContainsString(CaptchaClientTester::IP, $storedKey);
        }
    }

    public function testReturnsUnavailableAndLogsAnErrorWhenRedisFails(): void
    {
        // Arrange
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->method('incr')->willThrowException(new RuntimeException('Connection refused.'));
        $this->tester->haveCaptchaRedisClient($redisClientMock);
        $captchaProviderPluginMock = $this->haveChallengeUrlProvider();
        $loggerMock = $this->createMock(LoggerInterface::class);
        $this->tester->haveCaptchaLogger($loggerMock);

        // Expect
        $captchaProviderPluginMock->expects($this->never())->method('createChallengeData');
        $loggerMock->expects($this->once())->method('error');

        // Act
        $captchaChallengeDataResponseTransfer = $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $this->assertUnavailable($captchaChallengeDataResponseTransfer);
    }

    public function testReturnsUnavailableWithoutCountingWhenDisabled(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $captchaProviderPluginMock = $this->haveChallengeUrlProvider();
        $this->tester->haveCaptchaEnabled(false);

        // Expect
        $captchaProviderPluginMock->expects($this->never())->method('createChallengeData');

        // Act
        $captchaChallengeDataResponseTransfer = $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $this->assertUnavailable($captchaChallengeDataResponseTransfer);
        $this->assertSame([], $inMemoryCaptchaRedisClient->getStoredKeys($this->tester->getRedisConnectionKey()));
    }

    public function testReturnsUnavailableAndLogsAnErrorWhenTheProviderCannotServeAChallengeUrl(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($this->createMock(CaptchaProviderPluginInterface::class));
        $loggerMock = $this->createMock(LoggerInterface::class);
        $this->tester->haveCaptchaLogger($loggerMock);

        // Expect
        $loggerMock->expects($this->once())->method('error');

        // Act
        $captchaChallengeDataResponseTransfer = $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $this->assertUnavailable($captchaChallengeDataResponseTransfer);
    }

    public function testReturnsUnavailableAndLogsAnErrorWhenTheProviderThrows(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $captchaProviderPluginMock = $this->haveChallengeUrlProvider();
        $captchaProviderPluginMock->method('createChallengeData')->willThrowException(new RuntimeException('Zed is unreachable.'));
        $loggerMock = $this->createMock(LoggerInterface::class);
        $this->tester->haveCaptchaLogger($loggerMock);

        // Expect
        $loggerMock->expects($this->once())->method('error');

        // Act
        $captchaChallengeDataResponseTransfer = $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        // Assert
        $this->assertUnavailable($captchaChallengeDataResponseTransfer);
    }

    public function testRequiresTheContext(): void
    {
        // Arrange
        $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveChallengeUrlProvider();

        // Expect
        $this->expectException(RequiredTransferPropertyException::class);

        // Act
        $this->tester->getCaptchaClient()->createChallengeData((new CaptchaChallengeDataRequestTransfer())->setIp(CaptchaClientTester::IP));
    }

    /**
     * @param array<string> $ips
     *
     * @return array<\Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer>
     */
    protected function createChallengeDataForIps(array $ips): array
    {
        $captchaClient = $this->tester->getCaptchaClient();

        return array_map(
            fn (string $ip): CaptchaChallengeDataResponseTransfer => $captchaClient->createChallengeData($this->tester->createCaptchaChallengeDataRequest($ip)),
            $ips,
        );
    }

    /**
     * A plain `SET` drops the expiry of a key, as in Redis.
     */
    protected function haveRequestCountersWithoutExpiry(InMemoryCaptchaRedisClient $inMemoryCaptchaRedisClient): void
    {
        $this->tester->getCaptchaClient()->createChallengeData($this->tester->createCaptchaChallengeDataRequest());

        foreach ($inMemoryCaptchaRedisClient->getStoredKeys($this->tester->getRedisConnectionKey()) as $storedKey) {
            $inMemoryCaptchaRedisClient->set($this->tester->getRedisConnectionKey(), $storedKey, static::COUNTER_VALUE_WITHOUT_EXPIRY);
        }
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaChallengeUrlProviderPluginInterface
     */
    protected function haveChallengeUrlProvider(): CaptchaChallengeUrlProviderPluginInterface
    {
        $captchaProviderPluginMock = $this->createMockForIntersectionOfInterfaces([
            CaptchaProviderPluginInterface::class,
            CaptchaChallengeUrlProviderPluginInterface::class,
        ]);
        $captchaProviderPluginMock->method('createChallengeData')->willReturn(
            (new CaptchaChallengeDataResponseTransfer())
                ->setIsSuccessful(true)
                ->setChallengeData(static::CHALLENGE_DATA),
        );
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        return $captchaProviderPluginMock;
    }

    protected function assertRateLimited(CaptchaChallengeDataResponseTransfer $captchaChallengeDataResponseTransfer): void
    {
        $this->assertFalse($captchaChallengeDataResponseTransfer->getIsSuccessful());
        $this->assertTrue($captchaChallengeDataResponseTransfer->getIsRateLimited());
        $this->assertNull($captchaChallengeDataResponseTransfer->getChallengeData());
    }

    protected function assertUnavailable(CaptchaChallengeDataResponseTransfer $captchaChallengeDataResponseTransfer): void
    {
        $this->assertFalse($captchaChallengeDataResponseTransfer->getIsSuccessful());
        $this->assertFalse((bool)$captchaChallengeDataResponseTransfer->getIsRateLimited());
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE, $captchaChallengeDataResponseTransfer->getMessages()[0]->getValue());
    }
}
