<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Back Office challenge endpoint `/captcha/challenge?context=<context>` the CAPTCHA widget fetches its challenge from; the
 * Back Office must let anonymous users reach it.
 *
 * @method \Spryker\Zed\Captcha\Communication\CaptchaCommunicationFactory getFactory()
 */
class ChallengeController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        return $this->getFactory()
            ->createChallengeResponseBuilder()
            ->buildChallengeResponse($request);
    }
}
