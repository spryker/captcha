<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Resolver;

use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;

interface CaptchaProviderPluginResolverInterface
{
    public function findCaptchaProviderPlugin(): ?CaptchaProviderPluginInterface;
}
