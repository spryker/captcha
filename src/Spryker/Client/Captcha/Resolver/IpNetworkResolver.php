<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Resolver;

/**
 * An IPv6 subscriber usually gets a whole /64 network, so a limit per IPv6 address would give one client 2^64 budgets:
 * the requests of an IPv6 address count for its /64 network, the requests of an IPv4 address for the address.
 */
class IpNetworkResolver implements IpNetworkResolverInterface
{
    protected const int IPV6_NETWORK_PREFIX_LENGTH_BYTES = 8;

    protected const int IPV6_ADDRESS_LENGTH_BYTES = 16;

    protected const int BITS_PER_BYTE = 8;

    protected const string IPV6_NETWORK_PATTERN = '%s/%d';

    protected const string IPV4_MAPPED_IPV6_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    protected const string NULL_BYTE = "\0";

    public function resolveNetwork(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return $ip;
        }

        $packedIp = (string)inet_pton($ip);

        if (str_starts_with($packedIp, static::IPV4_MAPPED_IPV6_PREFIX)) {
            return (string)inet_ntop(substr($packedIp, strlen(static::IPV4_MAPPED_IPV6_PREFIX)));
        }

        $packedNetwork = str_pad(
            substr($packedIp, 0, static::IPV6_NETWORK_PREFIX_LENGTH_BYTES),
            static::IPV6_ADDRESS_LENGTH_BYTES,
            static::NULL_BYTE,
        );

        return sprintf(
            static::IPV6_NETWORK_PATTERN,
            (string)inet_ntop($packedNetwork),
            static::IPV6_NETWORK_PREFIX_LENGTH_BYTES * static::BITS_PER_BYTE,
        );
    }
}
