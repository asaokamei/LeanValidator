<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Rule\Ja;

class JaRuleTest extends TestCase
{
    public function test_hiragana()
    {
        $rule = Ja::hiragana();
        $this->assertTrue($rule('あいうえおー'));
        $this->assertFalse($rule('アイウエオ'));
        $this->assertFalse($rule('漢字'));
        $this->assertFalse($rule('abc'));
    }

    public function test_katakana()
    {
        $rule = Ja::katakana();
        $this->assertTrue($rule('アイウエオヶー'));
        $this->assertFalse($rule('あいうえお'));
        $this->assertFalse($rule('漢字'));
    }

    public function test_kana()
    {
        $rule = Ja::kana();
        $this->assertTrue($rule('あいうアイウ'));
        $this->assertFalse($rule('漢字'));
    }

    public function test_hankakuKana()
    {
        $rule = Ja::hankakuKana();
        $this->assertTrue($rule('ｱｲｳｴｵ｡'));
        $this->assertFalse($rule('あいうえお'));
        $this->assertFalse($rule('アイウエオ'));
    }

    public function test_kanji()
    {
        $rule = Ja::kanji();
        $this->assertTrue($rule('漢字々'));
        $this->assertFalse($rule('あいうえお'));
        $this->assertFalse($rule('アイウエオ'));
    }

    public function test_zenkaku()
    {
        $rule = Ja::zenkaku();
        $this->assertTrue($rule('あいうえお'));
        $this->assertTrue($rule('アイウエオ'));
        $this->assertTrue($rule('漢字'));
        $this->assertTrue($rule('１２３'));
        $this->assertTrue($rule('ＡＢＣ'));
        $this->assertFalse($rule('abc'));
        $this->assertFalse($rule('123'));
        $this->assertFalse($rule('ｱｲｳｴｵ'));
    }

    public function test_zip()
    {
        $rule = Ja::zip();
        $this->assertTrue($rule('123-4567'));
        $this->assertFalse($rule('1234567'));
        $this->assertFalse($rule('123-456'));
    }

    public function test_tel()
    {
        $rule = Ja::tel();
        $this->assertTrue($rule('03-1234-5678'));
        $this->assertTrue($rule('090-1234-5678'));
        $this->assertTrue($rule('0120-123-456'));
        $this->assertFalse($rule('0312345678'));
    }
}
