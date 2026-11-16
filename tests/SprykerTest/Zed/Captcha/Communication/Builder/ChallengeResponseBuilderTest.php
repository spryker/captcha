<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Captcha\Communication\Builder;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Zed\Captcha\CaptchaDependencyProvider;
use Spryker\Zed\Captcha\Communication\CaptchaCommunicationFactory;
use SprykerTest\Zed\Captcha\CaptchaCommunicationTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Captcha
 * @group Communication
 * @group Builder
 * @group ChallengeResponseBuilderTest
 * Add your own group annotations below this line
 */
class ChallengeResponseBuilderTest extends Unit
{
    protected const string CHALLENGE_DATA = '{"parameters":{"algorithm":"ARGON2ID"},"signature":"signed"}';

    protected const string BACKOFFICE_CHALLENGE_PATH = '/captcha/challenge';

    protected const string MERCHANT_PORTAL_CHALLENGE_PATH = '/captcha/merchant-portal-challenge';

    protected const int RETRY_AFTER_SECONDS = 60;

    protected CaptchaCommunicationTester $tester;

    /**
     * @dataProvider provideChallengePathsWithTheirContexts
     */
    public function testAnswersTheChallengeDataOfALoginFormContextOfTheEndpointAsUncachedJson(string $path, string $context): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock((new CaptchaChallengeDataResponseTransfer())->setIsSuccessful(true)->setChallengeData(static::CHALLENGE_DATA));

        // Expect
        $captchaClientMock->expects($this->once())
            ->method('createChallengeData')
            ->with($this->callback(fn (CaptchaChallengeDataRequestTransfer $captchaChallengeDataRequestTransfer): bool => $captchaChallengeDataRequestTransfer->getContext() === $context
                && $captchaChallengeDataRequestTransfer->getIp() === CaptchaCommunicationTester::CLIENT_IP));

        // Act
        $response = $this->buildChallengeResponse($captchaClientMock, $context, $path);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(static::CHALLENGE_DATA, $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function provideChallengePathsWithTheirContexts(): array
    {
        return [
            'Back Office user' => [static::BACKOFFICE_CHALLENGE_PATH, SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN],
            'Merchant Portal user' => [static::MERCHANT_PORTAL_CHALLENGE_PATH, SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_USER_LOGIN],
            'Merchant Portal agent' => [static::MERCHANT_PORTAL_CHALLENGE_PATH, SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_AGENT_LOGIN],
        ];
    }

    /**
     * Each endpoint serves only the login forms whose challenge URL it is, so neither application loads the challenge of the other.
     *
     * @dataProvider provideChallengePathsWithAContextOfAnotherApplication
     */
    public function testAnswersNotFoundWithoutCreatingAChallengeForAContextOfAnotherApplication(string $path, string $context): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);

        // Expect
        $captchaClientMock->expects($this->never())->method('createChallengeData');

        // Act
        $response = $this->buildChallengeResponse($captchaClientMock, $context, $path);

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertErrorBody('not_found', $response);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function provideChallengePathsWithAContextOfAnotherApplication(): array
    {
        return [
            'Storefront customer on the Back Office endpoint' => [static::BACKOFFICE_CHALLENGE_PATH, SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN],
            'Merchant Portal user on the Back Office endpoint' => [static::BACKOFFICE_CHALLENGE_PATH, SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_USER_LOGIN],
            'Merchant Portal agent on the Back Office endpoint' => [static::BACKOFFICE_CHALLENGE_PATH, SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_AGENT_LOGIN],
            'Back Office user on the Merchant Portal endpoint' => [static::MERCHANT_PORTAL_CHALLENGE_PATH, SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN],
        ];
    }

    /**
     * @dataProvider provideMethodsOtherThanGetAndHead
     */
    public function testAnswersMethodNotAllowedWithoutCreatingAChallengeForAMethodOtherThanGetOrHead(string $method): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);

        // Expect
        $captchaClientMock->expects($this->never())->method('createChallengeData');

        // Act
        $response = $this->buildChallengeResponse($captchaClientMock, SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN, static::BACKOFFICE_CHALLENGE_PATH, $method);

        // Assert
        $this->assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertSame('GET, HEAD', $response->headers->get('Allow'));
        $this->assertErrorBody('method_not_allowed', $response);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function provideMethodsOtherThanGetAndHead(): array
    {
        return [
            Request::METHOD_POST => [Request::METHOD_POST],
            Request::METHOD_PUT => [Request::METHOD_PUT],
            Request::METHOD_PATCH => [Request::METHOD_PATCH],
            Request::METHOD_DELETE => [Request::METHOD_DELETE],
        ];
    }

    public function testAnswersAHeadRequestLikeAGetRequest(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock((new CaptchaChallengeDataResponseTransfer())->setIsSuccessful(true)->setChallengeData(static::CHALLENGE_DATA));

        // Act
        $response = $this->buildChallengeResponse($captchaClientMock, SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN, static::BACKOFFICE_CHALLENGE_PATH, Request::METHOD_HEAD);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testAnswersTooManyRequestsWithTheRetryAfterOfTheRateLimitWhenRateLimited(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(
            (new CaptchaChallengeDataResponseTransfer())->setIsSuccessful(false)->setIsRateLimited(true)->setRetryAfterSeconds(static::RETRY_AFTER_SECONDS),
        );

        // Act
        $response = $this->buildChallengeResponse($captchaClientMock, SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN);

        // Assert
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame((string)static::RETRY_AFTER_SECONDS, $response->headers->get('Retry-After'));
        $this->assertErrorBody('rate_limited', $response);
    }

    public function testAnswersServiceUnavailableWhenNoChallengeCanBeCreated(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock((new CaptchaChallengeDataResponseTransfer())->setIsSuccessful(false)->setIsRateLimited(false));

        // Act
        $response = $this->buildChallengeResponse($captchaClientMock, SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN);

        // Assert
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertFalse($response->headers->has('Retry-After'));
        $this->assertErrorBody('unavailable', $response);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\Captcha\CaptchaClientInterface
     */
    protected function createCaptchaClientMock(CaptchaChallengeDataResponseTransfer $captchaChallengeDataResponseTransfer): CaptchaClientInterface
    {
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('createChallengeData')->willReturn($captchaChallengeDataResponseTransfer);

        return $captchaClientMock;
    }

    protected function buildChallengeResponse(
        CaptchaClientInterface $captchaClient,
        string $context,
        string $path = self::BACKOFFICE_CHALLENGE_PATH,
        string $method = Request::METHOD_GET
    ): Response {
        $this->tester->setDependency(CaptchaDependencyProvider::CLIENT_CAPTCHA, $captchaClient);
        $request = Request::create(sprintf('%s?context=%s', $path, $context), $method, [], [], [], ['REMOTE_ADDR' => CaptchaCommunicationTester::CLIENT_IP]);

        return (new CaptchaCommunicationFactory())->createChallengeResponseBuilder()->buildChallengeResponse($request);
    }

    protected function assertErrorBody(string $expectedError, Response $response): void
    {
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(['error' => $expectedError], json_decode((string)$response->getContent(), true));
    }
}
