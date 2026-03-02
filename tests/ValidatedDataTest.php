<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;
use RuntimeException;

class ValidatedDataTest extends TestCase
{
    public function testGetValidatedDataReturnsOnlyValidatedFields()
    {
        $data = [
            'name' => 'John',
            'age' => 30,
            'extra' => 'not validated'
        ];
        $v = Validator::make($data);
        $v->field('name')->required()->string();
        $v->field('age')->required()->int();

        $validated = $v->getValidatedData();
        $this->assertEquals([
            'name' => 'John',
            'age' => 30
        ], $validated);
        $this->assertArrayNotHasKey('extra', $validated);
    }

    public function testGetValidatedDataThrowsExceptionOnError()
    {
        $data = ['age' => 'not an int'];
        $v = Validator::make($data);
        $v->field('age')->required()->int();

        $this->expectException(RuntimeException::class);
        $v->getValidatedData();
    }

    public function testGetValidatedDataWithAsList()
    {
        $data = [
            'tags' => ['php', 'testing', 'validator'],
            'other' => 'hidden'
        ];
        $v = Validator::make($data);
        $v->field('tags')->asList('string');

        $validated = $v->getValidatedData();
        $this->assertEquals([
            'tags' => ['php', 'testing', 'validator']
        ], $validated);
    }

    public function testGetValidatedDataWithAsListObject()
    {
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'John', 'extra' => 'hide'],
                ['id' => 2, 'name' => 'Jane', 'extra' => 'hide'],
            ],
            'meta' => 'hide'
        ];
        $v = Validator::make($data);
        $v->field('users')->asListObject(function(Validator $child) {
            $child->field('id')->required()->int();
            $child->field('name')->required()->string();
        });

        $validated = $v->getValidatedData();
        $this->assertEquals([
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
            ]
        ], $validated);
    }
}
