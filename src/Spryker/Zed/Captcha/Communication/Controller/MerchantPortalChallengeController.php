<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Controller;

/**
 * The Merchant Portal challenge endpoint `/captcha/merchant-portal-challenge?context=<context>`; the Merchant Portal router only
 * routes controllers whose path or file name contains `MerchantPortal`. The Merchant Portal must let anonymous users reach it.
 */
class MerchantPortalChallengeController extends ChallengeController
{
}
