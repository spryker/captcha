<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Captcha;

use Codeception\Actor;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaWidgetTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use ReflectionClass;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use Spryker\Zed\Captcha\CaptchaDependencyProvider;
use Spryker\Zed\Captcha\Communication\Form\CaptchaType;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFunction;

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
class CaptchaCommunicationTester extends Actor
{
    use _generated\CaptchaCommunicationTesterActions;

    public const string FORM_NAME = 'auth';

    public const string CONTEXT = 'user';

    public const string CLIENT_IP = '192.0.2.10';

    public const string WIDGET_TAG_NAME = 'altcha-widget';

    public const string WIDGET_CHALLENGE = '{"parameters":{"algorithm":"PBKDF2/SHA-256"},"signature":"abc"}';

    public const string WIDGET_SCRIPT_ASSET_PATH = 'js/spryker-zed-captcha-altcha.js';

    public const string ASSETS_BASE_PATH = '/assets/';

    protected const string FORM_THEME_TEMPLATE_NAME = 'captcha.twig';

    protected const string BASE_FORM_LAYOUT_TEMPLATE_NAME = 'form_div_layout.html.twig';

    public function haveCaptchaTypeDependencies(CaptchaClientInterface $captchaClient): void
    {
        $this->setDependency(CaptchaDependencyProvider::CLIENT_CAPTCHA, $captchaClient);
        $this->setDependency(CaptchaDependencyProvider::SERVICE_REQUEST_STACK, $this->createRequestStack());
    }

    public function translate(string $glossaryKey, string $localeName): string
    {
        return $this->getLocator()->translator()->facade()->trans($glossaryKey, [], null, $localeName);
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
                    ->setLoaderTagName('captcha-altcha')
                    ->setScriptAssetPaths([static::WIDGET_SCRIPT_ASSET_PATH]),
            );
    }

    public function createUnavailableCaptchaChallengeResponse(): CaptchaChallengeResponseTransfer
    {
        return (new CaptchaChallengeResponseTransfer())
            ->setIsSuccessful(false)
            ->addMessage((new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE));
    }

    /**
     * @param array<string, mixed> $captchaFieldOptions
     */
    public function createLoginFormWithCaptchaField(array $captchaFieldOptions = []): FormInterface
    {
        return $this->createFormFactory()
            ->createNamedBuilder(static::FORM_NAME, FormType::class)
            ->add(CaptchaConfig::FORM_FIELD_NAME_CAPTCHA, CaptchaType::class, $captchaFieldOptions + [
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
            sprintf('%s/src/Spryker/Zed/Captcha/Presentation/Form/Type', $this->getModuleRootDirectory()),
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
        $twig->addFunction(new TwigFunction('assetsPath', static function (string $path): string {
            return static::ASSETS_BASE_PATH . $path;
        }));

        return $twig->createTemplate('{{ form_widget(form.captcha) }}')->render(['form' => $formView]);
    }

    protected function getModuleRootDirectory(): string
    {
        return dirname(__DIR__, 5);
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
