<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Zed\Captcha\Helper;

use Codeception\Module;
use Spryker\Shared\Translator\TranslatorConstants;
use Spryker\Zed\Locale\LocaleDependencyProvider;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;
use SprykerTest\Shared\Testify\Helper\ConfigHelperTrait;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class CaptchaTranslatorHelper extends Module
{
    use ConfigHelperTrait;
    use DependencyHelperTrait;
    use LocatorHelperTrait;

    protected const string TRANSLATION_FILE_PATH_PATTERN = '%s/data/translation/Zed/[a-z][a-z]_[A-Z][A-Z].csv';

    protected const string TRANSLATOR_CACHE_DIRECTORY = 'captcha-translator';

    /**
     * Loads only the Zed translation files of the Captcha module, so a translated text also proves that the module
     * ships the key; debug mode rebuilds the cached catalogue after a translation file changes.
     *
     * Returns a Translator facade set to the locale: with dynamic store mode off, the Locale module ignores
     * `LocaleDependencyProvider::LOCALE_CURRENT`, so only a translator with an explicitly set locale uses it in both modes.
     */
    public function haveTranslatorWithModuleTranslationFiles(string $localeName): TranslatorFacadeInterface
    {
        $this->setDependency(LocaleDependencyProvider::LOCALE_CURRENT, $localeName);
        $this->setConfig(TranslatorConstants::TRANSLATION_ZED_FILE_PATH_PATTERNS, [
            sprintf(static::TRANSLATION_FILE_PATH_PATTERN, dirname(__DIR__, 6)),
        ]);
        $this->setConfig(TranslatorConstants::TRANSLATION_ZED_CACHE_DIRECTORY, codecept_output_dir(static::TRANSLATOR_CACHE_DIRECTORY));
        $this->setConfig(TranslatorConstants::TRANSLATION_ZED_DEBUG_ENABLED, true);

        $translatorFacade = $this->getLocator()->translator()->facade();
        $translatorFacade->setLocale($localeName);

        return $translatorFacade;
    }
}
