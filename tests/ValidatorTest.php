<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class ValidatorTest extends TestCase
{
    public function testMakeWithArray()
    {
        $data = ['name' => 'John', 'age' => 30];
        $v = Validator::make($data);
        $this->assertInstanceOf(Validator::class, $v);
        $this->assertTrue($v->isValid());
    }

    public function testMakeWithString()
    {
        $v = Validator::make('John');
        $this->assertInstanceOf(Validator::class, $v);
        $v->string();
        $this->assertTrue($v->isValid());
        
        $v = Validator::make('John');
        $v->int();
        $this->assertFalse($v->isValid());
    }

    public function testGetErrorsFlat()
    {
        $v = Validator::make(['name' => '', 'age' => 'old', 'other' => '']);
        $v->field('name', 'Name is required')->required();
        $v->field('age', 'Age must be int')->int();
        $v->field('other')->required();

        $flat = $v->getErrorsFlat();
        $this->assertEquals('Name is required', $flat['name']);
        $this->assertEquals('Age must be int', $flat['age']);
        $this->assertEquals('This field is required.', $flat['other']);
    }

    public function testRequired()
    {
        $v = Validator::make(['name' => 'John']);
        
        // Success
        $v->field('name')->required();
        $this->assertTrue($v->isValid());
        
        // Failure
        $v->field('missing')->required();
        $this->assertFalse($v->isValid());
        $this->assertArrayHasKey('missing', $v->getErrorsFlat());
    }

    public function testString()
    {
        $v = Validator::make(['name' => 'John', 'age' => 30]);
        
        $v->field('name')->string();
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('age')->string();
        $this->assertTrue($v->isCurrentError());
    }

    public function testInt()
    {
        $v = Validator::make(['age' => 30, 'string_age' => '30']);
        
        $v->field('age')->int();
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('age')->int(18, 50);
        $this->assertTrue($v->isCurrentOK());

        $v->field('age')->int(40);
        $this->assertTrue($v->isCurrentError());

        $v->field('string_age')->int();
        $this->assertTrue($v->isCurrentError());
    }

    public function testEmail()
    {
        $v = Validator::make(['email' => 'test@example.com', 'invalid' => 'not-an-email']);
        
        $v->field('email')->email();
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->email();
        $this->assertTrue($v->isCurrentError());
    }

    public function testRegex()
    {
        $v = Validator::make(['zip' => '123-4567', 'invalid' => '1234567']);
        
        $v->field('zip')->regex('/^\d{3}-\d{4}$/');
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->regex('/^\d{3}-\d{4}$/');
        $this->assertTrue($v->isCurrentError());
    }

    public function testArrayCount()
    {
        $v = Validator::make(['list' => [1, 2, 3]]);
        
        $v->field('list')->arrayCount(1, 5);
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('list')->arrayCount(5);
        $this->assertTrue($v->isCurrentError());
    }

    public function testAsListWithInt()
    {
        $v = Validator::make(['list' => [10, 20, 30]]);
        
        // $child is bound as $this to the closure
        $v->field('list')->asList($v->int(...), 0, 100);
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('list')->asList($v->int(...), 0, 15);
        $this->assertTrue($v->isCurrentError());
        // Error for key 20 (index 1) and 30 (index 2)
        $errors = $v->getErrors()->toArray();
        $this->assertArrayHasKey('list.1', $errors);
        $this->assertArrayHasKey('list.2', $errors);
    }

    public function testAsListObject()
    {
        $data = [
            'users' => [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Kid', 'age' => 10],
            ]
        ];
        $v = Validator::make($data);
        
        $v->field('users')->asListObject(function(Validator $child) {
            $child->field('name')->required()->string();
            $child->field('age')->required()->int(18);
        });
        
        $this->assertFalse($v->isValid());
        $errors = $v->getErrors()->toArray();
        // Kid is 10, so age < 18 should fail
        $this->assertArrayHasKey('users.2.age', $errors);
    }
}
