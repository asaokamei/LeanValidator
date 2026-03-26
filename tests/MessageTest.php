<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class MessageTest extends TestCase
{
    /**
     * field() でセットしたメッセージが、次のフィールドに残らないことを確認する。
     */
    public function testFieldMessageIsIsolated()
    {
        $v = Validator::make(['f1' => '', 'f2' => '']);
        
        // f1 にカスタムメッセージをセット
        $v->field('f1', 'custom-f1')->required();
        // f2 にはセットしない
        $v->field('f2')->required();
        
        $errors = $v->getErrorsFlat();
        $this->assertEquals('custom-f1', $errors['f1']);
        $this->assertEquals('This field is required.', $errors['f2']);
    }

    /**
     * message() でセットしたメッセージが、次のルールに残らないことを確認する。
     */
    public function testMethodMessageIsClearedAfterRule()
    {
        $v = Validator::make(['f1' => 'not-email']);
        
        $v->field('f1', 'field-default')
            ->message('custom-rule-msg')->string() // 成功するはず
            ->email(); // 失敗するはず。ここでは 'field-default' が使われるべき

        $errors = $v->getErrorsFlat();
        // string() は成功するのでエラーにならず、email() でエラーになる。
        // email() の時点では 'custom-rule-msg' はクリアされている必要がある。
        $this->assertEquals('field-default', $errors['f1']);
    }

    /**
     * message() でセットしたメッセージが、ルールが失敗した時もクリアされることを確認する。
     */
    public function testMethodMessageIsClearedEvenAfterFailure()
    {
        $v = Validator::make(['f1' => 123]); // Use integer 123
        
        $v->field('f1', 'field-default')
            ->message('custom-int')->int()   // 成功。この後 'custom-int' はクリアされるはず。
            ->message('custom-email')->email(); // 失敗。'custom-email' が使われるべき。

        $errors = $v->getErrorsFlat();
        $this->assertEquals('custom-email', $errors['f1']);
    }

    /**
     * 別フィールドでのメッセージ分離
     */
    public function testMessageIsolationBetweenFields()
    {
        $v = Validator::make(['f1' => 'abc', 'f2' => 'not-url']);
        $v->field('f1')->message('msg-f1')->int(); // 失敗
        $v->field('f2')->url(); // 失敗。デフォルトメッセージが使われるべき
        
        $errors = $v->getErrorsFlat();
        $this->assertEquals('msg-f1', $errors['f1']);
        $this->assertEquals('Please check the input value.', $errors['f2']);
    }

    /**
     * メッセージの優先順位を確認する。
     * apply(msg) > message(msg) > field(msg) > default
     */
    public function testMessagePriorities()
    {
        // 1. apply(msg) が最優先
        // Note: Currently apply(msg) is only supported in required() and similar methods
        // that explicitly call setError($msg).
        $v = Validator::make(['f1' => '']);
        $v->field('f1', 'field-msg')
            ->message('method-msg')
            ->required('apply-msg'); 
        $this->assertEquals('apply-msg', $v->getErrorsFlat()['f1']);

        // 2. message(msg) が次点
        $v = Validator::make(['f1' => 'abc']);
        $v->field('f1', 'field-msg')
            ->message('method-msg')
            ->int(); 
        $this->assertEquals('method-msg', $v->getErrorsFlat()['f1']);

        // 3. field(msg)
        $v = Validator::make(['f1' => 'abc']);
        $v->field('f1', 'field-msg')
            ->int();
        $this->assertEquals('field-msg', $v->getErrorsFlat()['f1']);

        // 4. default
        $v = Validator::make(['f1' => 'abc']);
        $v->field('f1')
            ->int();
        $this->assertEquals('Please check the input value.', $v->getErrorsFlat()['f1']);
    }

    /**
     * Required 系のメッセージ優先順位
     */
    public function testRequiredMessagePriorities()
    {
        $v = Validator::make(['f1' => '']);
        
        // field msg vs required default
        $v->field('f1', 'field-msg')->required();
        $this->assertEquals('field-msg', $v->getErrorsFlat()['f1']);

        $v = Validator::make(['f2' => '']);
        // message() vs field msg
        $v->field('f2', 'field-msg')->message('must-input')->required();
        $this->assertEquals('must-input', $v->getErrorsFlat()['f2']);
    }
}
