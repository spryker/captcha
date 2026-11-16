<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Builder;

use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Generated\Shared\Transfer\CaptchaLoginFormTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Yves\Captcha\CaptchaConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChallengeResponseBuilder implements ChallengeResponseBuilderInterface
{
    protected const string HEADER_CACHE_CONTROL = 'Cache-Control';

    protected const string HEADER_RETRY_AFTER = 'Retry-After';

    protected const string CACHE_CONTROL_NO_STORE = 'no-store';

    public function __construct(
        protected CaptchaClientInterface $captchaClient,
        protected CaptchaConfig $captchaConfig
    ) {
    }

    public function buildChallengeResponse(Request $request): Response
    {
        $context = $request->query->get(SharedCaptchaConfig::CHALLENGE_URL_QUERY_PARAMETER_CONTEXT);

        if (!is_string($context) || !in_array($context, $this->getLoginFormContexts(), true)) {
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
    protected function getLoginFormContexts(): array
    {
        return array_map(
            fn (CaptchaLoginFormTransfer $captchaLoginFormTransfer): string => $captchaLoginFormTransfer->getContextOrFail(),
            $this->captchaConfig->getLoginForms(),
        );
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
