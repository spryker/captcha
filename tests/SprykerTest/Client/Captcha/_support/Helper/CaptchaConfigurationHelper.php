<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Client\Captcha\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Spryker\Client\Configuration\ConfigurationClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class CaptchaConfigurationHelper extends Module
{
    use LocatorHelperTrait;

    protected const string LOCATOR_CACHE_KEY_CONFIGURATION_CLIENT = 'configuration-client';

    protected const string STORED_VALUE_ON = 'true';

    protected const bool STORED_VALUE_OFF = false;

    public function haveCaptchaEnabled(bool $isCaptchaEnabled): void
    {
        $this->haveStoredCaptchaEnabledValue($isCaptchaEnabled ? static::STORED_VALUE_ON : static::STORED_VALUE_OFF);
    }

    /**
     * Answers the Back Office setting the way the Configuration client does: `'true'` when on, `false` when off and
     * `null` when nothing is stored. Every other setting is read from the Configuration client.
     */
    public function haveStoredCaptchaEnabledValue(mixed $storedValue): void
    {
        $locatorHelper = $this->getLocatorHelper();

        $locatorHelper->addToLocatorCache(
            static::LOCATOR_CACHE_KEY_CONFIGURATION_CLIENT,
            $this->createConfigurationClient($locatorHelper->getLocator()->configuration()->client(), $storedValue),
        );
    }

    protected function createConfigurationClient(ConfigurationClientInterface $configurationClient, mixed $storedValue): ConfigurationClientInterface
    {
        return new class ($configurationClient, $storedValue) implements ConfigurationClientInterface {
            public function __construct(
                protected ConfigurationClientInterface $configurationClient,
                protected mixed $storedValue
            ) {
            }

            public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed
            {
                if ($configurationValueRequestTransfer->getKey() !== CaptchaConfig::CONFIGURATION_KEY_IS_CAPTCHA_ENABLED) {
                    return $this->configurationClient->getConfigurationValue($configurationValueRequestTransfer);
                }

                return $this->storedValue;
            }

            /**
             * @return array<string, mixed>
             */
            public function getConfigurationValues(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): array
            {
                return $this->configurationClient->getConfigurationValues($configurationValueRequestTransfer);
            }

            /**
             * @return array<string, string>
             */
            public function getStorageDataForScope(string $scope, ?string $scopeIdentifier = null): array
            {
                return $this->configurationClient->getStorageDataForScope($scope, $scopeIdentifier);
            }
        };
    }
}
