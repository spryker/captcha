<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Constraint;

use Spryker\Zed\Kernel\Communication\Validator\AbstractConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @method \Spryker\Zed\Captcha\Communication\CaptchaCommunicationFactory getFactory()
 */
class CaptchaProviderConfiguredConstraintValidator extends AbstractConstraintValidator
{
    /**
     * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CaptchaProviderConfiguredConstraint) {
            throw new UnexpectedTypeException($constraint, CaptchaProviderConfiguredConstraint::class);
        }

        if (!filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if ($this->getFactory()->getCaptchaClient()->isCaptchaProviderConfigured()) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
