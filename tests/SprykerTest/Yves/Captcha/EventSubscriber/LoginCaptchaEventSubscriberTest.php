<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Yves\Captcha\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface;
use Spryker\Yves\Captcha\CaptchaConfig;
use Spryker\Yves\Captcha\CaptchaDependencyProvider;
use Spryker\Yves\Captcha\CaptchaFactory;
use Spryker\Yves\Messenger\FlashMessenger\FlashMessengerInterface;
use SprykerTest\Yves\Captcha\CaptchaYvesTester;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Yves
 * @group Captcha
 * @group EventSubscriber
 * @group LoginCaptchaEventSubscriberTest
 * Add your own group annotations below this line
 */
class LoginCaptchaEventSubscriberTest extends Unit
{
    /**
     * @uses \SprykerShop\Yves\CustomerPage\Form\LoginForm::FORM_NAME
     */
    protected const string LOGIN_FORM_NAME = 'loginForm';

    protected const string EMAIL = 'customer@spryker.com';

    protected const string PAYLOAD = 'solved-captcha-payload';

    protected const string IP = '10.0.0.1';

    protected const string HOST = 'yves.de.spryker.local';

    protected const int LOGIN_BLOCKER_PRIORITY = 9;

    protected CaptchaYvesTester $tester;

    /**
     * @dataProvider provideLoginCheckRoutes
     */
    public function testSubmissionIsVerifiedWithTheContextOfItsLoginCheckRoute(string $routeName, string $expectedContext): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(true);

