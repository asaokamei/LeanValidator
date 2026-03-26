<?php

namespace Wscore\LeanValidator\Rule;

use Closure;

class Date
{
    /**
     * HTML5 <input type="date"> format (Y-m-d).
     */
    public static function htmlDate(): Closure
    {
        return function ($value) {
            if (!is_string($value) && !is_int($value)) {
                return false;
            }
            return Date::matchFormat('Y-m-d', (string)$value);
        };
    }

    /**
     * HTML5 <input type="month"> format (Y-m).
     */
    public static function htmlMonth(): Closure
    {
        return function ($value) {
            if (!is_string($value) && !is_int($value)) {
                return false;
            }
            return Date::matchFormat('Y-m', (string)$value);
        };
    }

    /**
     * HTML5 <input type="time"> format (H:i or H:i:s).
     */
    public static function htmlTime(): Closure
    {
        return function ($value) {
            if (!is_string($value) && !is_int($value)) {
                return false;
            }
            $string = (string)$value;
            if (Date::matchFormat('H:i', $string)) {
                return true;
            }
            if (Date::matchFormat('H:i:s', $string)) {
                return true;
            }
            return false;
        };
    }

    /**
     * HTML5 <input type="datetime-local"> format (Y-m-d\TH:i or Y-m-d\TH:i:s).
     */
    public static function htmlDateTimeLocal(): Closure
    {
        return function ($value) {
            if (!is_string($value) && !is_int($value)) {
                return false;
            }
            $string = (string)$value;
            if (Date::matchFormat('Y-m-d\TH:i', $string)) {
                return true;
            }
            if (Date::matchFormat('Y-m-d\TH:i:s', $string)) {
                return true;
            }
            return false;
        };
    }

    /**
     * HTML5 <input type="week"> format (YYYY-Www).
     */
    public static function htmlWeek(): Closure
    {
        return function ($value) {
            if (!is_string($value) && !is_int($value)) {
                return false;
            }
            $string = (string)$value;
            if (!preg_match('/^(\d{4})-W(\d{2})$/', $string, $matches)) {
                return false;
            }
            $year = (int) $matches[1];
            $week = (int) $matches[2];
            if ($week < 1 || $week > 53) {
                return false;
            }
            try {
                new \DateTimeImmutable(sprintf('%d-W%02d-1', $year, $week));
            } catch (\Exception $e) {
                return false;
            }
            return true;
        };
    }

    /**
     * Rejects dates in the future (inclusive of today).
     *
     * @param string|null $format Date format, defaults to Y-m-d (HTML date).
     */
    public static function notFutureDate(?string $format = 'Y-m-d'): Closure
    {
        return function ($value) use ($format) {
            if (!is_string($value) && !is_int($value)) {
                return false;
            }
            $string = (string) $value;
            if (!Date::matchFormat($format, $string)) {
                return false;
            }
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $string);
            if ($date === false) {
                return false;
            }
            $today = new \DateTimeImmutable('today');
            return $date <= $today;
        };
    }

    public static function matchFormat(string $format, string $value): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('!' . $format, $value);
        if ($dt === false) {
            return false;
        }
        return $dt->format($format) === $value;
    }
}

