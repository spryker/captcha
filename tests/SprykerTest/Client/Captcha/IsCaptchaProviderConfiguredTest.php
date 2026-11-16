<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha;

use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group IsCaptchaProviderConfiguredTest
 * Add your own group annotations below this line
 */
class IsCaptchaProviderConfiguredTest extends Unit
{
    protected CaptchaClientTester $tester;

    public function testReturnsTrueWhenTheProviderIsConfiguredWhileTheCaptchaIsOff(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(false);
        $this->tester->haveCaptchaProviderPlugin($this->createCaptchaProviderPlugin(true));

        // Act
        $isCaptchaProviderConfigured = $this->tester->getCaptchaClient()->isCaptchaProviderConfigured();

        // Assert
        $this->assertTrue($isCaptchaProviderConfigured);
    }

    public function testReturnsFalseWhenTheProviderIsNotConfigured(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(false);
        $this->tester->haveCaptchaProviderPlugin($this->createCaptchaProviderPlugin(false));

        // Act
        $isCaptchaProviderConfigured = $this->tester->getCaptchaClient()->isCaptchaProviderConfigured();

        // Assert
        $this->assertFalse($isCaptchaProviderConfigured);
    }

    public function testReturnsFalseAndLogsWarningWhenNoProviderIsWired(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(false);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning');
        $this->tester->haveCaptchaLogger($loggerMock);

        // Act
        $isCaptchaProviderConfigured = $this->tester->getCaptchaClient()->isCaptchaProviderConfigured();

        // Assert
        $this->assertFalse($isCaptchaProviderConfigured);
    }

    public function testReturnsFalseAndLogsErrorWhenTheProviderThrows(): void
    {
        // Arrange
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('isConfigured')->willThrowException(new RuntimeException('Settings unreadable'));
        $this->tester->haveCaptchaEnabled(false);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $this->tester->haveCaptchaLogger($loggerMock);

        // Act
        $isCaptchaProviderConfigured = $this->tester->getCaptchaClient()->isCaptchaProviderConfigured();

        // Assert
        $this->assertFalse($isCaptchaProviderConfigured);
    }

    protected function createCaptchaProviderPlugin(bool $isConfigured): CaptchaProviderPluginInterface
    {
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('isConfigured')->willReturn($isConfigured);

        return $captchaProviderPluginMock;
    }
}
