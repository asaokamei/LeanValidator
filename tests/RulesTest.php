<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class RulesTest extends TestCase
{
    public function testFloat()
    {
        $v = Validator::make(['val' => '1.23', 'invalid' => 'abc']);
        
        $v->forKey('val')->float();
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->float();
        $this->assertTrue($v->isCurrentError());
    }

    public function testUrl()
    {
        $v = Validator::make(['val' => 'https://example.com', 'invalid' => 'not-a-url']);
        
        $v->forKey('val')->url();
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->url();
        $this->assertTrue($v->isCurrentError());
    }

    public function testAlnum()
    {
        $v = Validator::make(['val' => 'abc123', 'invalid' => 'abc-123']);
        
        $v->forKey('val')->alnum();
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->alnum();
        $this->assertTrue($v->isCurrentError());
    }

    public function testAlpha()
    {
        $v = Validator::make(['val' => 'abc', 'invalid' => 'abc123']);
        
        $v->forKey('val')->alpha();
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->alpha();
        $this->assertTrue($v->isCurrentError());
    }

    public function testNumeric()
    {
        $v = Validator::make(['val' => '123', 'invalid' => '123a']);
        
        $v->forKey('val')->numeric();
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->numeric();
        $this->assertTrue($v->isCurrentError());
    }

    public function testIn()
    {
        $v = Validator::make(['val' => 'a', 'invalid' => 'z']);
        
        $v->forKey('val')->in(['a', 'b', 'c']);
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->in(['a', 'b', 'c']);
        $this->assertTrue($v->isCurrentError());
    }

    public function testContains()
    {
        $v = Validator::make(['val' => 'hello world', 'invalid' => 'bye']);
        
        $v->forKey('val')->contains('world');
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->contains('world');
        $this->assertTrue($v->isCurrentError());
    }

    public function testEqualTo()
    {
        $v = Validator::make(['val' => '100', 'invalid' => 100]);
        
        $v->forKey('val')->equalTo('100');
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('invalid')->equalTo('100'); // Strict check
        $this->assertTrue($v->isCurrentError());
    }

    public function testLength()
    {
        $v = Validator::make(['val' => 'abcde', 'too_short' => 'abc', 'too_long' => 'abcdefg']);
        
        $v->forKey('val')->length(3, 5);
        $this->assertTrue($v->isCurrentOK());
        
        $v->forKey('too_short')->length(4);
        $this->assertTrue($v->isCurrentError());
        
        $v->forKey('too_long')->length(null, 5);
        $this->assertTrue($v->isCurrentError());
    }
}
