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
        $v = Validator::make(['name' => '', 'age' => 'old']);
        $v->forKey('name', 'Name is required')->required();
        $v->forKey('age', 'Age must be int')->int();
        
        $flat = $v->getErrorsFlat();
        $this->assertEquals('Name is required', $flat['name']);
        $this->assertEquals('Age must be int', $flat['age']);
    }

    public function testRequired()
    {
        $v = Validator::make(['name' => 'John']);
        
        // Success
        $v->forKey('name')->required();
        $this->assertTrue($v->isValid());
        
        // Failure
        $v->forKey('missing')->required();
        $this->assertFalse($v->isValid());
        $this->assertArrayHasKey('missing', $v->getErrorsFlat());
    }

    public function testString()
    {
        $v = Validator::make(['name' => 'John', 'age' => 30]);
        
        $v->forKey('name')->string();
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('age')->string();
        $this->assertTrue($v->isCurrentError());
    }

    public function testInt()
    {
        $v = Validator::make(['age' => 30, 'string_age' => '30']);
        
        $v->forKey('age')->int();
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('age')->int(18, 50);
        $this->assertTrue($v->isCurrentOK());

        $v->forKey('age')->int(40);
        $this->assertTrue($v->isCurrentError());

        $v->forKey('string_age')->int();
        $this->assertTrue($v->isCurrentError());
    }

    public function testEmail()
    {
        $v = Validator::make(['email' => 'test@example.com', 'invalid' => 'not-an-email']);
        
        $v->forKey('email')->email();
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->email();
        $this->assertTrue($v->isCurrentError());
    }

    public function testRegex()
    {
        $v = Validator::make(['zip' => '123-4567', 'invalid' => '1234567']);
        
        $v->forKey('zip')->regex('/^\d{3}-\d{4}$/');
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->regex('/^\d{3}-\d{4}$/');
        $this->assertTrue($v->isCurrentError());
    }

    public function testArrayCount()
    {
        $v = Validator::make(['list' => [1, 2, 3]]);
        
        $v->forKey('list')->arrayCount(1, 5);
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('list')->arrayCount(5);
        $this->assertTrue($v->isCurrentError());
    }

    public function testArrayApplyWithInt()
    {
        $v = Validator::make(['list' => [10, 20, 30]]);
        
        // $child is bound as $this to the closure
        $v->forKey('list')->arrayApply($v->int(...), 0, 100);
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('list')->arrayApply($v->int(...), 0, 15);
        $this->assertTrue($v->isCurrentError());
        // Error for key 20 (index 1) and 30 (index 2)
        $errors = $v->getErrors()->toArray();
        $this->assertArrayHasKey('list.1', $errors);
        $this->assertArrayHasKey('list.2', $errors);
    }

    public function testForEach()
    {
        $data = [
            'users' => [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Kid', 'age' => 10],
            ]
        ];
        $v = Validator::make($data);
        
        $v->forKey('users')->forEach(function(Validator $child) {
            $child->forKey('name')->required()->string();
            $child->forKey('age')->required()->int(18);
        });
        
        $this->assertFalse($v->isValid());
        $errors = $v->getErrors()->toArray();
        // Kid is 10, so age < 18 should fail
        $this->assertArrayHasKey('users.2.age', $errors);
    }
}
