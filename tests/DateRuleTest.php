<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Rule\Date;

class DateRuleTest extends TestCase
{
    public function testHtmlDate()
    {
        $rule = Date::htmlDate();
        $this->assertTrue($rule('2023-10-01'));
        $this->assertFalse($rule('2023-13-01'));
        $this->assertFalse($rule('01/10/2023'));
        $this->assertFalse($rule(123));
    }

    public function testHtmlMonth()
    {
        $rule = Date::htmlMonth();
        $this->assertTrue($rule('2023-10'));
        $this->assertFalse($rule('2023-13'));
        $this->assertFalse($rule('2023-10-01'));
    }

    public function testHtmlTime()
    {
        $rule = Date::htmlTime();
        $this->assertTrue($rule('12:34'));
        $this->assertTrue($rule('12:34:56'));
        $this->assertFalse($rule('25:00'));
        $this->assertFalse($rule('12:60'));
    }

    public function testHtmlDateTimeLocal()
    {
        $rule = Date::htmlDateTimeLocal();
        $this->assertTrue($rule('2023-10-01T12:34'));
        $this->assertTrue($rule('2023-10-01T12:34:56'));
        $this->assertFalse($rule('2023-10-01 12:34'));
    }

    public function testHtmlWeek()
    {
        $rule = Date::htmlWeek();
        $this->assertTrue($rule('2023-W01'));
        $this->assertTrue($rule('2023-W52'));
        $this->assertFalse($rule('2023-W54'));
        $this->assertFalse($rule('2023-01'));
    }

    public function testNotFutureDate()
    {
        $rule = Date::notFutureDate();
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $this->assertTrue($rule($today));
        $this->assertTrue($rule($yesterday));
        $this->assertFalse($rule($tomorrow));
        $this->assertFalse($rule('invalid'));
    }
}
