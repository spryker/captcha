<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Resolver;

use Symfony\Component\HttpFoundation\Request;

interface LoginFormResolverInterface
{
    /**
     * @return list<\Generated\Shared\Transfer\CaptchaLoginFormTransfer>
     */
    public function resolveSubmittedLoginForms(Request $request): array;
}
