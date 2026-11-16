<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Plugin\AgentPage;

use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerShop\Yves\AgentPageExtension\Dependency\Plugin\AgentLoginFormExpanderPluginInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @method \Spryker\Yves\Captcha\CaptchaFactory getFactory()
 * @method \Spryker\Yves\Captcha\CaptchaConfig getConfig()
 */
class AgentLoginFormCaptchaExpanderPlugin extends AbstractPlugin implements AgentLoginFormExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Adds the CAPTCHA field with the `storefront_agent_login` context to the Storefront agent login form.
     * - Adds nothing when CAPTCHA is disabled (Back Office setting `security:captcha:login:is_enabled`).
     * - In the `always` CAPTCHA mode, adds the field on every rendering of the form.
     * - In the `after_failed_attempts` CAPTCHA mode, adds the field only while a `CaptchaRequirementPluginInterface` plugin requires it,
     *   asked with the last login identifier of the session and the client IP.
     *
     * @api
     *
     * @param array<string, mixed> $options
     */
    public function expand(FormBuilderInterface $builder, array $options): FormBuilderInterface
    {
        return $this->getFactory()
            ->createLoginFormCaptchaExpander()
            ->expand($builder, SharedCaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN);
    }
}
