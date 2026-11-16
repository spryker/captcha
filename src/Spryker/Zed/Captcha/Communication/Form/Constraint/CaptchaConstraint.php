<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Form\Constraint;

use Spryker\Client\Captcha\CaptchaClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;

class CaptchaConstraint extends Constraint
{
    /**
     * @param array<string>|null $groups
     */
    public function __construct(
        protected CaptchaClientInterface $captchaClient,
        protected RequestStack $requestStack,
        protected string $context,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function getCaptchaClient(): CaptchaClientInterface
    {
        return $this->captchaClient;
    }

    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    public function getContext(): string
    {
        return $this->context;
    }
}
