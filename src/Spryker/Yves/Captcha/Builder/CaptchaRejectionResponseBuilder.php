<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Builder;

use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Spryker\Yves\Messenger\FlashMessenger\FlashMessengerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers like the Storefront login failure handlers: the multi-factor authentication script posts the login form with an
 * XHR and continues the login only when `requires_additional_auth` is `true`; a page submission goes back to the page the form
 * was on, which can be the checkout as well as the login page.
 *
 * @see \SprykerShop\Yves\CustomerPage\Plugin\Provider\CustomerAuthenticationFailureHandler::onAuthenticationFailure()
 */
class CaptchaRejectionResponseBuilder implements CaptchaRejectionResponseBuilderInterface
{
    /**
     * @uses \SprykerShop\Yves\CustomerPage\Plugin\Provider\CustomerAuthenticationFailureHandler::PARAMETER_REQUIRES_ADDITIONAL_AUTH
     */
    protected const string PARAMETER_REQUIRES_ADDITIONAL_AUTH = 'requires_additional_auth';

    protected const string HEADER_REFERER = 'Referer';

    /**
     * @var list<string>
     */
    protected const array WEB_URL_SCHEMES = ['http', 'https'];

    public function __construct(protected FlashMessengerInterface $flashMessenger)
    {
    }

    public function createCaptchaRejectionResponse(
        Request $request,
        CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer,
        string $fallbackUrl
    ): Response {
        foreach ($captchaVerificationResponseTransfer->getMessages() as $messageTransfer) {
            $this->flashMessenger->addErrorMessage((string)$messageTransfer->getValue());
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([static::PARAMETER_REQUIRES_ADDITIONAL_AUTH => false]);
        }

        return new RedirectResponse($this->resolveRedirectUrl($request, $fallbackUrl));
    }

    protected function resolveRedirectUrl(Request $request, string $fallbackUrl): string
    {
        $refererUrl = $request->headers->get(static::HEADER_REFERER);

        if ($refererUrl === null || !$this->isSameHostWebUrl($refererUrl, $request)) {
            return $fallbackUrl;
        }

        return $refererUrl;
    }

    protected function isSameHostWebUrl(string $url, Request $request): bool
    {
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, static::WEB_URL_SCHEMES, true) && parse_url($url, PHP_URL_HOST) === $request->getHost();
    }
}
