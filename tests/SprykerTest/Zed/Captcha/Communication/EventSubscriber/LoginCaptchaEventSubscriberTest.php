<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Captcha\Communication\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaLoginFormTransfer;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\ZedUiFormResponseTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface;
use Spryker\Shared\ZedUi\Configuration\ZedUiFormResponseBuilderInterface;
use Spryker\Shared\ZedUi\ZedUiFactoryInterface;
use Spryker\Zed\Captcha\CaptchaConfig;
use Spryker\Zed\Captcha\CaptchaDependencyProvider;
use Spryker\Zed\Captcha\Communication\CaptchaCommunicationFactory;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;
use SprykerTest\Zed\Captcha\CaptchaCommunicationTester;
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
 * @group Zed
 * @group Captcha
 * @group Communication
 * @group EventSubscriber
 * @group LoginCaptchaEventSubscriberTest
 * Add your own group annotations below this line
 */
class LoginCaptchaEventSubscriberTest extends Unit
{
    /**
     * @uses \Spryker\Zed\SecurityGui\Communication\Form\LoginForm::FORM_NAME
     */
    protected const string BACKOFFICE_FORM_NAME = 'auth';

    /**
     * @uses \Spryker\Zed\SecurityMerchantPortalGui\Communication\Form\MerchantLoginForm::FORM_NAME
     */
    protected const string MERCHANT_PORTAL_FORM_NAME = 'security-merchant-portal-gui';

    /**
     * @uses \Spryker\Zed\AgentSecurityMerchantPortalGui\Communication\Form\AgentMerchantLoginForm::FORM_NAME
     */
    protected const string MERCHANT_PORTAL_AGENT_FORM_NAME = 'agent-security-merchant-portal-gui';

    protected const string PROJECT_LOGIN_FORM_NAME = 'project-merchant-portal-gui';

    protected const string PROJECT_LOGIN_CONTEXT = 'project_merchant_portal_login';

    protected const string USERNAME = 'admin@spryker.com';

    protected const string PAYLOAD = 'solved-captcha-payload';

    protected const string IP = '10.0.0.1';

    protected const string RESPONSE_FIELD_NAME = 'g-recaptcha-response';

    /**
     * Priority of the login blocker subscribers, which count the attempt.
     */
    protected const int LOGIN_BLOCKER_PRIORITY = 9;

    protected CaptchaCommunicationTester $tester;

    public function testSubmissionWithoutAValidCaptchaIsRejectedAndTheUsernameRemembered(): void
    {
        // Arrange
        $request = $this->createLoginRequest(static::BACKOFFICE_FORM_NAME);
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertSame('/security-gui/login', $event->getResponse()->getTargetUrl());
        $this->assertSame(static::USERNAME, $request->getSession()->get(SecurityRequestAttributes::LAST_USERNAME));
    }

    /**
     * The rejection sets the response, which stops the event before the login blockers (priority 9) count the attempt.
     */
    public function testRejectedSubmissionNeverReachesTheLoginBlocker(): void
    {
        // Arrange
        $isLoginBlockerReached = false;

        // Act
        $this->dispatch(
            $this->createRequestEvent($this->createLoginRequest(static::BACKOFFICE_FORM_NAME)),
            SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS,
            $this->createCaptchaClientMock(false),
            function () use (&$isLoginBlockerReached): void {
                $isLoginBlockerReached = true;
            },
        );

        // Assert
        $this->assertFalse($isLoginBlockerReached);
    }

    public function testSubmissionWithAValidCaptchaContinuesToTheLoginBlocker(): void
    {
        // Arrange
        $isLoginBlockerReached = false;
        $event = $this->createRequestEvent($this->createLoginRequest(static::BACKOFFICE_FORM_NAME));

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(true), function () use (&$isLoginBlockerReached): void {
            $isLoginBlockerReached = true;
        });

