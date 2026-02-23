<?php

namespace Wscore\LeanValidator\Rule;

use Closure;

class Net
{
    /**
     * IP アドレス（v4, v6）を検証します。
     *
     * @param int $flags FILTER_FLAG_IPV4, FILTER_FLAG_IPV6, FILTER_FLAG_NO_PRIV_RANGE, FILTER_FLAG_NO_RES_RANGE
     */
    public static function ip(int $flags = 0): Closure
    {
        return function ($value) use ($flags) {
            return filter_var($value, FILTER_VALIDATE_IP, $flags) !== false;
        };
    }

    /**
     * IPv4 アドレスを検証します。
     */
    public static function ipv4(): Closure
    {
        return self::ip(FILTER_FLAG_IPV4);
    }

    /**
     * IPv6 アドレスを検証します。
     */
    public static function ipv6(): Closure
    {
        return self::ip(FILTER_FLAG_IPV6);
    }

    /**
     * MAC アドレスを検証します。
     */
    public static function mac(): Closure
    {
        return function ($value) {
            return filter_var($value, FILTER_VALIDATE_MAC) !== false;
        };
    }

    /**
     * UUID を検証します。
     */
    public static function uuid(): Closure
    {
        return function ($value) {
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
        };
    }

    /**
     * ドメイン名を検証します。
     */
    public static function domain(): Closure
    {
        return function ($value) {
            return filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
        };
    }
}
