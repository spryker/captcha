<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Yves\Captcha\Builder;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Yves\Captcha\CaptchaDependencyProvider;
use Spryker\Yves\Captcha\CaptchaFactory;
use SprykerTest\Yves\Captcha\CaptchaYvesTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Yves
 * @group Captcha
 * @group Builder
 * @group ChallengeResponseBuilderTest
 * Add your own group annotations below this line
 */
class ChallengeResponseBuilderTest extends Unit
{
    protected const string CHALLENGE_DATA = '{"parameters":{"algorithm":"ARGON2ID"},"signature":"signed"}';

    protected const int RETRY_AFTER_SECONDS = 60;

    protected CaptchaYvesTester $tester;

    public function testAnswersTheChallengeDataOfALoginFormContextAsUncachedJson(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock((new CaptchaChallengeDataResponseTransfer())->setIsSuccessful(true)->setChallengeData(static::CHALLENGE_DATA));

        // Expect
        $captchaClientMock->expects($this->once())
            ->method('createChallengeData')
            ->with($this->callback(fn (CaptchaChallengeDataRequestTransfer $captchaChallengeDataRequestTransfer): bool => $captchaChallengeDataRequestTransfer->getContext() === SharedCaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN
                && $captchaChallengeDataRequestTransfer->getIp() === CaptchaYvesTester::CLIENT_IP));

        // Act
        $response = $this->buildChallengeResponse($captchaClientMock, SharedCaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(static::CHALLENGE_DATA, $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
    }

    public function testAnswersNotFoundWithoutCreatingAChallengeForAnotherContext(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);

        // Expect
        $captchaClientMock->expects($this->never())->method('createChallengeData');

        // Act
        $response = $this->buildChallengeResponse($captchaClientMock, SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN);

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertErrorBody('not_found', $response);
    }

    public function testAnswersTooManyRequestsWithTheRetryAfterOfTheRateLimitWhenRateLimited(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(
            (new CaptchaChallengeDataResponseTransfer())->setIsSuccessful(false)->setIsRateLimited(true)->setRetryAfterSeconds(static::RETRY_AFTER_SECONDS),
        );

        // Act
        $response = $this->buildChallengeResponse($captchaClientMock, SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN);

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
        $response = $this->buildChallengeResponse($captchaClientMock, SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN);

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

    protected function buildChallengeResponse(CaptchaClientInterface $captchaClient, string $context): Response
    {
        $this->tester->setDependency(CaptchaDependencyProvider::CLIENT_CAPTCHA, $captchaClient);
        $request = Request::create('/captcha/challenge', Request::METHOD_GET, ['context' => $context], [], [], ['REMOTE_ADDR' => CaptchaYvesTester::CLIENT_IP]);

        return (new CaptchaFactory())->createChallengeResponseBuilder()->buildChallengeResponse($request);
    }

    protected function assertErrorBody(string $expectedError, Response $response): void
    {
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(['error' => $expectedError], json_decode((string)$response->getContent(), true));
    }
}
