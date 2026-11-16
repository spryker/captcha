<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Shared\Kernel\Transfer\Exception\RequiredTransferPropertyException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group IsCaptchaEnabledTest
 * Add your own group annotations below this line
 */
class IsCaptchaEnabledTest extends Unit
{
    protected CaptchaClientTester $tester;

    public function testIsDisabledWhenTheBackOfficeSettingIsNotStoredAlthoughProviderIsWired(): void
    {
        // Arrange
        $this->tester->haveStoredCaptchaEnabledValue(null);
        $this->tester->haveCaptchaProviderPlugin($this->createMock(CaptchaProviderPluginInterface::class));

        // Act
        $isCaptchaEnabled = $this->tester->getCaptchaClient()->isCaptchaEnabled($this->tester->createCaptchaChallengeRequest());

        // Assert
        $this->assertFalse($isCaptchaEnabled);
    }

    public function testReturnsTrueWhenEnabled(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($this->createMock(CaptchaProviderPluginInterface::class));

        // Act
        $isCaptchaEnabled = $this->tester->getCaptchaClient()->isCaptchaEnabled($this->tester->createCaptchaChallengeRequest());

        // Assert
        $this->assertTrue($isCaptchaEnabled);
    }

    public function testReturnsTrueWhenEnabledWithoutProvider(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(true);

        // Act
        $isCaptchaEnabled = $this->tester->getCaptchaClient()->isCaptchaEnabled($this->tester->createCaptchaChallengeRequest());

        // Assert
        $this->assertTrue($isCaptchaEnabled);
    }

    public function testReturnsFalseWhenDisabled(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(false);
        $this->tester->haveCaptchaProviderPlugin($this->createMock(CaptchaProviderPluginInterface::class));

        // Act
        $isCaptchaEnabled = $this->tester->getCaptchaClient()->isCaptchaEnabled($this->tester->createCaptchaChallengeRequest());

        // Assert
        $this->assertFalse($isCaptchaEnabled);
    }

    public function testReturnsFalseWhenTheStoredValueIsTheStringFalse(): void
    {
        // Arrange
        $this->tester->haveStoredCaptchaEnabledValue('false');
        $this->tester->haveCaptchaProviderPlugin($this->createMock(CaptchaProviderPluginInterface::class));

        // Act
        $isCaptchaEnabled = $this->tester->getCaptchaClient()->isCaptchaEnabled($this->tester->createCaptchaChallengeRequest());

        // Assert
        $this->assertFalse($isCaptchaEnabled);
    }

    public function testRequiresContext(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(true);

        // Assert
        $this->expectException(RequiredTransferPropertyException::class);

        // Act
        $this->tester->getCaptchaClient()->isCaptchaEnabled(new CaptchaChallengeRequestTransfer());
    }
}
