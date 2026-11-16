<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication\Plugin\EventDispatcher;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\EventDispatcher\EventDispatcherInterface;
use Spryker\Shared\EventDispatcherExtension\Dependency\Plugin\EventDispatcherPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Spryker\Zed\Captcha\Communication\CaptchaCommunicationFactory getFactory()
 * @method \Spryker\Zed\Captcha\CaptchaConfig getConfig()
 */
class LoginCaptchaEventDispatcherPlugin extends AbstractPlugin implements EventDispatcherPluginInterface
{
    /**
     * {@inheritDoc}
     * - Verifies the CAPTCHA of a submitted login form listed in `CaptchaConfig::getLoginForms()` (Back Office, Merchant Portal,
     *   Merchant Portal agent) before the login blockers and the firewall handle the submission.
     * - Does nothing when CAPTCHA is disabled, or in the `after_failed_attempts` CAPTCHA mode while no `CaptchaRequirementPluginInterface` plugin requires it.
     * - Reads the payload from the CAPTCHA form field, or from the top-level parameter the provider plugin names.
     * - Rejects a submission without a valid CAPTCHA before it is counted as a failed login: stores the submitted username as the last
     *   username of the session, adds the provider messages as error messages and answers like the login failure handler of the form
     *   (a redirect to the login page, or a JSON answer for the Back Office multi-factor authentication script and the Merchant Portal ZedUi form).
     * - Register it in the event dispatcher plugin stacks of the Back Office and the Merchant Portal applications.
     *
     * @api
     */
    public function extend(EventDispatcherInterface $eventDispatcher, ContainerInterface $container): EventDispatcherInterface
    {
        $eventDispatcher->addSubscriber($this->getFactory()->createLoginCaptchaEventSubscriber());

        return $eventDispatcher;
    }
}
