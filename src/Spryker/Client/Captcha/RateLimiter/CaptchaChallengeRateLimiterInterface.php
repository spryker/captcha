<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\RateLimiter;

interface CaptchaChallengeRateLimiterInterface
{
    public function isRateLimited(string $context, string $ip): bool;

    public function getWindowSeconds(): int;
}
