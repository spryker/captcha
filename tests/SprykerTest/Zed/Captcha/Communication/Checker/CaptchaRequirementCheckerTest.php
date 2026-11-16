<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Captcha\Communication\Checker;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface;
use Spryker\Zed\Captcha\CaptchaConfig;
use Spryker\Zed\Captcha\CaptchaDependencyProvider;
use Spryker\Zed\Captcha\Communication\CaptchaCommunicationFactory;
use Spryker\Zed\Captcha\Communication\Checker\AfterFailedAttemptsCaptchaRequirementChecker;
use SprykerTest\Zed\Captcha\CaptchaCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Captcha
 * @group Communication
 * @group Checker
 * @group CaptchaRequirementCheckerTest
 * Add your own group annotations below this line
 */
class CaptchaRequirementCheckerTest extends Unit
{
    protected CaptchaCommunicationTester $tester;

    public function testAlwaysModeRequiresTheCaptchaWithoutAskingThePlugins(): void
    {
        // Arrange
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $captchaRequirementPluginMock->expects($this->never())->method('isCaptchaRequired');
        $captchaCommunicationFactory = $this->createCaptchaCommunicationFactory(SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, [$captchaRequirementPluginMock]);

        // Act
        $isCaptchaRequired = $captchaCommunicationFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertTrue($isCaptchaRequired);
    }

    public function testAfterFailedAttemptsModeRequiresTheCaptchaWhenAnyPluginRequiresIt(): void
    {
        // Arrange
        $captchaCommunicationFactory = $this->createCaptchaCommunicationFactory(
            SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS,
            [$this->createCaptchaRequirementPlugin(false), $this->createCaptchaRequirementPlugin(true)],
        );

        // Act
        $isCaptchaRequired = $captchaCommunicationFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertTrue($isCaptchaRequired);
    }

    public function testAfterFailedAttemptsModeDoesNotRequireTheCaptchaWhenNoPluginRequiresIt(): void
    {
        // Arrange
        $captchaCommunicationFactory = $this->createCaptchaCommunicationFactory(
            SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS,
            [$this->createCaptchaRequirementPlugin(false), $this->createCaptchaRequirementPlugin(false)],
        );

        // Act
        $isCaptchaRequired = $captchaCommunicationFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertFalse($isCaptchaRequired);
    }

    public function testAfterFailedAttemptsModeWithoutPluginsRequiresTheCaptcha(): void
    {
        // Arrange
        $captchaCommunicationFactory = $this->createCaptchaCommunicationFactory(SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, []);

        // Act
        $isCaptchaRequired = $captchaCommunicationFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

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
        $captchaCommunicationFactory = $this->createCaptchaCommunicationFactory('unknown', [$this->createCaptchaRequirementPlugin(false)]);

        // Act
        $isCaptchaRequired = $captchaCommunicationFactory->createCaptchaRequirementChecker()->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertTrue($isCaptchaRequired);
    }

    /**
     * @param list<\Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface> $captchaRequirementPlugins
     */
    protected function createCaptchaCommunicationFactory(string $captchaMode, array $captchaRequirementPlugins): CaptchaCommunicationFactory
    {
        $this->tester->setDependency(CaptchaDependencyProvider::PLUGINS_CAPTCHA_REQUIREMENT, $captchaRequirementPlugins);

        $captchaCommunicationFactory = new CaptchaCommunicationFactory();
        $captchaCommunicationFactory->setConfig($this->createConfiguredMock(CaptchaConfig::class, ['getCaptchaMode' => $captchaMode]));

        return $captchaCommunicationFactory;
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
            ->setContext(SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN)
            ->setIp('10.0.0.1')
            ->setLoginIdentifier('admin@spryker.com');
    }
}
