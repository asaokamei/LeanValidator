<?php

namespace Wscore\LeanValidator\Rule;

use Closure;

class Net
{
    /**
     * IP address (v4 or v6).
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
     * IPv4 address.
     */
    public static function ipv4(): Closure
    {
        return self::ip(FILTER_FLAG_IPV4);
    }

    /**
     * Private IP address (v4 or v6).
     */
    public static function privateIp(): Closure
    {
        return self::ip(FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * IPv6 address.
     */
    public static function ipv6(): Closure
    {
        return self::ip(FILTER_FLAG_IPV6);
    }

    /**
     * MAC address.
     */
    public static function mac(): Closure
    {
        return function ($value) {
            return filter_var($value, FILTER_VALIDATE_MAC) !== false;
        };
    }

    /**
     * UUID.
     */
    public static function uuid(): Closure
    {
        return function ($value) {
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
        };
    }

    /**
     * URL.
     */
    public static function url(): Closure
    {
        return function ($value) {
            return filter_var($value, FILTER_VALIDATE_URL) !== false;
        };
    }

    /**
     * Domain name.
     */
    public static function domain(): Closure
    {
        return function ($value) {
            return filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
        };
    }
}
