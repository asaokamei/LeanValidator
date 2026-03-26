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
        $v = Validator::make([
            's' => '123',
            'f' => 1.5,
            'i' => 42,
            'invalid' => '123a',
        ]);

        $v->field('s')->numeric();
        $this->assertTrue($v->isCurrentOK());

        $v->field('f')->numeric();
        $this->assertTrue($v->isCurrentOK());

        $v->field('i')->numeric();
        $this->assertTrue($v->isCurrentOK());

        $v->field('invalid')->numeric();
        $this->assertTrue($v->isCurrentError());
    }

    public function testDigit()
    {
        $v = Validator::make(['ok' => '123', 'bad' => '123a', 'intVal' => 123]);

        $v->field('ok')->digit();
        $this->assertTrue($v->isCurrentOK());

        $v->field('bad')->digit();
        $this->assertTrue($v->isCurrentError());

        $v->field('intVal')->digit();
        $this->assertTrue($v->isCurrentOK());
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

    public function testStartsWith()
    {
        $v = Validator::make(['ok' => 'prefix-rest', 'bad' => 'hello']);

        $v->field('ok')->startsWith('prefix-');
        $this->assertTrue($v->isCurrentOK());

        $v->field('bad')->startsWith('prefix-');
        $this->assertTrue($v->isCurrentError());
    }

    public function testEndsWith()
    {
        $v = Validator::make(['ok' => 'rest-suffix', 'bad' => 'hello']);

        $v->field('ok')->endsWith('-suffix');
        $this->assertTrue($v->isCurrentOK());

        $v->field('bad')->endsWith('-suffix');
        $this->assertTrue($v->isCurrentError());
    }

    public function testJson()
    {
        $v = Validator::make([
            'ok' => '{"a":1}',
            'bad' => '{',
            'notString' => 1,
        ]);

        $v->field('ok')->json();
        $this->assertTrue($v->isCurrentOK());

        $v->field('bad')->json();
        $this->assertTrue($v->isCurrentError());

        $v->field('notString')->json();
        $this->assertTrue($v->isCurrentError());
    }

    public function testBool()
    {
        $v = Validator::make([
            't' => true,
            'f' => false,
            'one' => 1,
            'str' => 'true',
        ]);

        $v->field('t')->bool();
        $this->assertTrue($v->isCurrentOK());

        $v->field('f')->bool();
        $this->assertTrue($v->isCurrentOK());

        $v->field('one')->bool();
        $this->assertTrue($v->isCurrentError());

        $v->field('str')->bool();
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

    public function testSameAs()
    {
        $v = Validator::make([
            'password' => 'secret',
            'password_confirm' => 'secret',
            'mismatch' => 'other',
        ]);

        $v->field('password_confirm')->sameAs('password');
        $this->assertTrue($v->isCurrentOK());

        $v->field('mismatch')->sameAs('password');
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

    public function testDate()
    {
        $v = Validator::make(['ok' => '2023-10-01', 'bad' => '2023-13-01', 'format' => '01/10/2023']);

        $v->field('ok')->date();
        $this->assertTrue($v->isCurrentOK());

        $v->field('bad')->date();
        $this->assertTrue($v->isCurrentError());

        $v->field('format')->date('d/m/Y');
        $this->assertTrue($v->isCurrentOK());
    }

    public function testAccepted()
    {
        $v = Validator::make(['t1' => true, 't2' => '1', 't3' => 'on', 't4' => 'yes', 't5' => 'true', 'f1' => false, 'f2' => '0']);

        $v->field('t1')->accepted(); $this->assertTrue($v->isCurrentOK());
        $v->field('t2')->accepted(); $this->assertTrue($v->isCurrentOK());
        $v->field('t3')->accepted(); $this->assertTrue($v->isCurrentOK());
        $v->field('t4')->accepted(); $this->assertTrue($v->isCurrentOK());
        $v->field('t5')->accepted(); $this->assertTrue($v->isCurrentOK());

        $v->field('f1')->accepted(); $this->assertTrue($v->isCurrentError());
        $v->field('f2')->accepted(); $this->assertTrue($v->isCurrentError());
    }

    public function testNotIn()
    {
        $v = Validator::make(['val' => 'a', 'invalid' => 'b']);

        $v->field('val')->notIn(['b', 'c']);
        $this->assertTrue($v->isCurrentOK());

        $v->field('invalid')->notIn(['b', 'c']);
        $this->assertTrue($v->isCurrentError());
    }

    public function testInKeys()
    {
        $v = Validator::make(['val' => 'a', 'invalid' => 'z']);
        $keys = ['a' => 1, 'b' => 2];

        $v->field('val')->inKeys($keys);
        $this->assertTrue($v->isCurrentOK());

        $v->field('invalid')->inKeys($keys);
        $this->assertTrue($v->isCurrentError());
    }

    public function testAlphaDash()
    {
        $v = Validator::make(['ok' => 'a-b_1', 'bad' => 'a b']);

        $v->field('ok')->alphaDash();
        $this->assertTrue($v->isCurrentOK());

        $v->field('bad')->alphaDash();
        $this->assertTrue($v->isCurrentError());
    }

    public function testHasChar()
    {
        $v = Validator::make(['ok' => 'Abc!', 'bad' => 'abc']);

        // Must have at least one upper case
        $v->field('ok')->hasChar('/[A-Z]/');
        $this->assertTrue($v->isCurrentOK());

        $v->field('bad')->hasChar('/[A-Z]/');
        $this->assertTrue($v->isCurrentError());

        // Must have at least two alphabets
        $v->field('ok')->hasChar('/[a-z]/', 2);
        $this->assertTrue($v->isCurrentOK());
    }
}
