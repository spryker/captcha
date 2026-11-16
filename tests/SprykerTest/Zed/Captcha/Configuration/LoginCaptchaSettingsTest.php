<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Captcha\Configuration;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationErrorTransfer;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Zed\Captcha\Communication\Constraint\CaptchaProviderConfiguredConstraint;
use SprykerTest\Zed\Captcha\CaptchaConfigurationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Captcha
 * @group Configuration
 * @group LoginCaptchaSettingsTest
 * Add your own group annotations below this line
 */
class LoginCaptchaSettingsTest extends Unit
{
    protected const string FEATURE_SECURITY = 'security';

    protected const string TAB_LOGIN_LOCK = 'login_lock';

    protected const string MESSAGE_PROVIDER_NOT_SET_UP_DE = 'Richten Sie den CAPTCHA-Anbieter unter Integrationen ein, bevor Sie das CAPTCHA einschalten.';

    protected CaptchaConfigurationTester $tester;

    public function testCaptchaIsOffWhenNothingIsStored(): void
    {
        // Arrange
        $this->tester->haveNoStoredGlobalConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED);

        // Act
        $isCaptchaEnabled = $this->tester->getConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED);

        // Assert
        $this->assertFalse($isCaptchaEnabled);
    }

    /**
     * @dataProvider provideCaptchaSwitchValues
     */
    public function testCaptchaCanBeSwitchedOnAndOffWhileTheProviderIsConfigured(string $value, bool $expectedIsCaptchaEnabled): void
    {
        // Arrange
        $this->tester->haveCaptchaProviderPlugin($this->createCaptchaProviderPlugin(true));

        // Act
        $configurationValueCollectionResponseTransfer = $this->tester->saveGlobalConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED, $value);

        // Assert
        $this->assertTrue($configurationValueCollectionResponseTransfer->getIsSuccess());
        $this->assertSame($expectedIsCaptchaEnabled, filter_var($this->tester->getConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED), FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * @dataProvider provideUnconfiguredProviders
     */
    public function testCaptchaCannotBeTurnedOnWhileTheProviderIsNotSetUp(?bool $isProviderConfigured): void
    {
        // Arrange
        $this->tester->haveNoStoredGlobalConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED);
        $this->haveCaptchaProvider($isProviderConfigured);

        // Act
        $configurationValueCollectionResponseTransfer = $this->tester->saveGlobalConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED, 'true');

        // Assert
        $this->assertFalse($configurationValueCollectionResponseTransfer->getIsSuccess());
        $this->assertSame(
            [(new CaptchaProviderConfiguredConstraint())->message],
            array_map(fn (ConfigurationErrorTransfer $configurationErrorTransfer): ?string => $configurationErrorTransfer->getMessage(), $configurationValueCollectionResponseTransfer->getErrors()->getArrayCopy()),
        );
        $this->assertFalse($this->tester->getConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED));
    }

    public function testTheRejectionOfTurningOnTheCaptchaIsTranslated(): void
    {
        // Arrange
        $this->haveCaptchaProvider(false);

        // Act
        $configurationValueCollectionResponseTransfer = $this->tester->saveGlobalConfigurationValue(
            SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED,
            'true',
            CaptchaConfigurationTester::LOCALE_NAME_DE,
        );

        // Assert
        $this->assertSame(
            static::MESSAGE_PROVIDER_NOT_SET_UP_DE,
            $configurationValueCollectionResponseTransfer->getErrors()->offsetGet(0)->getMessage(),
        );
    }

    /**
     * @dataProvider provideUnconfiguredProviders
     */
    public function testCaptchaCanBeTurnedOffWhileTheProviderIsNotSetUp(?bool $isProviderConfigured): void
    {
        // Arrange
        $this->haveCaptchaProvider($isProviderConfigured);

        // Act
        $configurationValueCollectionResponseTransfer = $this->tester->saveGlobalConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED, 'false');

        // Assert
        $this->assertTrue($configurationValueCollectionResponseTransfer->getIsSuccess());
        $this->assertFalse(filter_var($this->tester->getConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED), FILTER_VALIDATE_BOOLEAN));
    }

    public function testCaptchaModeIsShownOnlyWhileTheCaptchaIsOn(): void
    {
        // Act
        $dependencies = $this->tester->getConfigurationSettingDependencies(SharedCaptchaConfig::CONFIGURATION_KEY_CAPTCHA_MODE);

        // Assert
        $this->assertSame([['when' => ['any' => [$this->createCaptchaIsOnCondition()]]]], $dependencies);
    }

    public function testCaptchaModeLinksToTheLoginLockTab(): void
    {
        // Act
        $configurationSettingLinkTransfer = $this->tester->findConfigurationSettingLink(SharedCaptchaConfig::CONFIGURATION_KEY_CAPTCHA_MODE);

        // Assert
        $this->assertSame(static::FEATURE_SECURITY, $configurationSettingLinkTransfer?->getFeatureKey());
        $this->assertSame(static::TAB_LOGIN_LOCK, $configurationSettingLinkTransfer?->getTabKey());
    }

    public function testCaptchaModeIsAfterFailedAttemptsWhenNothingIsStored(): void
    {
        // Arrange
        $this->tester->haveNoStoredGlobalConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_CAPTCHA_MODE);

        // Act
        $captchaMode = $this->tester->getConfigurationValue(SharedCaptchaConfig::CONFIGURATION_KEY_CAPTCHA_MODE);

        // Assert
        $this->assertSame(SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, $captchaMode);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function provideCaptchaSwitchValues(): array
    {
        return [
            'on' => ['true', true],
            'off' => ['false', false],
        ];
    }

    /**
     * `null` stands for no provider plugin wired.
     *
     * @return array<string, array{bool|null}>
     */
    public function provideUnconfiguredProviders(): array
    {
        return [
            'provider not set up' => [false],
            'no provider wired' => [null],
        ];
    }

    protected function haveCaptchaProvider(?bool $isProviderConfigured): void
    {
        if ($isProviderConfigured === null) {
            return;
        }

        $this->tester->haveCaptchaProviderPlugin($this->createCaptchaProviderPlugin($isProviderConfigured));
    }

    protected function createCaptchaProviderPlugin(bool $isConfigured): CaptchaProviderPluginInterface
    {
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('isConfigured')->willReturn($isConfigured);

        return $captchaProviderPluginMock;
    }

    /**
     * @return array<string, string>
     */
    protected function createCaptchaIsOnCondition(): array
    {
        return [
            'setting' => SharedCaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED,
            'operator' => 'equals',
            'value' => 'true',
        ];
    }
}
