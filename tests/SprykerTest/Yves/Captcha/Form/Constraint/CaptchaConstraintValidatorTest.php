<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Yves\Captcha\Form\Constraint;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use Spryker\Yves\Captcha\Form\Constraint\CaptchaConstraint;
use SprykerTest\Yves\Captcha\CaptchaYvesTester;
use Symfony\Component\Validator\Validation;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Yves
 * @group Captcha
 * @group Form
 * @group Constraint
 * @group CaptchaConstraintValidatorTest
 * Add your own group annotations below this line
 */
class CaptchaConstraintValidatorTest extends Unit
{
    protected const string PAYLOAD = 'solved-payload';

    protected const string RESPONSE_FIELD_NAME = 'g-recaptcha-response';

    protected CaptchaYvesTester $tester;

    public function testAcceptsPayloadVerifiedAsValid(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(function (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool {
                return $captchaVerificationRequestTransfer->getPayload() === static::PAYLOAD
                    && $captchaVerificationRequestTransfer->getContext() === CaptchaYvesTester::CONTEXT;
            }))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));

        // Act
        $constraintViolationList = Validation::createValidator()->validate(
            static::PAYLOAD,
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaYvesTester::CONTEXT),
        );

        // Assert
        $this->assertCount(0, $constraintViolationList);
    }

    public function testAddsOneViolationPerVerificationMessage(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('verifyCaptcha')->willReturn(
            (new CaptchaVerificationResponseTransfer())
                ->setIsValid(false)
                ->setIsExpired(true)
                ->addMessage((new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED)),
        );

        // Act
        $constraintViolationList = Validation::createValidator()->validate(
            static::PAYLOAD,
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaYvesTester::CONTEXT),
        );

        // Assert
        $this->assertCount(1, $constraintViolationList);
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED, $constraintViolationList->get(0)->getMessage());
    }

    public function testAddsInvalidViolationWhenRejectionCarriesNoMessage(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('verifyCaptcha')->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(false));

        // Act
        $constraintViolationList = Validation::createValidator()->validate(
            static::PAYLOAD,
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaYvesTester::CONTEXT),
        );

        // Assert
        $this->assertCount(1, $constraintViolationList);
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID, $constraintViolationList->get(0)->getMessage());
    }

    /**
     * @dataProvider provideValuesWithoutPayload
     */
    public function testPassesNoPayloadToClientForMissingOrNonStringValue(mixed $value): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(function (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool {
                return $captchaVerificationRequestTransfer->getPayload() === null;
            }))
            ->willReturn(
                (new CaptchaVerificationResponseTransfer())
                    ->setIsValid(false)
                    ->addMessage((new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_REQUIRED)),
            );

        // Act
        $constraintViolationList = Validation::createValidator()->validate(
            $value,
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaYvesTester::CONTEXT),
        );

        // Assert
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_REQUIRED, $constraintViolationList->get(0)->getMessage());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideValuesWithoutPayload(): array
    {
        return [
            'missing value' => [null],
            'array value' => [[static::PAYLOAD]],
        ];
    }

    public function testSendsClientIpOfCurrentRequest(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(function (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool {
                return $captchaVerificationRequestTransfer->getIp() === CaptchaYvesTester::CLIENT_IP;
            }))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));

        // Act
        Validation::createValidator()->validate(
            static::PAYLOAD,
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaYvesTester::CONTEXT),
        );
    }

    public function testReadsPayloadFromResponseFieldOfProvider(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('findResponseFieldName')->willReturn(static::RESPONSE_FIELD_NAME);
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(function (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool {
                return $captchaVerificationRequestTransfer->getPayload() === static::PAYLOAD;
            }))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));
        $requestStack = $this->tester->createRequestStack([static::RESPONSE_FIELD_NAME => static::PAYLOAD]);

        // Act
        $constraintViolationList = Validation::createValidator()->validate(
            null,
            new CaptchaConstraint($captchaClientMock, $requestStack, CaptchaYvesTester::CONTEXT),
        );

        // Assert
        $this->assertCount(0, $constraintViolationList);
    }
}
