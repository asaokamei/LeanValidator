<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class AsObjectTest extends TestCase
{
    public function testAsObjectSuccess()
    {
        $data = ['address' => ['post_code' => '123-1234', 'town' => 'TOKYO', 'city' => 'Meguro']];
        $v = Validator::make($data);

        $v->field('address')->required()->asObject(function (Validator $child) {
            $child->field('post_code')->required()->regex('/^\d{3}-\d{4}$/');
            $child->field('town')->required()->string();
            $child->field('city')->required()->string();
        });

        $this->assertTrue($v->isValid());
        $this->assertSame($data, $v->getValidatedData());
    }

    public function testAsObjectFailureMergesErrorsWithPath()
    {
        $data = ['address' => ['post_code' => '1231234', 'town' => 'TOKYO', 'city' => 'Meguro']];
        $v = Validator::make($data);

        $v->field('address')->required()->asObject(function (Validator $child) {
            $child->field('post_code')->required()->regex('/^\d{3}-\d{4}$/');
        });

        $this->assertFalse($v->isValid());
        $errors = $v->getErrors()->toArray();
        $this->assertArrayHasKey('address.post_code', $errors);
    }

    public function testAsObjectAndAsList()
    {
        $data = ['address' => ['post_code' => '123-1234', 'cities' => ['Tokyo', 'Osaka']]];
        $v = Validator::make($data);

        $v->field('address')->required()->asObject(function (Validator $child) {
            $child->field('post_code')->required()->regex('/^\d{3}-\d{4}$/');
            $child->field('cities')->required()->asList('string');
        });

        $this->assertTrue($v->isValid());
        $this->assertSame($data, $v->getValidatedData());
    }

    public function testAsObjectForField()
    {
        $data = [];
        $v = Validator::make($data);
        $v->field('address')->asObject(function (Validator $child) {
            $child->field('post_code')->required()->regex('/^\d{3}-\d{4}$/');
        });
        // 親キー address が required() されていない場合でも、
        // asObject() 内で required() がある場合はエラーになるべき（親が null でも空配列として扱うため）
        $this->assertFalse($v->isValid());
        $this->assertArrayNotHasKey('address', $v->getErrors()->toArray());
        $this->assertArrayHasKey('address.post_code', $v->getErrors()->toArray());
    }

    public function testAsObjectForFieldAndOptional()
    {
        $data = [];
        $v = Validator::make($data);
        $v->field('address')->asObject(function (Validator $child) {
            $child->field('post_code')->optional()->regex('/^\d{3}-\d{4}$/');
        });
        // 親も子も実質 optional ならば、isValid() は true になるべき
        $this->assertTrue($v->isValid());
    }

    public function testAsObjectForEmptyField()
    {
        $data = ['address' => ''];
        $v = Validator::make($data);
        $v->field('address')->asObject(function (Validator $child) {
            $child->field('post_code')->regex('/^\d{3}-\d{4}$/');
        });
        // 親キー address が空文字の場合、配列ではないのでエラーになるべき
        $this->assertFalse($v->isValid());
        $this->assertArrayHasKey('address', $v->getErrors()->toArray());
        $this->assertArrayNotHasKey('address.post_code', $v->getErrors()->toArray());
    }

    public function testAsObjectAndOptional()
    {
        $data = [];
        $v = Validator::make($data);
        $v->field('address')->optional()->asObject(function (Validator $child) {
            $child->field('post_code')->required()->regex('/^\d{3}-\d{4}$/');
        });
        $this->assertTrue($v->isValid());
    }
    public function testAsObjectAndRequired()
    {
        $data = [];
        $v = Validator::make($data);
        $v->field('address')->required()->asObject(function (Validator $child) {
            $child->field('post_code')->required()->regex('/^\d{3}-\d{4}$/');
        });
        $this->assertFalse($v->isValid());
        $errors = $v->getErrors()->toArray();
        $this->assertArrayHasKey('address', $errors);
    }
}