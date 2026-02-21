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
}