        // Assert
        $this->assertNull($event->getResponse());
        $this->assertTrue($isLoginBlockerReached);
    }

    public function testEachLoginFormIsVerifiedWithItsOwnContext(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(true);

        // Assert
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(fn (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool => $captchaVerificationRequestTransfer->getContext() === SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_AGENT_LOGIN
                && $captchaVerificationRequestTransfer->getPayload() === static::PAYLOAD
                && $captchaVerificationRequestTransfer->getIp() === static::IP))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));

        // Act
        $this->dispatch($this->createRequestEvent($this->createLoginRequest(static::MERCHANT_PORTAL_AGENT_FORM_NAME)), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
    }

    public function testPayloadIsReadFromTheResponseFieldOfTheProvider(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(true, static::RESPONSE_FIELD_NAME);
        $request = $this->createLoginRequest(static::BACKOFFICE_FORM_NAME, null);
        $request->request->set(static::RESPONSE_FIELD_NAME, static::PAYLOAD);

        // Assert
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(fn (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool => $captchaVerificationRequestTransfer->getPayload() === static::PAYLOAD))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));

        // Act
        $this->dispatch($this->createRequestEvent($request), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
    }

    public function testAjaxBackOfficeSubmissionIsAnsweredForTheMultiFactorAuthenticationScript(): void
    {
        // Arrange
        $request = $this->createLoginRequest(static::BACKOFFICE_FORM_NAME);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false));

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $event->getResponse());
        $this->assertSame(['requires_additional_auth' => false], json_decode((string)$event->getResponse()->getContent(), true));
    }

    public function testJsonMerchantPortalSubmissionIsAnsweredWithARedirectAction(): void
    {
        // Arrange
        $request = $this->createLoginRequest(static::MERCHANT_PORTAL_FORM_NAME);
        $request->headers->set('Accept', 'application/json');
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false), null, $this->createZedUiFactoryMock());

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $event->getResponse());
    }

    public function testMerchantPortalRejectionFallsBackToARedirectWithoutAZedUiFactory(): void
    {
        // Arrange
        $request = $this->createLoginRequest(static::MERCHANT_PORTAL_FORM_NAME);
        $request->headers->set('Accept', 'application/json');
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertSame('/security-merchant-portal-gui/login', $event->getResponse()->getTargetUrl());
    }

    public function testProjectLoginFormIsRejectedWithTheResponseTypeOfItsConfiguration(): void
    {
        // Arrange
        $captchaConfigMock = $this->createConfiguredMock(CaptchaConfig::class, [
            'getCaptchaMode' => SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS,
            'getLoginForms' => [
                (new CaptchaLoginFormTransfer())
                    ->setFormName(static::PROJECT_LOGIN_FORM_NAME)
                    ->setContext(static::PROJECT_LOGIN_CONTEXT)
                    ->setUsernameFieldName('username')
                    ->setLoginUrl('/project-merchant-portal-gui/login')
                    ->setRejectionResponseType(CaptchaConfig::REJECTION_RESPONSE_TYPE_ZED_UI),
            ],
        ]);
        $request = $this->createLoginRequest(static::PROJECT_LOGIN_FORM_NAME);
        $request->headers->set('Accept', 'application/json');
        $event = $this->createRequestEvent($request);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false), null, $this->createZedUiFactoryMock(), [], $captchaConfigMock);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $event->getResponse());
    }

    public function testNothingIsVerifiedWhenCaptchaIsDisabled(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('isCaptchaEnabled')->willReturn(false);
        $event = $this->createRequestEvent($this->createLoginRequest(static::BACKOFFICE_FORM_NAME));

        // Assert
        $captchaClientMock->expects($this->never())->method('verifyCaptcha');

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
    }

    public function testAfterFailedAttemptsModeSkipsTheVerificationWhileNoPluginRequiresACaptcha(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(false);
        $event = $this->createRequestEvent($this->createLoginRequest(static::BACKOFFICE_FORM_NAME));

        // Assert
        $captchaClientMock->expects($this->never())->method('verifyCaptcha');

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, $captchaClientMock, null, null, [$this->createCaptchaRequirementPlugin(false)]);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testAfterFailedAttemptsModeAsksThePluginsWithTheSubmittedUsernameAndVerifies(): void
    {
        // Arrange
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $event = $this->createRequestEvent($this->createLoginRequest(static::BACKOFFICE_FORM_NAME));

        // Assert
        $captchaRequirementPluginMock->expects($this->once())
            ->method('isCaptchaRequired')
            ->with($this->callback(fn (CaptchaRequirementRequestTransfer $captchaRequirementRequestTransfer): bool => $captchaRequirementRequestTransfer->getContext() === SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN
                && $captchaRequirementRequestTransfer->getLoginIdentifier() === static::USERNAME
                && $captchaRequirementRequestTransfer->getIp() === static::IP))
            ->willReturn(true);

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, $this->createCaptchaClientMock(false), null, null, [$captchaRequirementPluginMock]);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    /**
     * No browser submits two login forms at once. Choosing one of them would let the other form's authenticator run without
     * the CAPTCHA its own context requires.
     */
    public function testSubmissionCarryingTwoLoginFormsIsRejectedWithoutAskingThePlugins(): void
    {
        // Arrange
        $isLoginBlockerReached = false;
        $request = $this->createLoginRequest(static::BACKOFFICE_FORM_NAME);
        $request->request->set(static::MERCHANT_PORTAL_FORM_NAME, ['username' => static::USERNAME]);
        $event = $this->createRequestEvent($request);
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $captchaClientMock = $this->createCaptchaClientMock(true);

        // Assert
        $captchaRequirementPluginMock->expects($this->never())->method('isCaptchaRequired');
        $captchaClientMock->expects($this->never())->method('verifyCaptcha');

        // Act
        $this->dispatch($event, SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, $captchaClientMock, function () use (&$isLoginBlockerReached): void {
            $isLoginBlockerReached = true;
        }, null, [$captchaRequirementPluginMock]);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertFalse($isLoginBlockerReached);
    }

    public function testGetRequestIsIgnored(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(false);
        $request = $this->createLoginRequest(static::BACKOFFICE_FORM_NAME);
        $request->setMethod(Request::METHOD_GET);

        // Assert
        $captchaClientMock->expects($this->never())->method('verifyCaptcha');

        // Act
        $this->dispatch($this->createRequestEvent($request), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
    }

    public function testSubmissionOfAnotherFormIsIgnored(): void
    {
        // Arrange
        $captchaClientMock = $this->createCaptchaClientMock(false);
        $request = $this->createLoginRequest('product_form');

        // Assert
        $captchaClientMock->expects($this->never())->method('verifyCaptcha');

        // Act
        $this->dispatch($this->createRequestEvent($request), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $captchaClientMock);
    }

    public function testCaptchaModeIsNotReadForARequestThatSubmitsNoLoginForm(): void
    {
        // Arrange
        $captchaConfigMock = $this->createMock(CaptchaConfig::class);
        $captchaConfigMock->method('getLoginForms')->willReturn((new CaptchaConfig())->getLoginForms());
        $getRequest = $this->createLoginRequest(static::BACKOFFICE_FORM_NAME);
        $getRequest->setMethod(Request::METHOD_GET);
        $otherFormRequest = Request::create('/user/edit', Request::METHOD_POST, ['user' => ['username' => 'admin@spryker.com']]);

        // Expect
        $captchaConfigMock->expects($this->never())->method('getCaptchaMode');

        // Act
        $this->dispatch($this->createRequestEvent($getRequest), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false), null, null, [], $captchaConfigMock);
        $this->dispatch($this->createRequestEvent($otherFormRequest), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, $this->createCaptchaClientMock(false), null, null, [], $captchaConfigMock);
    }

    /**
     * @param list<\Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface> $captchaRequirementPlugins
     */
    protected function dispatch(
        RequestEvent $event,
        string $captchaMode,
        CaptchaClientInterface $captchaClient,
        ?callable $loginBlockerListener = null,
        ?ZedUiFactoryInterface $zedUiFactory = null,
        array $captchaRequirementPlugins = [],
        ?CaptchaConfig $captchaConfig = null
    ): void {
        $this->tester->setDependency(CaptchaDependencyProvider::CLIENT_CAPTCHA, $captchaClient);
        $this->tester->setDependency(CaptchaDependencyProvider::FACADE_MESSENGER, $this->createMock(MessengerFacadeInterface::class));
        $this->tester->setDependency(CaptchaDependencyProvider::SERVICE_ZED_UI_FACTORY, $zedUiFactory);
        $this->tester->setDependency(CaptchaDependencyProvider::PLUGINS_CAPTCHA_REQUIREMENT, $captchaRequirementPlugins);

        $captchaCommunicationFactory = new CaptchaCommunicationFactory();
        $captchaCommunicationFactory->setConfig($captchaConfig ?? $this->createConfiguredMock(CaptchaConfig::class, [
            'getCaptchaMode' => $captchaMode,
            'getLoginForms' => (new CaptchaConfig())->getLoginForms(),
        ]));

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($captchaCommunicationFactory->createLoginCaptchaEventSubscriber());

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

    protected function createCaptchaRequirementPlugin(bool $isCaptchaRequired): CaptchaRequirementPluginInterface
    {
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $captchaRequirementPluginMock->method('isCaptchaRequired')->willReturn($isCaptchaRequired);

        return $captchaRequirementPluginMock;
    }

    protected function createZedUiFactoryMock(): ZedUiFactoryInterface
    {
        $zedUiFormResponseBuilderMock = $this->createMock(ZedUiFormResponseBuilderInterface::class);
        $zedUiFormResponseBuilderMock->method('addActionRedirect')->willReturnSelf();
        $zedUiFormResponseBuilderMock->method('createResponse')->willReturn(new ZedUiFormResponseTransfer());

        $zedUiFactoryMock = $this->createMock(ZedUiFactoryInterface::class);
        $zedUiFactoryMock->method('createZedUiFormResponseBuilder')->willReturn($zedUiFormResponseBuilderMock);

        return $zedUiFactoryMock;
    }

    protected function createLoginRequest(string $formName, ?string $payload = self::PAYLOAD): Request
    {
        $loginForm = ['username' => static::USERNAME];

        if ($payload !== null) {
            $loginForm[SharedCaptchaConfig::FORM_FIELD_NAME_CAPTCHA] = $payload;
        }

        $request = Request::create('/login_check', Request::METHOD_POST, [$formName => $loginForm], [], [], ['REMOTE_ADDR' => static::IP]);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    protected function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
