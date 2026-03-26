<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\ValidatorData;

class AddErrorTest extends TestCase
{
    public function testAddErrorDirectly()
    {
        $v = ValidatorData::make(['name' => 'test']);
        $v->addError('name', 'custom error');
        $v->addError('other', 'another error');

        $this->assertFalse($v->isValid());
        $errors = $v->getErrorsFlat();
        $this->assertEquals('custom error', $errors['name']);
        $this->assertEquals('another error', $errors['other']);
    }

    public function testFieldTemporaryMessage()
    {
        $v = ValidatorData::make(['age' => 'abc']);
        // field() の第2引数でそのフィールドのデフォルトエラーメッセージを指定
        $v->field('age', 'Must be a number')->int();

        $this->assertFalse($v->isValid());
        $errors = $v->getErrorsFlat();
        $this->assertEquals('Must be a number', $errors['age']);
    }

    public function testFieldTemporaryMessageReset()
    {
        $v = ValidatorData::make(['age' => 'abc', 'email' => 'not-email']);
        $v->field('age', 'Custom age error')->int();
        $v->field('email')->email();

        $this->assertFalse($v->isValid());
        $errors = $v->getErrorsFlat();
        $this->assertEquals('Custom age error', $errors['age']);
        // email の方はデフォルトメッセージになるはず
        $this->assertEquals('Please check the input value.', $errors['email']);
    }
}
