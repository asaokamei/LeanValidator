<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Rule\Net;

class NetRuleTest extends TestCase
{
    public function test_ip()
    {
        $rule = Net::ip();
        $this->assertTrue($rule('127.0.0.1'));
        $this->assertTrue($rule('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertFalse($rule('256.256.256.256'));
        $this->assertFalse($rule('not an ip'));
    }

    public function test_ipv4()
    {
        $rule = Net::ipv4();
        $this->assertTrue($rule('192.168.1.1'));
        $this->assertFalse($rule('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
    }

    public function test_ipv6()
    {
        $rule = Net::ipv6();
        $this->assertTrue($rule('::1'));
        $this->assertFalse($rule('127.0.0.1'));
    }

    public function test_mac()
    {
        $rule = Net::mac();
        $this->assertTrue($rule('01:23:45:67:89:ab'));
        $this->assertTrue($rule('01-23-45-67-89-ab'));
        $this->assertFalse($rule('01:23:45:67:89'));
    }

    public function test_uuid()
    {
        $rule = Net::uuid();
        $this->assertTrue($rule('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertTrue($rule('550E8400-E29B-41D4-A716-446655440000'));
        $this->assertFalse($rule('550e8400-e29b-41d4-a716-44665544000'));
        $this->assertFalse($rule('not-a-uuid'));
    }

    public function test_domain()
    {
        $rule = Net::domain();
        $this->assertTrue($rule('example.com'));
        $this->assertTrue($rule('localhost'));
        $this->assertFalse($rule('example..com'));
        $this->assertFalse($rule('-example.com'));
    }
}
