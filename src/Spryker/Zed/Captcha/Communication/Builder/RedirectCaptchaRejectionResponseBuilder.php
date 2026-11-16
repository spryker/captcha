<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Builder;

use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers like the Back Office login failure handler: the multi-factor authentication script posts the login form with
 * an XHR and continues the login only when `requires_additional_auth` is `true`.
 *
 * @see \Spryker\Zed\SecurityGui\Communication\Plugin\Security\Handler\UserAuthenticationFailureHandler::onAuthenticationFailure()
 */
class RedirectCaptchaRejectionResponseBuilder implements CaptchaRejectionResponseBuilderInterface
{
    /**
     * @uses \Spryker\Zed\SecurityGui\Communication\Plugin\Security\Handler\UserAuthenticationFailureHandler::PARAMETER_REQUIRES_ADDITIONAL_AUTH
     */
    protected const string PARAMETER_REQUIRES_ADDITIONAL_AUTH = 'requires_additional_auth';

    public function __construct(protected MessengerFacadeInterface $messengerFacade)
    {
    }

    public function createCaptchaRejectionResponse(
        Request $request,
        CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer,
        string $loginUrl
    ): Response {
        foreach ($captchaVerificationResponseTransfer->getMessages() as $messageTransfer) {
            $this->messengerFacade->addErrorMessage((new MessageTransfer())->setValue($messageTransfer->getValue()));
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([static::PARAMETER_REQUIRES_ADDITIONAL_AUTH => false]);
        }

        return new RedirectResponse($loginUrl);
    }
}
