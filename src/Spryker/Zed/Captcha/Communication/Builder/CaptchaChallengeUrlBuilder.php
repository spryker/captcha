<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Builder;

use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Zed\Captcha\CaptchaConfig;

class CaptchaChallengeUrlBuilder implements CaptchaChallengeUrlBuilderInterface
{
    protected const string CHALLENGE_URL_PATTERN = '%s?%s';

    public function __construct(protected CaptchaConfig $captchaConfig)
    {
    }

    public function findChallengeUrl(string $context): ?string
    {
        foreach ($this->captchaConfig->getLoginForms() as $captchaLoginFormTransfer) {
            if ($captchaLoginFormTransfer->getContext() !== $context || (string)$captchaLoginFormTransfer->getChallengeUrl() === '') {
                continue;
            }

            return sprintf(
                static::CHALLENGE_URL_PATTERN,
                $captchaLoginFormTransfer->getChallengeUrl(),
                http_build_query([SharedCaptchaConfig::CHALLENGE_URL_QUERY_PARAMETER_CONTEXT => $context]),
            );
        }

        return null;
    }
}
