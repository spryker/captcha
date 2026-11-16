<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha;

use Codeception\Test\Unit;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group FindResponseFieldNameTest
 * Add your own group annotations below this line
 */
class FindResponseFieldNameTest extends Unit
{
    protected const string RESPONSE_FIELD_NAME = 'g-recaptcha-response';

    protected CaptchaClientTester $tester;

    public function testReturnsResponseFieldNameOfProvider(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($this->createProviderPluginWithResponseFieldName(static::RESPONSE_FIELD_NAME));

        // Act
        $responseFieldName = $this->tester->getCaptchaClient()->findResponseFieldName();

        // Assert
        $this->assertSame(static::RESPONSE_FIELD_NAME, $responseFieldName);
    }

    public function testReturnsNullWhenProviderPostsTokenInFormField(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($this->createProviderPluginWithResponseFieldName(null));

        // Act
        $responseFieldName = $this->tester->getCaptchaClient()->findResponseFieldName();

        // Assert
        $this->assertNull($responseFieldName);
    }

    public function testReturnsNullWhenDisabled(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(false);
        $this->tester->haveCaptchaProviderPlugin($this->createProviderPluginWithResponseFieldName(static::RESPONSE_FIELD_NAME));

        // Act
        $responseFieldName = $this->tester->getCaptchaClient()->findResponseFieldName();

        // Assert
        $this->assertNull($responseFieldName);
    }

    public function testReturnsNullWhenNoProviderIsWired(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(true);

        // Act
        $responseFieldName = $this->tester->getCaptchaClient()->findResponseFieldName();

        // Assert
        $this->assertNull($responseFieldName);
    }

    protected function createProviderPluginWithResponseFieldName(?string $responseFieldName): CaptchaProviderPluginInterface
    {
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('getResponseFieldName')->willReturn($responseFieldName);

        return $captchaProviderPluginMock;
    }
}
