<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Builder;

use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Zed\Captcha\CaptchaConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChallengeResponseBuilder implements ChallengeResponseBuilderInterface
{
    protected const string HEADER_CACHE_CONTROL = 'Cache-Control';

    protected const string HEADER_RETRY_AFTER = 'Retry-After';

    protected const string HEADER_ALLOW = 'Allow';

    protected const string CACHE_CONTROL_NO_STORE = 'no-store';

    /**
     * @var list<string>
     */
    protected const array ALLOWED_METHODS = [Request::METHOD_GET, Request::METHOD_HEAD];

    protected const string ALLOWED_METHODS_SEPARATOR = ', ';

    public function __construct(
        protected CaptchaClientInterface $captchaClient,
        protected CaptchaConfig $captchaConfig
    ) {
    }

    public function buildChallengeResponse(Request $request): Response
    {
        if (!in_array($request->getMethod(), static::ALLOWED_METHODS, true)) {
            return $this->createErrorResponse(Response::HTTP_METHOD_NOT_ALLOWED, SharedCaptchaConfig::CHALLENGE_RESPONSE_ERROR_METHOD_NOT_ALLOWED, [
                static::HEADER_ALLOW => implode(static::ALLOWED_METHODS_SEPARATOR, static::ALLOWED_METHODS),
            ]);
        }

        $context = $request->query->get(SharedCaptchaConfig::CHALLENGE_URL_QUERY_PARAMETER_CONTEXT);

        if (!is_string($context) || !in_array($context, $this->getLoginFormContexts($request->getPathInfo()), true)) {
            return $this->createErrorResponse(Response::HTTP_NOT_FOUND, SharedCaptchaConfig::CHALLENGE_RESPONSE_ERROR_NOT_FOUND);
        }

        $captchaChallengeDataResponseTransfer = $this->captchaClient->createChallengeData(
            (new CaptchaChallengeDataRequestTransfer())
                ->setContext($context)
                ->setIp((string)$request->getClientIp()),
        );

        if ($captchaChallengeDataResponseTransfer->getIsSuccessful()) {
            return $this->createChallengeResponse((string)$captchaChallengeDataResponseTransfer->getChallengeData());
        }

        if ($captchaChallengeDataResponseTransfer->getIsRateLimited()) {
            return $this->createRateLimitedResponse($captchaChallengeDataResponseTransfer);
        }

        return $this->createErrorResponse(Response::HTTP_SERVICE_UNAVAILABLE, SharedCaptchaConfig::CHALLENGE_RESPONSE_ERROR_UNAVAILABLE);
    }

    /**
     * @return list<string>
     */
    protected function getLoginFormContexts(string $challengeUrl): array
    {
        $contexts = [];

        foreach ($this->captchaConfig->getLoginForms() as $captchaLoginFormTransfer) {
            if ($captchaLoginFormTransfer->getChallengeUrl() !== $challengeUrl) {
                continue;
            }

            $contexts[] = $captchaLoginFormTransfer->getContextOrFail();
        }

        return $contexts;
    }

    protected function createChallengeResponse(string $challengeData): Response
    {
        return JsonResponse::fromJsonString($challengeData, Response::HTTP_OK, [
            static::HEADER_CACHE_CONTROL => static::CACHE_CONTROL_NO_STORE,
        ]);
    }

    protected function createRateLimitedResponse(CaptchaChallengeDataResponseTransfer $captchaChallengeDataResponseTransfer): Response
    {
        $retryAfterSeconds = $captchaChallengeDataResponseTransfer->getRetryAfterSeconds();

        if ($retryAfterSeconds === null) {
            return $this->createErrorResponse(Response::HTTP_TOO_MANY_REQUESTS, SharedCaptchaConfig::CHALLENGE_RESPONSE_ERROR_RATE_LIMITED);
        }

        return $this->createErrorResponse(Response::HTTP_TOO_MANY_REQUESTS, SharedCaptchaConfig::CHALLENGE_RESPONSE_ERROR_RATE_LIMITED, [
            static::HEADER_RETRY_AFTER => (string)$retryAfterSeconds,
        ]);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function createErrorResponse(int $statusCode, string $error, array $headers = []): Response
    {
        return new JsonResponse([SharedCaptchaConfig::CHALLENGE_RESPONSE_KEY_ERROR => $error], $statusCode, [
            static::HEADER_CACHE_CONTROL => static::CACHE_CONTROL_NO_STORE,
        ] + $headers);
    }
}
