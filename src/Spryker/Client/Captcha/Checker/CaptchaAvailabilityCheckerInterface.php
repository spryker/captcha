<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Checker;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;

interface CaptchaAvailabilityCheckerInterface
{
    public function isCaptchaEnabled(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): bool;
}
