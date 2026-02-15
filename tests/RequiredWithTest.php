<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class RequiredWithTest extends TestCase
{
    public function testRequiredWithWhenKeyExists()
    {
        $v = Validator::make(['other' => 'exists', 'target' => '']);
        $v->forKey('target')->requiredWith('other', 'Target is required if other exists');

        $this->assertFalse($v->isValid());
        $this->assertEquals('Target is required if other exists', $v->getErrorsFlat()['target']);
    }

    public function testRequiredWithWhenKeyMissing()
    {
        $v = Validator::make(['target' => '']);
        $v->forKey('target')->requiredWith('other', 'Target is required if other exists')->string();

        $this->assertTrue($v->isValid());
        $this->assertArrayNotHasKey('target', $v->getValidatedData());
    }

    public function testRequiredWithWhenKeyExistsWithValue()
    {
        $v = Validator::make(['other' => 'exists', 'target' => 'value']);
        $v->forKey('target')->requiredWith('other')->string();

        $this->assertTrue($v->isValid());
        $this->assertEquals('value', $v->getValidatedData()['target']);
    }

    public function testRequiredWithElseOverwrite()
    {
        $v = Validator::make(['target' => 'original']);
        // 'other' is missing, so it should overwrite with 'default' and skip string() validation
        $v->forKey('target')->requiredWith('other', 'msg', 'default')->string();

        $this->assertTrue($v->isValid());
        $this->assertEquals('default', $v->getValidatedData()['target']);
    }

    public function testRequiredWithoutWhenKeyMissing()
    {
        $v = Validator::make(['target' => '']);
        $v->forKey('target')->requiredWithout('other', 'Target is required if other is missing');

        $this->assertFalse($v->isValid());
        $this->assertEquals('Target is required if other is missing', $v->getErrorsFlat()['target']);
    }

    public function testRequiredWithoutWhenKeyExists()
    {
        $v = Validator::make(['other' => 'exists', 'target' => '']);
        $v->forKey('target')->requiredWithout('other')->string();

        $this->assertTrue($v->isValid());
        $this->assertArrayNotHasKey('target', $v->getValidatedData());
    }

    public function testRequiredWithNullValue()
    {
        // Even if the value is null, the key exists
        $v = Validator::make(['other' => null, 'target' => '']);
        $v->forKey('target')->requiredWith('other', 'Required');

        $this->assertFalse($v->isValid());
    }
}
