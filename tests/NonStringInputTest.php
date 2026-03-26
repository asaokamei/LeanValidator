<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class NonStringInputTest extends TestCase
{
    public function testFloatRuleWithNumber()
    {
        $v = Validator::make(['price' => 12.34]);
        $v->field('price')->float();
        $this->assertTrue($v->isCurrentOK(), 'float rule should accept float numbers');

        $v = Validator::make(['price' => 100]);
        $v->field('price')->float();
        $this->assertTrue($v->isCurrentOK(), 'float rule should accept integer numbers');
    }

    public function testEmailRuleWithNonString()
    {
        $v = Validator::make(['email' => 123]);
        $v->field('email')->email();
        $this->assertFalse($v->isCurrentOK(), 'email rule should reject integers');
    }

    public function testNumericRuleWithNumber()
    {
        $v = Validator::make(['count' => 10]);
        $v->field('count')->numeric();
        $this->assertTrue($v->isCurrentOK(), 'numeric rule should accept integers');
    }
    public function testAlnumRuleWithNumber()
    {
        $v = Validator::make(['val' => 123]);
        $v->field('val')->alnum();
        $this->assertTrue($v->isCurrentOK(), 'alnum rule should accept integers');
    }

    public function testAlphaRuleWithNumber()
    {
        $v = Validator::make(['val' => 123]);
        $v->field('val')->alpha();
        $this->assertFalse($v->isCurrentOK(), 'alpha rule should reject integers');
    }

    public function testDigitRuleWithNumber()
    {
        $v = Validator::make(['val' => 123]);
        $v->field('val')->digit();
        $this->assertTrue($v->isCurrentOK(), 'digit rule should accept integers');
    }

    public function testMinMaxRuleWithFloat()
    {
        $v = Validator::make(['val' => 10.5]);
        $v->field('val')->min(10);
        $this->assertTrue($v->isCurrentOK(), 'min rule should accept floats');

        $v->field('val')->max(11);
        $this->assertTrue($v->isCurrentOK(), 'max rule should accept floats');
    }

    public function testBetweenRuleWithFloat()
    {
        $v = Validator::make(['val' => 10.5]);
        $v->field('val')->between(10, 11);
        $this->assertTrue($v->isCurrentOK(), 'between rule should accept floats');
    }
}
