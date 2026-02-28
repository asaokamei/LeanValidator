<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class RulesTest extends TestCase
{
    public function testFloat()
    {
        $v = Validator::make(['val' => '1.23', 'invalid' => 'abc']);
        
        $v->field('val')->float();
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->float();
        $this->assertTrue($v->isCurrentError());
    }

    public function testUrl()
    {
        $v = Validator::make(['val' => 'https://example.com', 'invalid' => 'not-a-url']);
        
        $v->field('val')->url();
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->url();
        $this->assertTrue($v->isCurrentError());
    }

    public function testAlnum()
    {
        $v = Validator::make(['val' => 'abc123', 'invalid' => 'abc-123']);
        
        $v->field('val')->alnum();
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->alnum();
        $this->assertTrue($v->isCurrentError());
    }

    public function testAlpha()
    {
        $v = Validator::make(['val' => 'abc', 'invalid' => 'abc123']);
        
        $v->field('val')->alpha();
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->alpha();
        $this->assertTrue($v->isCurrentError());
    }

    public function testNumeric()
    {
        $v = Validator::make(['val' => '123', 'invalid' => '123a']);
        
        $v->field('val')->numeric();
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->numeric();
        $this->assertTrue($v->isCurrentError());
    }

    public function testIn()
    {
        $v = Validator::make(['val' => 'a', 'invalid' => 'z']);
        
        $v->field('val')->in(['a', 'b', 'c']);
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->in(['a', 'b', 'c']);
        $this->assertTrue($v->isCurrentError());
    }

    public function testContains()
    {
        $v = Validator::make(['val' => 'hello world', 'invalid' => 'bye']);
        
        $v->field('val')->contains('world');
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->contains('world');
        $this->assertTrue($v->isCurrentError());
    }

    public function testEqualTo()
    {
        $v = Validator::make(['val' => '100', 'invalid' => 100]);
        
        $v->field('val')->equalTo('100');
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('invalid')->equalTo('100'); // Strict check
        $this->assertTrue($v->isCurrentError());
    }

    public function testLength()
    {
        $v = Validator::make(['val' => 'abcde', 'too_short' => 'abc', 'too_long' => 'abcdefg']);
        
        $v->field('val')->length(3, 5);
        $this->assertTrue($v->isCurrentOK());
        
        $v->field('too_short')->length(4);
        $this->assertTrue($v->isCurrentError());
        
        $v->field('too_long')->length(null, 5);
        $this->assertTrue($v->isCurrentError());
    }
}
