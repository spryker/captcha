<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Yves\Captcha;

use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaWidgetTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use ReflectionClass;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use Spryker\Yves\Captcha\CaptchaDependencyProvider;
use Spryker\Yves\Captcha\Form\CaptchaType;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class CaptchaYvesTester extends Actor
{
    use _generated\CaptchaYvesTesterActions;

    public const string FORM_NAME = 'loginForm';

    public const string CONTEXT = 'customer';

    public const string CHALLENGE_URL = '/DE/en/captcha/challenge?context=customer';

    public const string CLIENT_IP = '192.0.2.10';

    public const string WIDGET_TAG_NAME = 'altcha-widget';

    public const string WIDGET_LOADER_TAG_NAME = 'captcha-altcha';

    public const string WIDGET_CHALLENGE = '{"parameters":{"algorithm":"PBKDF2/SHA-256"},"signature":"abc"}';

    protected const string FORM_THEME_TEMPLATE_NAME = 'captcha.twig';

    protected const string BASE_FORM_LAYOUT_TEMPLATE_NAME = 'form_div_layout.html.twig';

    public function haveCaptchaTypeDependencies(CaptchaClientInterface $captchaClient, ?ChainRouterInterface $router = null): void
    {
        $this->setDependency(CaptchaDependencyProvider::CLIENT_CAPTCHA, $captchaClient);
        $this->setDependency(CaptchaDependencyProvider::SERVICE_REQUEST_STACK, $this->createRequestStack());
        $this->setDependency(CaptchaDependencyProvider::SERVICE_ROUTER, $router ?? $this->createRouterGeneratingChallengeUrl());
    }

    public function createRouterGeneratingChallengeUrl(): ChainRouterInterface
    {
        return Stub::makeEmpty(ChainRouterInterface::class, ['generate' => static::CHALLENGE_URL]);
    }

    public function createRouterWithoutChallengeRoute(): ChainRouterInterface
    {
        return Stub::makeEmpty(ChainRouterInterface::class, [
            'generate' => static function (): never {
                throw new RouteNotFoundException('Route "captcha-challenge" does not exist.');
            },
        ]);
    }

    public function createSuccessfulCaptchaChallengeResponse(): CaptchaChallengeResponseTransfer
    {
        return (new CaptchaChallengeResponseTransfer())
            ->setIsSuccessful(true)
            ->setWidget(
                (new CaptchaWidgetTransfer())
                    ->setTagName(static::WIDGET_TAG_NAME)
                    ->setAttributes(['challenge' => static::WIDGET_CHALLENGE])
                    ->setNameAttribute('name')
                    ->setLoaderTagName(static::WIDGET_LOADER_TAG_NAME)
                    ->setScriptAssetPaths(['js/spryker-zed-captcha-altcha.js']),
            );
    }

    public function createUnavailableCaptchaChallengeResponse(): CaptchaChallengeResponseTransfer
    {
        return (new CaptchaChallengeResponseTransfer())
            ->setIsSuccessful(false)
            ->addMessage((new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE));
    }

    public function createLoginFormWithCaptchaField(): FormInterface
    {
        return $this->createFormFactory()
            ->createNamedBuilder(static::FORM_NAME, FormType::class)
            ->add(CaptchaConfig::FORM_FIELD_NAME_CAPTCHA, CaptchaType::class, [
                CaptchaType::OPTION_CAPTCHA_CONTEXT => static::CONTEXT,
            ])
            ->getForm();
    }

    public function createFormFactory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
    }

    public function renderCaptchaWidget(FormView $formView): string
    {
        $twig = new Environment(new FilesystemLoader([
            sprintf('%s/src/Spryker/Yves/Captcha/Theme/default/resources/form', dirname(__DIR__, 5)),
            sprintf('%s/Resources/views/Form', dirname((string)(new ReflectionClass(FormExtension::class))->getFileName(), 2)),
        ]));
        $twigRendererEngine = new TwigRendererEngine([static::BASE_FORM_LAYOUT_TEMPLATE_NAME, static::FORM_THEME_TEMPLATE_NAME], $twig);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static function () use ($twigRendererEngine): FormRenderer {
                return new FormRenderer($twigRendererEngine);
            },
        ]));
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new TranslationExtension());

        return $twig->createTemplate('{{ form_widget(form.captcha) }}')->render(['form' => $formView]);
    }

    /**
     * @param array<string, mixed> $requestParameters
     */
    public function createRequestStack(array $requestParameters = []): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/login_check', Request::METHOD_POST, $requestParameters, [], [], ['REMOTE_ADDR' => static::CLIENT_IP]));

        return $requestStack;
    }
}
