<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Resolver;

use Generated\Shared\Transfer\CaptchaLoginFormTransfer;
use Spryker\Yves\Captcha\CaptchaConfig;
use Symfony\Component\HttpFoundation\Request;

/**
 * The customer and the agent login form post the same form name, so the login-check route tells them apart. Route names carry
 * the store and locale as prefixes (`DE_de_agent_login_check`), so a route matches a form when it equals the form's route name
 * or ends with `_` and it; the longest route name is tried first, because `agent_login_check` also ends with `login_check`.
 *
 * The login form authenticators accept the form on every route of their firewall, so a submission to any other route returns
 * every candidate form.
 */
class LoginFormResolver implements LoginFormResolverInterface
{
    protected const string ROUTE_KEY = '_route';

    protected const string ROUTE_NAME_SEPARATOR = '_';

    public function __construct(protected CaptchaConfig $captchaConfig)
    {
    }

    /**
     * @return list<\Generated\Shared\Transfer\CaptchaLoginFormTransfer>
     */
    public function resolveSubmittedLoginForms(Request $request): array
    {
        $submittedLoginFormTransfers = array_values(array_filter(
            $this->captchaConfig->getLoginForms(),
            fn (CaptchaLoginFormTransfer $captchaLoginFormTransfer): bool => is_array($request->request->all()[$captchaLoginFormTransfer->getFormNameOrFail()] ?? null),
        ));

        $routeName = $request->attributes->get(static::ROUTE_KEY);

        if (!is_string($routeName)) {
            return $submittedLoginFormTransfers;
        }

        $matchedLoginFormTransfer = $this->findLoginFormByRouteName($submittedLoginFormTransfers, $routeName);

        return $matchedLoginFormTransfer === null ? $submittedLoginFormTransfers : [$matchedLoginFormTransfer];
    }

    /**
     * @param list<\Generated\Shared\Transfer\CaptchaLoginFormTransfer> $captchaLoginFormTransfers
     */
    protected function findLoginFormByRouteName(array $captchaLoginFormTransfers, string $routeName): ?CaptchaLoginFormTransfer
    {
        usort(
            $captchaLoginFormTransfers,
            fn (CaptchaLoginFormTransfer $first, CaptchaLoginFormTransfer $second): int => mb_strlen((string)$second->getLoginCheckRouteName()) <=> mb_strlen((string)$first->getLoginCheckRouteName()),
        );

        foreach ($captchaLoginFormTransfers as $captchaLoginFormTransfer) {
            $loginCheckRouteName = (string)$captchaLoginFormTransfer->getLoginCheckRouteName();

            if ($routeName === $loginCheckRouteName || str_ends_with($routeName, static::ROUTE_NAME_SEPARATOR . $loginCheckRouteName)) {
                return $captchaLoginFormTransfer;
            }
        }

        return null;
    }
}
