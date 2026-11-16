<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Checker;

use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;

interface CaptchaRequirementCheckerInterface
{
    public function isCaptchaRequired(CaptchaRequirementRequestTransfer $captchaRequirementRequestTransfer): bool;
}
