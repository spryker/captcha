<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Form;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Spryker\Shared\Captcha\CaptchaConfig;
use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

/**
 * @method \Spryker\Yves\Captcha\CaptchaFactory getFactory()
 */
class CaptchaType extends AbstractType
{
    public const string OPTION_CAPTCHA_CONTEXT = CaptchaConfig::FORM_OPTION_CAPTCHA_CONTEXT;

    protected const string BLOCK_PREFIX = CaptchaConfig::FORM_TYPE_BLOCK_PREFIX;

    protected const string OPTION_MAPPED = 'mapped';

    protected const string OPTION_LABEL = 'label';

    protected const string OPTION_CONSTRAINTS = 'constraints';

    protected const string VIEW_VARIABLE_CAPTCHA_WIDGET = CaptchaConfig::FORM_VIEW_VARIABLE_CAPTCHA_WIDGET;

    protected const string VIEW_VARIABLE_CAPTCHA_MESSAGES = CaptchaConfig::FORM_VIEW_VARIABLE_CAPTCHA_MESSAGES;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(static::OPTION_CAPTCHA_CONTEXT);
        $resolver->setAllowedTypes(static::OPTION_CAPTCHA_CONTEXT, 'string');
        $resolver->setDefaults([
            static::OPTION_MAPPED => false,
            static::OPTION_LABEL => false,
            static::OPTION_CONSTRAINTS => [],
        ]);
        $resolver->addNormalizer(static::OPTION_CONSTRAINTS, function (Options $options, mixed $constraints): array {
            return $this->addCaptchaConstraint($constraints, $options[static::OPTION_CAPTCHA_CONTEXT]);
        });
    }

    /**
     * @param \Symfony\Component\Form\FormInterface<mixed> $form
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $captchaContext = $options[static::OPTION_CAPTCHA_CONTEXT];
        $captchaChallengeResponseTransfer = $this->getFactory()
            ->getCaptchaClient()
            ->createChallenge(
                (new CaptchaChallengeRequestTransfer())
                    ->setContext($captchaContext)
                    ->setChallengeUrl($this->getFactory()->createCaptchaChallengeUrlBuilder()->findChallengeUrl($captchaContext)),
            );

        $view->vars[static::VIEW_VARIABLE_CAPTCHA_WIDGET] = $captchaChallengeResponseTransfer->getWidget();
        $view->vars[static::VIEW_VARIABLE_CAPTCHA_MESSAGES] = $captchaChallengeResponseTransfer->getMessages();
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return static::BLOCK_PREFIX;
    }

    /**
     * @return array<\Symfony\Component\Validator\Constraint>
     */
    protected function addCaptchaConstraint(mixed $constraints, string $captchaContext): array
    {
        $constraints = $constraints instanceof Constraint ? [$constraints] : (array)$constraints;
        $constraints[] = $this->getFactory()->createCaptchaConstraint($captchaContext);

        return $constraints;
    }
}
