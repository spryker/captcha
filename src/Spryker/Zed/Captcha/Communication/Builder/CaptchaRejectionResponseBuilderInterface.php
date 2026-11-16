<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Builder;

use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CaptchaRejectionResponseBuilderInterface
{
    public function createCaptchaRejectionResponse(
        Request $request,
        CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer,
        string $loginUrl
    ): Response;
}
