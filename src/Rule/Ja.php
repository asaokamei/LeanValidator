<?php

namespace Wscore\LeanValidator\Rule;

use Closure;

class Ja
{
    /**
     * Hiragana only.
     */
    public static function hiragana(): Closure
    {
        return function ($value) {
            return preg_match('/^[ぁ-んー]*$/u', $value) === 1;
        };
    }

    /**
     * Katakana only.
     */
    public static function katakana(): Closure
    {
        return function ($value) {
            return preg_match('/^[ァ-ヶー]*$/u', $value) === 1;
        };
    }

    /**
     * Hiragana or Katakana.
     */
    public static function kana(): Closure
    {
        return function ($value) {
            return preg_match('/^[ぁ-んァ-ヶー]*$/u', $value) === 1;
        };
    }

    /**
     * Hankaku-Katakana only.
     */
    public static function hankakuKana(): Closure
    {
        return function ($value) {
            return preg_match('/^[｡-ﾟ]*$/u', $value) === 1;
        };
    }

    /**
     * Kanji only.
     */
    public static function kanji(): Closure
    {
        return function ($value) {
            return preg_match('/^[一-龠々]*$/u', $value) === 1;
        };
    }

    /**
     * Zenkaku characters (non-ASCII).
     */
    public static function zenkaku(): Closure
    {
        return function ($value) {
            return preg_match('/^[^ -~｡-ﾟ]*$/u', $value) === 1;
        };
    }

    /**
     * Japanese Zip code (000-0000).
     */
    public static function zip(): Closure
    {
        return function ($value) {
            return preg_match('/^\d{3}-\d{4}$/', $value) === 1;
        };
    }

    /**
     * Japanese Phone number (000-0000-0000).
     */
    public static function tel(): Closure
    {
        return function ($value) {
            return preg_match('/^\d{2,5}-\d{1,4}-\d{3,4}$/', $value) === 1;
        };
    }
}