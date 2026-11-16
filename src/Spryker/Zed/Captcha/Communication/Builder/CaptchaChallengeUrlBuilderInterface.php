<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Builder;

interface CaptchaChallengeUrlBuilderInterface
{
    public function findChallengeUrl(string $context): ?string;
}
