<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint of the Back Office setting `security:captcha:login:is_enabled`, declared in `captcha.configuration.yml`:
 * the Configuration module creates it from its class name, so it takes no constructor arguments.
 */
class CaptchaProviderConfiguredConstraint extends Constraint
{
    public string $message = 'Set up the CAPTCHA provider under Integrations before turning on the CAPTCHA.';

    public function validatedBy(): string
    {
        return CaptchaProviderConfiguredConstraintValidator::class;
    }
}
