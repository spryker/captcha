<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Plugin\SecurityMerchantPortalGui;

use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Zed\Captcha\Communication\Form\CaptchaType;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\SecurityMerchantPortalGuiExtension\Dependency\Plugin\MerchantPortalLoginFormExpanderPluginInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @method \Spryker\Zed\Captcha\Communication\CaptchaCommunicationFactory getFactory()
 * @method \Spryker\Zed\Captcha\CaptchaConfig getConfig()
 */
class MerchantPortalLoginFormCaptchaExpanderPlugin extends AbstractPlugin implements MerchantPortalLoginFormExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Adds the CAPTCHA field with the `merchant_portal_user_login` context to the Merchant Portal login form.
     * - Leaves loading the widget scripts to the Merchant Portal frontend build.
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
            ->expand($builder, SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_USER_LOGIN, [CaptchaType::OPTION_INCLUDE_SCRIPT_ASSETS => false]);
    }
}
