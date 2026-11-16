<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Client\Redis\RedisClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use TypeError;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group VerifyCaptchaTest
 * Add your own group annotations below this line
 */
class VerifyCaptchaTest extends Unit
{
    protected const string CONNECTION_KEY = 'CAPTCHA_REDIS';

    protected const string SPENT_KEY_PATTERN = 'captcha:spent:%s';

    protected CaptchaClientTester $tester;

    public function testDelegatesPayloadContextAndIpUnchangedToProvider(): void
    {
        // Arrange
        $providerResponseTransfer = (new CaptchaVerificationResponseTransfer())->setIsValid(true);
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->expects($this->once())
            ->method('verify')
            ->with($this->callback(function (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool {
                return $captchaVerificationRequestTransfer->getPayload() === CaptchaClientTester::PAYLOAD
                    && $captchaVerificationRequestTransfer->getContext() === CaptchaClientTester::CONTEXT
                    && $captchaVerificationRequestTransfer->getIp() === CaptchaClientTester::IP;
            }))
            ->willReturn($providerResponseTransfer);
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getCaptchaClient()->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD),
        );

        // Assert
        $this->assertSame($providerResponseTransfer, $captchaVerificationResponseTransfer);
    }

    public function testReturnsProviderRejectionUnchanged(): void
    {
        // Arrange
        $providerResponseTransfer = (new CaptchaVerificationResponseTransfer())
            ->setIsValid(false)
            ->setIsExpired(true)
            ->addMessage((new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED));
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('verify')->willReturn($providerResponseTransfer);
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getCaptchaClient()->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD),
        );

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertTrue($captchaVerificationResponseTransfer->getIsExpired());
    }

    /**
     * @dataProvider provideEmptyPayloads
     */
    public function testReturnsRequiredWithoutCallingProviderWhenPayloadIsEmpty(?string $payload): void
    {
        // Arrange
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->expects($this->never())->method('verify');
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getCaptchaClient()->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest($payload),
        );

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame(CaptchaConfig::ERROR_CODE_REQUIRED, $captchaVerificationResponseTransfer->getErrorCode());
        $this->assertSame(
            CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_REQUIRED,
            $captchaVerificationResponseTransfer->getMessages()->offsetGet(0)->getValue(),
        );
    }

    /**
     * @return array<string, array<string|null>>
     */
    public function provideEmptyPayloads(): array
    {
        return [
            'null payload' => [null],
            'empty payload' => [''],
        ];
    }

    public function testReturnsUnavailableWithoutCallingProviderWhenDisabled(): void
    {
        // Arrange
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->expects($this->never())->method('verify');
        $this->tester->haveCaptchaEnabled(false);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getCaptchaClient()->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD),
        );

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame(
            CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE,
            $captchaVerificationResponseTransfer->getMessages()->offsetGet(0)->getValue(),
        );
    }

    public function testReturnsUnavailableAndLogsErrorWhenEnabledWithoutProvider(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(true);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $this->tester->haveCaptchaLogger($loggerMock);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getCaptchaClient()->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD),
        );

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertTrue($captchaVerificationResponseTransfer->getIsProviderUnavailable());
        $this->assertSame(CaptchaConfig::ERROR_CODE_UNAVAILABLE, $captchaVerificationResponseTransfer->getErrorCode());
        $this->assertSame(
            CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE,
            $captchaVerificationResponseTransfer->getMessages()->offsetGet(0)->getValue(),
        );
    }

    public function testAcceptsPayloadWithReplayKeyOnlyOnce(): void
    {
        // Arrange
        $this->haveProviderReturning(true, CaptchaClientTester::REPLAY_KEY);
        $captchaClient = $this->tester->getCaptchaClient();
        $captchaClient->verifyCaptcha($this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD));

        // Act
        $captchaVerificationResponseTransfer = $captchaClient->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD),
        );

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame(CaptchaConfig::ERROR_CODE_REPLAYED, $captchaVerificationResponseTransfer->getErrorCode());
        $this->assertSame(
            CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID,
            $captchaVerificationResponseTransfer->getMessages()->offsetGet(0)->getValue(),
        );
    }

    public function testKeepsReplayKeySpentUntilTheExpiryReportedByProvider(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveProviderReturning(true, CaptchaClientTester::REPLAY_KEY);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getCaptchaClient()->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD),
        );

        // Assert
        $expectedKey = sprintf(static::SPENT_KEY_PATTERN, hash('sha256', CaptchaClientTester::REPLAY_KEY));
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame([$expectedKey], $inMemoryCaptchaRedisClient->getStoredKeys(static::CONNECTION_KEY));
        $this->assertEqualsWithDelta(time() + CaptchaClientTester::REPLAY_TTL, $inMemoryCaptchaRedisClient->getExpiresAt(static::CONNECTION_KEY, $expectedKey), 1);
    }

    /**
     * Vendors such as Turnstile or reCAPTCHA reject a reused token themselves and return no replay key.
     */
    public function testAcceptsPayloadWithoutReplayKeyAgainWithoutStoringIt(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveProviderReturning(true, null);
        $captchaClient = $this->tester->getCaptchaClient();
        $captchaClient->verifyCaptcha($this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD));

        // Act
        $captchaVerificationResponseTransfer = $captchaClient->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD),
        );

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame([], $inMemoryCaptchaRedisClient->getStoredKeys(static::CONNECTION_KEY));
    }

    public function testDoesNotSpendPayloadRejectedByProvider(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $this->haveProviderReturning(false, CaptchaClientTester::REPLAY_KEY);

        // Act
        $this->tester->getCaptchaClient()->verifyCaptcha($this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD));

        // Assert
        $this->assertSame([], $inMemoryCaptchaRedisClient->getStoredKeys(static::CONNECTION_KEY));
    }

    public function testReturnsUnavailableAndLogsErrorWhenReplayStoreFails(): void
    {
        // Arrange
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->method('set')->willThrowException(new RuntimeException('Connection refused'));
        $this->tester->haveCaptchaRedisClient($redisClientMock);
        $this->haveProviderReturning(true, CaptchaClientTester::REPLAY_KEY);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $this->tester->haveCaptchaLogger($loggerMock);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getCaptchaClient()->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD),
        );

        // Assert
        $this->assertUnavailable($captchaVerificationResponseTransfer);
    }

    public function testReturnsUnavailableAndLogsErrorWhenProviderThrows(): void
    {
        // Arrange
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('verify')->willThrowException(new RuntimeException('siteverify timed out'));
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $this->tester->haveCaptchaLogger($loggerMock);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getCaptchaClient()->verifyCaptcha(
            $this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD),
        );

        // Assert
        $this->assertUnavailable($captchaVerificationResponseTransfer);
    }

    public function testLetsProviderErrorSurface(): void
    {
        // Arrange
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('verify')->willThrowException(new TypeError());
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        // Assert
        $this->expectException(TypeError::class);

        // Act
        $this->tester->getCaptchaClient()->verifyCaptcha($this->tester->createCaptchaVerificationRequest(CaptchaClientTester::PAYLOAD));
    }

    protected function assertUnavailable(CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer): void
    {
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertTrue($captchaVerificationResponseTransfer->getIsProviderUnavailable());
        $this->assertSame(CaptchaConfig::ERROR_CODE_UNAVAILABLE, $captchaVerificationResponseTransfer->getErrorCode());
        $this->assertSame(
            CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE,
            $captchaVerificationResponseTransfer->getMessages()->offsetGet(0)->getValue(),
        );
    }

    protected function haveProviderReturning(bool $isValid, ?string $replayKey): void
    {
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('verify')->willReturnCallback(function () use ($isValid, $replayKey): CaptchaVerificationResponseTransfer {
            return (new CaptchaVerificationResponseTransfer())
                ->setIsValid($isValid)
                ->setIsExpired(false)
                ->setReplayKey($replayKey)
                ->setReplayExpiresAt(time() + CaptchaClientTester::REPLAY_TTL);
        });
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);
    }
}
