<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Sanitizer;

class SanitizerTest extends TestCase
{
    private function getSanitizer(): Sanitizer
    {
        return new Sanitizer();
    }

    public function testDefaultSanitization()
    {
        $s = $this->getSanitizer();
        $data = [
            'name' => '  John  ',
            'city' => ' Tokyo ',
        ];
        $cleaned = $s->clean($data);
        
        $this->assertEquals('John', $cleaned['name']);
        $this->assertEquals('Tokyo', $cleaned['city']);
    }

    public function testSkip()
    {
        $s = $this->getSanitizer();
        $s->skip('password');
        
        $data = [
            'name' => '  John  ',
            'password' => '  secret  ',
        ];
        $cleaned = $s->clean($data);
        
        $this->assertEquals('John', $cleaned['name']);
        $this->assertEquals('  secret  ', $cleaned['password']);
    }

    public function testSkipTrim()
    {
        $s = $this->getSanitizer();
        $s->skipTrim('comment');
        
        $data = [
            'name' => '  John  ',
            'comment' => '  keep spaces  ',
        ];
        $cleaned = $s->clean($data);
        
        $this->assertEquals('John', $cleaned['name']);
        $this->assertEquals('  keep spaces  ', $cleaned['comment']);
    }

    public function testToDigits()
    {
        $s = $this->getSanitizer();
        $s->toDigits('tel');
        
        $data = [
            'tel' => '03-1234-5678',
        ];
        $cleaned = $s->clean($data);
        
        $this->assertEquals('0312345678', $cleaned['tel']);
    }

    public function testToLower()
    {
        $s = $this->getSanitizer();
        $s->toLower('email');
        
        $data = [
            'email' => 'TEST@Example.COM',
        ];
        $cleaned = $s->clean($data);
        
        $this->assertEquals('test@example.com', $cleaned['email']);
    }

    public function testToKana()
    {
        $s = $this->getSanitizer();
        $s->toKana('name');
        
        $data = [
            'name' => 'ﾜｰﾙﾄﾞ', // 半角カナ
        ];
        $cleaned = $s->clean($data);
        
        // 'KVa' -> 全角カナ、半角英数字に変換
        $this->assertEquals('ワールド', $cleaned['name']);
    }

    public function testRecursiveClean()
    {
        $s = $this->getSanitizer();
        $s->toDigits('user.tel');
        
        $data = [
            'user' => [
                'name' => '  John  ',
                'tel' => '090-1111-2222',
            ]
        ];
        $cleaned = $s->clean($data);
        
        $this->assertEquals('John', $cleaned['user']['name']);
        $this->assertEquals('09011112222', $cleaned['user']['tel']);
    }

    public function testWildcard()
    {
        $s = $this->getSanitizer();
        $s->toDigits('items.*.code');
        
        $data = [
            'items' => [
                ['name' => '  Item 1  ', 'code' => 'A-123'],
                ['name' => '  Item 2  ', 'code' => 'B-456'],
            ]
        ];
        $cleaned = $s->clean($data);
        
        $this->assertEquals('Item 1', $cleaned['items'][0]['name']);
        $this->assertEquals('123', $cleaned['items'][0]['code']);
        $this->assertEquals('Item 2', $cleaned['items'][1]['name']);
        $this->assertEquals('456', $cleaned['items'][1]['code']);
    }

    public function testNonStringValue()
    {
        $s = $this->getSanitizer();
        $data = [
            'age' => 30,
            'is_active' => true,
        ];
        $cleaned = $s->clean($data);
        
        $this->assertSame(30, $cleaned['age']);
        $this->assertSame(true, $cleaned['is_active']);
    }
}
