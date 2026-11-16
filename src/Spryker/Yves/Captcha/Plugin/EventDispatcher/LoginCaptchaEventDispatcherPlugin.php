<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Plugin\EventDispatcher;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\EventDispatcher\EventDispatcherInterface;
use Spryker\Shared\EventDispatcherExtension\Dependency\Plugin\EventDispatcherPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;

/**
 * @method \Spryker\Yves\Captcha\CaptchaFactory getFactory()
 * @method \Spryker\Yves\Captcha\CaptchaConfig getConfig()
 */
class LoginCaptchaEventDispatcherPlugin extends AbstractPlugin implements EventDispatcherPluginInterface
{
    /**
     * {@inheritDoc}
     * - Verifies the CAPTCHA of a submitted Storefront login form listed in `CaptchaConfig::getLoginForms()` (customer, agent)
     *   before the login blockers and the firewall handle the submission.
     * - Tells the customer and the agent form apart by the login-check route; a submission to another route is checked for both forms.
     * - Does nothing when CAPTCHA is disabled, or in the `after_failed_attempts` CAPTCHA mode while no `CaptchaRequirementPluginInterface` plugin requires it.
     * - Reads the payload from the CAPTCHA form field, or from the top-level parameter the provider plugin names.
     * - Rejects a submission without a valid CAPTCHA before it is counted as a failed login: stores the submitted email as the last
     *   username of the session, adds the provider messages as flash error messages and answers like the login failure handler
     *   (a JSON answer for the multi-factor authentication script, otherwise a redirect to the same-host referer or the login page).
     *
     * @api
     */
    public function extend(EventDispatcherInterface $eventDispatcher, ContainerInterface $container): EventDispatcherInterface
    {
        $eventDispatcher->addSubscriber($this->getFactory()->createLoginCaptchaEventSubscriber());

        return $eventDispatcher;
    }
}
