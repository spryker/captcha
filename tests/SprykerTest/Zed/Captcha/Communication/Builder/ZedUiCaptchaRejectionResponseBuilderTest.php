<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Captcha\Communication\Builder;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Zed\Captcha\Communication\Builder\ZedUiCaptchaRejectionResponseBuilder;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Captcha
 * @group Communication
 * @group Builder
 * @group ZedUiCaptchaRejectionResponseBuilderTest
 * Add your own group annotations below this line
 */
class ZedUiCaptchaRejectionResponseBuilderTest extends Unit
{
    protected const string LOGIN_URL = '/security-merchant-portal-gui/login';

    public function testLogsWarningWhenJsonRejectionFallsBackToARedirectWithoutAZedUiFactory(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning');
        $request = Request::create(static::LOGIN_URL, Request::METHOD_POST, server: ['HTTP_ACCEPT' => 'application/json']);

        // Act
        $response = $this->createBuilder($loggerMock)->createCaptchaRejectionResponse($request, new CaptchaVerificationResponseTransfer(), static::LOGIN_URL);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(static::LOGIN_URL, $response->getTargetUrl());
    }

    public function testRedirectsWithoutWarningWhenNoJsonIsAccepted(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->never())->method('warning');
        $request = Request::create(static::LOGIN_URL, Request::METHOD_POST);

        // Act
        $response = $this->createBuilder($loggerMock)->createCaptchaRejectionResponse($request, new CaptchaVerificationResponseTransfer(), static::LOGIN_URL);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    protected function createBuilder(LoggerInterface $logger): ZedUiCaptchaRejectionResponseBuilder
    {
        return new ZedUiCaptchaRejectionResponseBuilder($this->createMock(MessengerFacadeInterface::class), null, $logger);
    }
}
