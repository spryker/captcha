<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Builder;

use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Shared\ZedUi\ZedUiFactoryInterface;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers like the Merchant Portal login failure handler: the Merchant Portal login form posts as JSON and follows the redirect
 * action of a ZedUi form response.
 *
 * @see \Spryker\Zed\SecurityMerchantPortalGui\Communication\Plugin\Security\Handler\MerchantUserAuthenticationFailureHandler::onAuthenticationFailure()
 */
class ZedUiCaptchaRejectionResponseBuilder implements CaptchaRejectionResponseBuilderInterface
{
    protected const string CONTENT_TYPE_JSON = 'application/json';

    protected const string LOG_MESSAGE_ZED_UI_FACTORY_MISSING = 'A Merchant Portal login form asked for a JSON answer to a CAPTCHA rejection, but ZedUiApplicationPlugin is not registered in this application, so the rejection is answered with a redirect that the form does not show.';

    public function __construct(
        protected MessengerFacadeInterface $messengerFacade,
        protected ?ZedUiFactoryInterface $zedUiFactory,
        protected LoggerInterface $logger
    ) {
    }

    public function createCaptchaRejectionResponse(
        Request $request,
        CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer,
        string $loginUrl
    ): Response {
        foreach ($captchaVerificationResponseTransfer->getMessages() as $messageTransfer) {
            $this->messengerFacade->addErrorMessage((new MessageTransfer())->setValue($messageTransfer->getValue()));
        }

        $isJsonAccepted = in_array(static::CONTENT_TYPE_JSON, $request->getAcceptableContentTypes(), true);

        if ($isJsonAccepted && $this->zedUiFactory === null) {
            $this->logger->warning(static::LOG_MESSAGE_ZED_UI_FACTORY_MISSING);
        }

        if ($this->zedUiFactory === null || !$isJsonAccepted) {
            return new RedirectResponse($loginUrl);
        }

        return new JsonResponse($this->zedUiFactory
            ->createZedUiFormResponseBuilder()
            ->addActionRedirect($loginUrl)
            ->createResponse()
            ->toArray());
    }
}
