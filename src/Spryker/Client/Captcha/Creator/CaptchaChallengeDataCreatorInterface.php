<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Creator;

use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;

interface CaptchaChallengeDataCreatorInterface
{
    public function createChallengeData(CaptchaChallengeDataRequestTransfer $captchaChallengeDataRequestTransfer): CaptchaChallengeDataResponseTransfer;
}
