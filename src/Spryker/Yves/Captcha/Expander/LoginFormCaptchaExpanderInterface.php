<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Expander;

use Symfony\Component\Form\FormBuilderInterface;

interface LoginFormCaptchaExpanderInterface
{
    public function expand(FormBuilderInterface $builder, string $context): FormBuilderInterface;
}
