<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Reader;

use Spryker\Client\Captcha\CaptchaConfig;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;

class CaptchaResponseFieldNameReader implements CaptchaResponseFieldNameReaderInterface
{
    public function __construct(
        protected CaptchaConfig $captchaConfig,
        protected ?CaptchaProviderPluginInterface $captchaProviderPlugin,
    ) {
    }

    public function findResponseFieldName(): ?string
    {
        if (!$this->captchaConfig->isCaptchaEnabled() || $this->captchaProviderPlugin === null) {
            return null;
        }

        return $this->captchaProviderPlugin->getResponseFieldName();
    }
}
