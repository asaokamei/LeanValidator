<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class NestTest extends TestCase
{
    public function testNestSuccess()
    {
        $data = ['address' => ['post_code' => '123-1234', 'town' => 'TOKYO', 'city' => 'Meguro']];
        $v = Validator::make($data);

        $v->forKey('address')->required()->nest(function (Validator $child) {
            $child->forKey('post_code')->required()->regex('/^\d{3}-\d{4}$/');
            $child->forKey('town')->required()->string();
            $child->forKey('city')->required()->string();
        });

        $this->assertTrue($v->isValid());
        $this->assertSame($data, $v->getValidatedData());
    }

    public function testNestFailureMergesErrorsWithPath()
    {
        $data = ['address' => ['post_code' => '1231234', 'town' => 'TOKYO', 'city' => 'Meguro']];
        $v = Validator::make($data);

        $v->forKey('address')->required()->nest(function (Validator $child) {
            $child->forKey('post_code')->required()->regex('/^\d{3}-\d{4}$/');
        });

        $this->assertFalse($v->isValid());
        $errors = $v->getErrors()->toArray();
        $this->assertArrayHasKey('address.post_code', $errors);
    }

    public function testNestAndArrayApply()
    {
        $data = ['address' => ['post_code' => '123-1234', 'cities' => ['Tokyo', 'Osaka']]];
        $v = Validator::make($data);

        $v->forKey('address')->required()->nest(function (Validator $child) {
            $child->forKey('post_code')->required()->regex('/^\d{3}-\d{4}$/');
            $child->forKey('cities')->required()->arrayApply('string');
        });

        $this->assertTrue($v->isValid());
        $this->assertSame($data, $v->getValidatedData());
    }

    public function testNestForNonExistingKey()
    {
        $data = [];
        $v = Validator::make($data);
        $v->forKey('address')->nest(function (Validator $child) {
            $child->forKey('post_code')->required()->regex('/^\d{3}-\d{4}$/');
        });
        // 親キー address が required() されていない場合でも、
        // nest() 内で required() がある場合はエラーになるべき（親が null でも空配列として扱うため）
        $this->assertFalse($v->isValid());
        $this->assertArrayNotHasKey('address', $v->getErrors()->toArray());
        $this->assertArrayHasKey('address.post_code', $v->getErrors()->toArray());
    }

    public function testNestForNonExistingKeyAndOptional()
    {
        $data = [];
        $v = Validator::make($data);
        $v->forKey('address')->nest(function (Validator $child) {
            $child->forKey('post_code')->optional()->regex('/^\d{3}-\d{4}$/');
        });
        // 親も子も実質 optional ならば、isValid() は true になるべき
        $this->assertTrue($v->isValid());
    }

    public function testNestForEmptyKey()
    {
        $data = ['address' => ''];
        $v = Validator::make($data);
        $v->forKey('address')->nest(function (Validator $child) {
            $child->forKey('post_code')->regex('/^\d{3}-\d{4}$/');
        });
        // 親キー address が空文字の場合、配列ではないのでエラーになるべき
        $this->assertFalse($v->isValid());
        $this->assertArrayHasKey('address', $v->getErrors()->toArray());
        $this->assertArrayNotHasKey('address.post_code', $v->getErrors()->toArray());
    }

    public function testNestAndOptional()
    {
        $data = [];
        $v = Validator::make($data);
        $v->forKey('address')->optional()->nest(function (Validator $child) {
            $child->forKey('post_code')->required()->regex('/^\d{3}-\d{4}$/');
        });
        $this->assertTrue($v->isValid());
    }
    public function testNestAndRequired()
    {
        $data = [];
        $v = Validator::make($data);
        $v->forKey('address')->required()->nest(function (Validator $child) {
            $child->forKey('post_code')->required()->regex('/^\d{3}-\d{4}$/');
        });
        $this->assertFalse($v->isValid());
        $errors = $v->getErrors()->toArray();
        $this->assertArrayHasKey('address', $errors);
    }
}