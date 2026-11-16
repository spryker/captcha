<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Checker;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Spryker\Client\Captcha\CaptchaConfig;

class CaptchaAvailabilityChecker implements CaptchaAvailabilityCheckerInterface
{
    public function __construct(protected CaptchaConfig $captchaConfig)
    {
    }

    public function isCaptchaEnabled(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): bool
    {
        $captchaChallengeRequestTransfer->requireContext();

        return $this->captchaConfig->isCaptchaEnabled();
    }
}
