<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class RequiredUnlessTest extends TestCase
{
    public function testRequiredUnlessMatched()
    {
        $v = Validator::make(['type' => 'personal', 'name' => '']);
        // type が personal なので、name は必須ではない (matched)
        $v->forKey('name')->requiredUnless('type', 'personal')->string();

        $this->assertTrue($v->isValid());
    }

    public function testRequiredUnlessNotMatched()
    {
        $v = Validator::make(['type' => 'business', 'name' => '']);
        // type が personal ではないので、name は必須 (not matched)
        $v->forKey('name')->requiredUnless('type', 'personal', 'Name is required')->string();

        $this->assertFalse($v->isValid());
        $this->assertEquals('Name is required', $v->getErrorsFlat()['name']);
    }

    public function testRequiredUnlessWithArrayExpectMatched()
    {
        $v = Validator::make(['type' => 'A', 'name' => '']);
        // type が A (A or Bに含まれる) なので、name は必須ではない
        $v->forKey('name')->requiredUnless('type', ['A', 'B'])->string();

        $this->assertTrue($v->isValid());
    }

    public function testRequiredUnlessWithArrayExpectNotMatched()
    {
        $v = Validator::make(['type' => 'C', 'name' => '']);
        // type が C (A or Bに含まれない) なので、name は必須
        $v->forKey('name')->requiredUnless('type', ['A', 'B'], 'Required')->string();

        $this->assertFalse($v->isValid());
    }

    public function testRequiredUnlessWithElseOverwrite()
    {
        $v = Validator::make(['type' => 'personal', 'name' => '']);
        // type が personal (matched) なので、name を 'guest' で上書きしてスキップ
        $v->forKey('name')->requiredUnless('type', 'personal', 'Required', 'guest')->int();

        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertEquals('guest', $data['name']);
    }
}
