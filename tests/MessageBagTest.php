<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\MessageBag;

class MessageBagTest extends TestCase
{
    public function testAddAndGet()
    {
        $bag = new MessageBag();
        $bag->add('error 1', 'field1');
        $bag->add('error 2', 'field1');
        $bag->add('error 3', 'field2');

        $this->assertEquals(['error 1', 'error 2'], $bag->get('field1'));
        $this->assertEquals(['error 3'], $bag->get('field2'));
        $this->assertTrue($bag->has('field1'));
        $this->assertFalse($bag->has('field3'));
    }

    public function testFirst()
    {
        $bag = new MessageBag();
        $bag->add('error 1', 'field1');
        $bag->add('error 2', 'field1');

        $this->assertEquals('error 1', $bag->first('field1'));
        $this->assertNull($bag->first('field2'));
    }

    public function testAll()
    {
        $bag = new MessageBag();
        $bag->add('error 1', 'field1');
        $bag->add('error 2', 'field2');

        $this->assertEquals(['error 1', 'error 2'], $bag->all());
    }

    public function testIsEmpty()
    {
        $bag = new MessageBag();
        $this->assertTrue($bag->isEmpty());

        $bag->add('error', 'field');
        $this->assertFalse($bag->isEmpty());
    }

    public function testBuildPath()
    {
        $bag = new MessageBag();
        $bag->add('error', 'parent', 'child', 'grandchild');
        
        $this->assertEquals(['error'], $bag->get('parent.child.grandchild'));
    }

    public function testFromFormName()
    {
        $bag = new MessageBag();
        $bag->add('error', 'user.address.city');

        $this->assertEquals(['error'], $bag->getFromFormName('user[address][city]'));
        $this->assertEquals('error', $bag->firstFromFormName('user[address][city]'));
    }

    public function testSetErrors()
    {
        $bag = new MessageBag();
        $errors = [
            'name' => ['required'],
            'address' => [
                'city' => ['too long'],
            ]
        ];
        $bag->setErrors($errors);

        $this->assertEquals(['required'], $bag->get('name'));
        $this->assertEquals(['too long'], $bag->get('address.city'));
    }

    public function testSetErrorsWithPath()
    {
        $bag = new MessageBag();
        $errors = [
            'city' => ['required'],
        ];
        $bag->setErrors($errors, 'user', 'address');

        $this->assertEquals(['required'], $bag->get('user.address.city'));
    }
}
