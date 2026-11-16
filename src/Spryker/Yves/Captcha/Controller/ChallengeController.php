<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \Spryker\Yves\Captcha\CaptchaFactory getFactory()
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
