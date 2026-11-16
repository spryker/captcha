<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Form\Constraint;

use ArrayObject;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Shared\Captcha\CaptchaConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CaptchaConstraintValidator extends ConstraintValidator
{
    /**
     * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CaptchaConstraint) {
            throw new UnexpectedTypeException($constraint, CaptchaConstraint::class);
        }

        $request = $constraint->getRequestStack()->getCurrentRequest();

        $captchaVerificationResponseTransfer = $constraint->getCaptchaClient()->verifyCaptcha(
            (new CaptchaVerificationRequestTransfer())
                ->setPayload($this->findPayload($value, $constraint, $request))
                ->setContext($constraint->getContext())
                ->setIp($request?->getClientIp()),
        );

        if ($captchaVerificationResponseTransfer->getIsValid()) {
            return;
        }

        foreach ($this->getRejectionMessages($captchaVerificationResponseTransfer) as $messageTransfer) {
            $this->context->buildViolation((string)$messageTransfer->getValue())
                ->setParameters($messageTransfer->getParameters())
                ->addViolation();
        }
    }

    protected function findPayload(mixed $value, CaptchaConstraint $constraint, ?Request $request): ?string
    {
        $responseFieldName = $constraint->getCaptchaClient()->findResponseFieldName();

        if ($responseFieldName !== null && $request !== null) {
            $value = $request->request->all()[$responseFieldName] ?? null;
        }

        return is_string($value) ? $value : null;
    }

    /**
     * @return \ArrayObject<int, \Generated\Shared\Transfer\MessageTransfer>
     */
    protected function getRejectionMessages(CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer): ArrayObject
    {
        if ($captchaVerificationResponseTransfer->getMessages()->count() > 0) {
            return $captchaVerificationResponseTransfer->getMessages();
        }

        return new ArrayObject([(new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID)]);
    }
}
