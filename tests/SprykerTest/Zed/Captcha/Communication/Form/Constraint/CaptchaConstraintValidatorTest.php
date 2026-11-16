<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Captcha\Communication\Form\Constraint;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use Spryker\Zed\Captcha\Communication\Form\Constraint\CaptchaConstraint;
use SprykerTest\Zed\Captcha\CaptchaCommunicationTester;
use Symfony\Component\Validator\Validation;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Captcha
 * @group Communication
 * @group Form
 * @group Constraint
 * @group CaptchaConstraintValidatorTest
 * Add your own group annotations below this line
 */
class CaptchaConstraintValidatorTest extends Unit
{
    protected const string PAYLOAD = 'solved-payload';

    protected const string RESPONSE_FIELD_NAME = 'g-recaptcha-response';

    protected const string LOCALE_EN_US = 'en_US';

    protected const string LOCALE_DE_DE = 'de_DE';

    protected CaptchaCommunicationTester $tester;

    public function testAcceptsPayloadVerifiedAsValid(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(function (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool {
                return $captchaVerificationRequestTransfer->getPayload() === static::PAYLOAD
                    && $captchaVerificationRequestTransfer->getContext() === CaptchaCommunicationTester::CONTEXT;
            }))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));

        // Act
        $constraintViolationList = Validation::createValidator()->validate(
            static::PAYLOAD,
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaCommunicationTester::CONTEXT),
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
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaCommunicationTester::CONTEXT),
        );

        // Assert
        $this->assertCount(1, $constraintViolationList);
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED, $constraintViolationList->get(0)->getMessage());
    }

    /**
     * A provider that rejects without saying why must still fail validation: an invalid result without a violation
     * would let the form pass.
     */
    public function testAddsInvalidViolationWhenRejectionCarriesNoMessage(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('verifyCaptcha')->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(false));

        // Act
        $constraintViolationList = Validation::createValidator()->validate(
            static::PAYLOAD,
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaCommunicationTester::CONTEXT),
        );

        // Assert
        $this->assertCount(1, $constraintViolationList);
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID, $constraintViolationList->get(0)->getMessage());
    }

    public function testPassesNoPayloadToClientForMissingValue(): void
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
            null,
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaCommunicationTester::CONTEXT),
        );

        // Assert
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_REQUIRED, $constraintViolationList->get(0)->getMessage());
    }

    /**
     * @dataProvider provideTranslatedCaptchaMessages
     */
    public function testModuleTranslationFilesTranslateEveryCaptchaMessage(string $localeName, string $glossaryKey, string $expectedTranslation): void
    {
        // Arrange
        $this->tester->haveTranslatorWithModuleTranslationFiles($localeName);

        // Act
        $translation = $this->tester->translate($glossaryKey, $localeName);

        // Assert
        $this->assertSame($expectedTranslation, $translation);
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideTranslatedCaptchaMessages(): array
    {
        return [
            'en_US required' => [static::LOCALE_EN_US, CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_REQUIRED, 'Please complete the security check.'],
            'en_US invalid' => [static::LOCALE_EN_US, CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID, 'The security check could not be verified. Please try again.'],
            'en_US expired' => [static::LOCALE_EN_US, CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED, 'The security check has expired. Please try again.'],
            'en_US unavailable' => [static::LOCALE_EN_US, CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE, 'The security check is currently unavailable. Please try again later.'],
            'en_US noscript' => [static::LOCALE_EN_US, 'captcha.noscript', 'Please enable JavaScript to complete the security check.'],
            'de_DE required' => [static::LOCALE_DE_DE, CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_REQUIRED, 'Bitte schließen Sie die Sicherheitsprüfung ab.'],
            'de_DE invalid' => [static::LOCALE_DE_DE, CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID, 'Die Sicherheitsprüfung konnte nicht bestätigt werden. Bitte versuchen Sie es erneut.'],
            'de_DE expired' => [static::LOCALE_DE_DE, CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED, 'Die Sicherheitsprüfung ist abgelaufen. Bitte versuchen Sie es erneut.'],
            'de_DE unavailable' => [static::LOCALE_DE_DE, CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE, 'Die Sicherheitsprüfung ist derzeit nicht verfügbar. Bitte versuchen Sie es später erneut.'],
            'de_DE noscript' => [static::LOCALE_DE_DE, 'captcha.noscript', 'Bitte aktivieren Sie JavaScript, um die Sicherheitsprüfung abzuschließen.'],
        ];
    }

    public function testSendsClientIpOfCurrentRequest(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(function (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool {
                return $captchaVerificationRequestTransfer->getIp() === CaptchaCommunicationTester::CLIENT_IP;
            }))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));

        // Act
        Validation::createValidator()->validate(
            static::PAYLOAD,
            new CaptchaConstraint($captchaClientMock, $this->tester->createRequestStack(), CaptchaCommunicationTester::CONTEXT),
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
            new CaptchaConstraint($captchaClientMock, $requestStack, CaptchaCommunicationTester::CONTEXT),
        );

        // Assert
        $this->assertCount(0, $constraintViolationList);
    }
}
