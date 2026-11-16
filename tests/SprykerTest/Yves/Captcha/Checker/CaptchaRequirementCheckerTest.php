<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Yves\Captcha\Checker;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface;
use Spryker\Yves\Captcha\CaptchaConfig;
use Spryker\Yves\Captcha\CaptchaDependencyProvider;
use Spryker\Yves\Captcha\CaptchaFactory;
use Spryker\Yves\Captcha\Checker\AfterFailedAttemptsCaptchaRequirementChecker;
use SprykerTest\Yves\Captcha\CaptchaYvesTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Yves
 * @group Captcha
 * @group Checker
 * @group CaptchaRequirementCheckerTest
 * Add your own group annotations below this line
 */
class CaptchaRequirementCheckerTest extends Unit
{
    protected CaptchaYvesTester $tester;

    public function testAlwaysModeRequiresTheCaptchaWithoutAskingThePlugins(): void
    {
        // Arrange
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $captchaRequirementPluginMock->expects($this->never())->method('isCaptchaRequired');
        $captchaFactory = $this->createCaptchaFactory(SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, [$captchaRequirementPluginMock]);

        // Act
        $isCaptchaRequired = $captchaFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertTrue($isCaptchaRequired);
    }

    public function testAfterFailedAttemptsModeRequiresTheCaptchaWhenAnyPluginRequiresIt(): void
    {
        // Arrange
        $captchaFactory = $this->createCaptchaFactory(
            SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS,
            [$this->createCaptchaRequirementPlugin(false), $this->createCaptchaRequirementPlugin(true)],
        );

        // Act
        $isCaptchaRequired = $captchaFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertTrue($isCaptchaRequired);
    }

    public function testAfterFailedAttemptsModeDoesNotRequireTheCaptchaWhenNoPluginRequiresIt(): void
    {
        // Arrange
        $captchaFactory = $this->createCaptchaFactory(
            SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS,
            [$this->createCaptchaRequirementPlugin(false), $this->createCaptchaRequirementPlugin(false)],
        );

        // Act
        $isCaptchaRequired = $captchaFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertFalse($isCaptchaRequired);
    }

    public function testAfterFailedAttemptsModeWithoutPluginsRequiresTheCaptcha(): void
    {
        // Arrange
        $captchaFactory = $this->createCaptchaFactory(SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, []);

        // Act
        $isCaptchaRequired = $captchaFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertTrue($isCaptchaRequired);
    }

    public function testAfterFailedAttemptsModeWithoutPluginsLogsAnError(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $afterFailedAttemptsCaptchaRequirementChecker = new AfterFailedAttemptsCaptchaRequirementChecker([], $loggerMock);

        // Expect
        $loggerMock->expects($this->once())->method('error');

        // Act
        $afterFailedAttemptsCaptchaRequirementChecker->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());
    }

    public function testAfterFailedAttemptsModeRequiresTheCaptchaAndLogsAnErrorWhenAPluginFails(): void
    {
        // Arrange
        $failingCaptchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $failingCaptchaRequirementPluginMock->method('isCaptchaRequired')->willThrowException(new RuntimeException('Redis is unreachable.'));
        $loggerMock = $this->createMock(LoggerInterface::class);
        $afterFailedAttemptsCaptchaRequirementChecker = new AfterFailedAttemptsCaptchaRequirementChecker(
            [$failingCaptchaRequirementPluginMock, $this->createCaptchaRequirementPlugin(false)],
            $loggerMock,
        );

        // Expect
        $loggerMock->expects($this->once())->method('error');

        // Act
        $isCaptchaRequired = $afterFailedAttemptsCaptchaRequirementChecker->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertTrue($isCaptchaRequired);
    }

    public function testUnknownModeRequiresTheCaptchaLikeTheAlwaysMode(): void
    {
        // Arrange
        $captchaFactory = $this->createCaptchaFactory('unknown', [$this->createCaptchaRequirementPlugin(false)]);

        // Act
        $isCaptchaRequired = $captchaFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertTrue($isCaptchaRequired);
    }

    /**
     * @param list<\Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface> $captchaRequirementPlugins
     */
    protected function createCaptchaFactory(string $captchaMode, array $captchaRequirementPlugins): CaptchaFactory
    {
        $this->tester->setDependency(CaptchaDependencyProvider::PLUGINS_CAPTCHA_REQUIREMENT, $captchaRequirementPlugins);

        $captchaFactory = new CaptchaFactory();
        $captchaFactory->setConfig($this->createConfiguredMock(CaptchaConfig::class, ['getCaptchaMode' => $captchaMode]));

        return $captchaFactory;
    }

    protected function createCaptchaRequirementPlugin(bool $isCaptchaRequired): CaptchaRequirementPluginInterface
    {
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $captchaRequirementPluginMock->method('isCaptchaRequired')->willReturn($isCaptchaRequired);

        return $captchaRequirementPluginMock;
    }

    protected function createCaptchaRequirementRequestTransfer(): CaptchaRequirementRequestTransfer
    {
        return (new CaptchaRequirementRequestTransfer())
            ->setContext(SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN)
            ->setIp('10.0.0.1')
            ->setLoginIdentifier('customer@spryker.com');
    }
}
