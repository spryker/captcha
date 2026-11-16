<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Validator;

use Generated\Shared\Transfer\CaptchaWidgetTransfer;

interface CaptchaWidgetValidatorInterface
{
    /**
     * @return list<string>
     */
    public function validate(CaptchaWidgetTransfer $captchaWidgetTransfer): array;
}
