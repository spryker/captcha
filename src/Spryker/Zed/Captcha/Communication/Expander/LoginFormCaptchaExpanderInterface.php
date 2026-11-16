<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Expander;

use Symfony\Component\Form\FormBuilderInterface;

interface LoginFormCaptchaExpanderInterface
{
    /**
     * @param array<string, mixed> $captchaTypeOptions
     */
    public function expand(FormBuilderInterface $builder, string $context, array $captchaTypeOptions = []): FormBuilderInterface;
}