        // Assert
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(fn (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool => $captchaVerificationRequestTransfer->getContext() === $expectedContext
                && $captchaVerificationRequestTransfer->getPayload() === static::PAYLOAD
                && $captchaVerificationRequestTransfer->getIp() === static::IP))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));

        // Act
        $this->dispatch($this->createRequestEvent($this->createLoginRequest($routeName)), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideLoginCheckRoutes(): array
    {
        return [
            'customer' => ['login_check', SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN],
            'customer with locale' => ['de_login_check', SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN],
            'customer with store and locale' => ['DE_de_login_check', SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN],
            'agent' => ['agent_login_check', SharedCaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN],
            'agent with store and locale' => ['DE_de_agent_login_check', SharedCaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN],
        ];
    }

    public function testSubmissionWithoutAValidCaptchaIsRedirectedToTheLoginPageAndTheEmailRemembered(): void
    {
        // Arrange
        $request = $this->createLoginRequest('DE_de_agent_login_check');
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertSame('/agent/login', $event->getResponse()->getTargetUrl());
        $this->assertSame(static::EMAIL, $request->getSession()->get(SecurityRequestAttributes::LAST_USERNAME));
    }

    public function testRejectionRedirectsBackToASameHostReferer(): void
    {
        // Arrange
        $request = $this->createLoginRequest('login_check');
        $request->headers->set('Referer', sprintf('https://%s/checkout/customer', static::HOST));
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false));

        // Assert
        $this->assertSame(sprintf('https://%s/checkout/customer', static::HOST), $event->getResponse()->getTargetUrl());
    }

    public function testRejectionIgnoresAForeignReferer(): void
    {
        // Arrange
        $request = $this->createLoginRequest('login_check');
        $request->headers->set('Referer', 'https://attacker.example/login');
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false));

        // Assert
        $this->assertSame('/login', $event->getResponse()->getTargetUrl());
    }

    public function testRejectionIgnoresASameHostRefererWithoutAWebScheme(): void
    {
        // Arrange
        $request = $this->createLoginRequest('login_check');
        $request->headers->set('Referer', sprintf('javascript://%s/%%0Aalert(1)', static::HOST));
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false));

        // Assert
        $this->assertSame('/login', $event->getResponse()->getTargetUrl());
    }

    public function testAjaxSubmissionIsAnsweredForTheMultiFactorAuthenticationScript(): void
    {
        // Arrange
        $request = $this->createLoginRequest('login_check');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false));

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $event->getResponse());
        $this->assertSame(['requires_additional_auth' => false], json_decode((string)$event->getResponse()->getContent(), true));
    }

    public function testRejectedSubmissionNeverReachesTheLoginBlocker(): void
    {
        // Arrange
        $isLoginBlockerReached = false;

        // Act
        $this->dispatch($this->createRequestEvent($this->createLoginRequest('login_check')), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false), function () use (&$isLoginBlockerReached): void {
            $isLoginBlockerReached = true;
        });

        // Assert
        $this->assertFalse($isLoginBlockerReached);
    }

    public function testSubmissionWithAValidCaptchaContinuesToTheLoginBlocker(): void
    {
        // Arrange
        $isLoginBlockerReached = false;
        $event = $this->createRequestEvent($this->createLoginRequest('login_check'));

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(true), function () use (&$isLoginBlockerReached): void {
            $isLoginBlockerReached = true;
        });

        // Assert
        $this->assertNull($event->getResponse());
        $this->assertTrue($isLoginBlockerReached);
    }

    /**
     * The login form authenticators accept the form on every route of their firewall, so a submission to another route is
     * checked for both forms in the `after_failed_attempts` mode.
     */
    public function testSubmissionToAnotherRouteAsksThePluginsForBothForms(): void
    {
        // Arrange
        $askedContexts = [];
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $captchaRequirementPluginMock->method('isCaptchaRequired')->willReturnCallback(
            function (CaptchaRequirementRequestTransfer $captchaRequirementRequestTransfer) use (&$askedContexts): bool {
                $askedContexts[] = $captchaRequirementRequestTransfer->getContext();

                return $captchaRequirementRequestTransfer->getContext() === SharedCaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN;
            },
        );
        $event = $this->createRequestEvent($this->createLoginRequest('home'));

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, $this->createCaptchaClientMock(false), null, [$captchaRequirementPluginMock]);

        // Assert
        $this->assertSame([SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN, SharedCaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN], $askedContexts);
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testAfterFailedAttemptsModeSkipsTheVerificationWhileNoPluginRequiresACaptcha(): void
    {
        // Arrange
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $captchaRequirementPluginMock->method('isCaptchaRequired')->willReturn(false);
        $captchaClientMock = $this->createCaptchaClientMock(false);

        // Assert
        $captchaClientMock->expects($this->never())->method('verifyCaptcha');

        // Act
        $this->dispatch($this->createRequestEvent($this->createLoginRequest('login_check')), SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, $captchaClientMock, null, [$captchaRequirementPluginMock]);
    }

    public function testNothingIsVerifiedWhenCaptchaIsDisabled(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('isCaptchaEnabled')->willReturn(false);

        // Assert
        $captchaClientMock->expects($this->never())->method('verifyCaptcha');

        // Act
        $this->dispatch($this->createRequestEvent($this->createLoginRequest('login_check')), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
    }

    public function testGetRequestAndOtherFormsAreIgnored(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(false);
        $getRequest = $this->createLoginRequest('login_check');
        $getRequest->setMethod(Request::METHOD_GET);
        $otherFormRequest = Request::create('/register', Request::METHOD_POST, ['registerForm' => ['email' => static::EMAIL]]);

        // Assert
        $captchaClientMock->expects($this->never())->method('verifyCaptcha');

        // Act
        $this->dispatch($this->createRequestEvent($getRequest), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
        $this->dispatch($this->createRequestEvent($otherFormRequest), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
    }

    public function testPayloadIsReadFromTheResponseFieldOfTheProvider(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(true, 'g-recaptcha-response');
        $request = $this->createLoginRequest('login_check', null);
        $request->request->set('g-recaptcha-response', static::PAYLOAD);

        // Assert
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(fn (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool => $captchaVerificationRequestTransfer->getPayload() === static::PAYLOAD))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));

        // Act
        $this->dispatch($this->createRequestEvent($request), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
    }

    public function testSubmissionToAnotherRouteIsRejectedWithoutVerifyingTheTokenWhenACaptchaIsRequired(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(true);
        $event = $this->createRequestEvent($this->createLoginRequest('home'));

        // Expect
        $captchaClientMock->expects($this->never())->method('verifyCaptcha');

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertSame(static::EMAIL, $event->getRequest()->getSession()->get(SecurityRequestAttributes::LAST_USERNAME));
    }

    public function testSubmissionToAnotherRouteContinuesWhenNoFormRequiresACaptcha(): void
    {
        // Arrange
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $captchaRequirementPluginMock->method('isCaptchaRequired')->willReturn(false);
        $event = $this->createRequestEvent($this->createLoginRequest('home'));

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, $this->createCaptchaClientMock(false), null, [$captchaRequirementPluginMock]);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testCaptchaModeIsNotReadForARequestThatSubmitsNoLoginForm(): void
    {
        // Arrange
        $captchaConfigMock = $this->createMock(CaptchaConfig::class);
        $captchaConfigMock->method('getLoginForms')->willReturn((new CaptchaConfig())->getLoginForms());
        $getRequest = $this->createLoginRequest('login_check');
        $getRequest->setMethod(Request::METHOD_GET);
        $otherFormRequest = Request::create('/register', Request::METHOD_POST, ['registerForm' => ['email' => static::EMAIL]]);

        // Expect
        $captchaConfigMock->expects($this->never())->method('getCaptchaMode');

        // Act
        $this->dispatch($this->createRequestEvent($getRequest), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false), null, [], $captchaConfigMock);
        $this->dispatch($this->createRequestEvent($otherFormRequest), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false), null, [], $captchaConfigMock);
    }

    /**
     * @param list<\Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface> $captchaRequirementPlugins
     */
    protected function dispatch(
        RequestEvent $event,
        string $captchaMode,
        CaptchaClientInterface $captchaClient,
        ?callable $loginBlockerListener = null,
        array $captchaRequirementPlugins = [],
        ?CaptchaConfig $captchaConfig = null
    ): void {
        $this->tester->setDependency(CaptchaDependencyProvider::CLIENT_CAPTCHA, $captchaClient);
        $this->tester->setDependency(CaptchaDependencyProvider::SERVICE_FLASH_MESSENGER, $this->createMock(FlashMessengerInterface::class));
        $this->tester->setDependency(CaptchaDependencyProvider::PLUGINS_CAPTCHA_REQUIREMENT, $captchaRequirementPlugins);

        $captchaFactory = new CaptchaFactory();
        $captchaFactory->setConfig($captchaConfig ?? $this->createConfiguredMock(CaptchaConfig::class, [
            'getCaptchaMode' => $captchaMode,
            'getLoginForms' => (new CaptchaConfig())->getLoginForms(),
        ]));

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($captchaFactory->createLoginCaptchaEventSubscriber());

        if ($loginBlockerListener !== null) {
            $eventDispatcher->addListener(KernelEvents::REQUEST, $loginBlockerListener, static::LOGIN_BLOCKER_PRIORITY);
        }

        $eventDispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\Captcha\CaptchaClientInterface
     */
    protected function createCaptchaClientMock(bool $isCaptchaValid, ?string $responseFieldName = null): CaptchaClientInterface
    {
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('isCaptchaEnabled')->willReturn(true);
        $captchaClientMock->method('findResponseFieldName')->willReturn($responseFieldName);
        $captchaClientMock->method('verifyCaptcha')->willReturn(
            (new CaptchaVerificationResponseTransfer())
                ->setIsValid($isCaptchaValid)
                ->addMessage((new MessageTransfer())->setValue(SharedCaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID)),
        );

        return $captchaClientMock;
    }

    protected function createLoginRequest(string $routeName, ?string $payload = self::PAYLOAD): Request
    {
        $loginForm = ['email' => static::EMAIL, 'password' => 'secret'];

        if ($payload !== null) {
            $loginForm[SharedCaptchaConfig::FORM_FIELD_NAME_CAPTCHA] = $payload;
        }

        $request = Request::create(sprintf('https://%s/login_check', static::HOST), Request::METHOD_POST, [static::LOGIN_FORM_NAME => $loginForm], [], [], ['REMOTE_ADDR' => static::IP]);
        $request->attributes->set('_route', $routeName);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    protected function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
