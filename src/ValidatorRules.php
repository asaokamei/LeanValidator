<?php
declare(strict_types=1);

namespace Wscore\LeanValidator;

use ReflectionException;
use Wscore\LeanValidator\Trait\ApplyRules;
use Wscore\LeanValidator\Trait\ArrayRules;
use Wscore\LeanValidator\Trait\RequiredRules;

/**
 * 1 キー分のルール適用を担当する。
 * ValidatorData::field() から返され、required / apply / asList などを提供する。
 *
 * ルール（int, string などの組み込み述語）はこのクラスに定義している。
 * 適用ロジック・required 系・配列系は Rule\* トレイトに分離。
 *
 * @method $this int()
 * @method $this min(int $min)
 * @method $this max(int $max)
 * @method $this between(int|float $min, int|float $max)
 * @method $this string()
 * @method $this filterVar(int $filter)
 * @method $this email()
 * @method $this float()
 * @method $this url()
 * @method $this regex(string $pattern)
 * @method $this alnum()
 * @method $this alpha()
 * @method $this digit()
 * @method $this numeric()
 * @method $this in(array $choices)
 * @method $this contains(string $needle)
 * @method $this startsWith(string $prefix)
 * @method $this endsWith(string $suffix)
 * @method $this json()
 * @method $this bool()
 * @method $this equalTo(mixed $expect)
 * @method $this sameAs(string $otherKey)
 * @method $this length(?int $min = null, ?int $max = null)
 * @method $this date(?string $format = null)
 * @method $this accepted()
 * @method $this notIn(array $choices)
 * @method $this inKeys(array $allowedKeys)
 * @method $this alphaDash()
 * @method $this hasChar(string $pattern, int $min = 1)
 */
class ValidatorRules
{
    use ApplyRules;
    use RequiredRules;
    use ArrayRules;

    private ValidatorData $data;
    private ?string $methodMessage = null;
    private ?string $fieldMessage = null;

    /**
     * 追加・上書き用。組み込みは builtinRules() とマージされる（同名キーはこちらが優先）。
     *
     * @var array<string, callable>
     */
    protected array $rules = [];

    public function __construct(ValidatorData $data, ?string $fieldMessage = null)
    {
        $this->data = $data;
        $this->fieldMessage = $fieldMessage;
        $this->rules = array_merge(self::builtinRules(), $this->rules);
    }

    /**
     * @return array<string, callable>
     */
    private static function builtinRules(): array
    {
        return [
            'email' => fn ($v): bool => is_string($v) && filter_var($v, FILTER_VALIDATE_EMAIL) !== false,
            'float' => fn ($v): bool => is_numeric($v) && filter_var($v, FILTER_VALIDATE_FLOAT) !== false,
            'url' => fn ($v): bool => is_string($v) && filter_var($v, FILTER_VALIDATE_URL) !== false,
            'alnum' => fn ($v): bool => (is_string($v) || is_int($v)) && preg_match('/^[a-zA-Z0-9]+$/', (string)$v) === 1,
            'alpha' => fn ($v): bool => is_string($v) && preg_match('/^[a-zA-Z]+$/', $v) === 1,
            'digit' => fn ($v): bool => (is_string($v) || is_int($v)) && preg_match('/^[0-9]+$/', (string)$v) === 1,
            'numeric' => fn ($v): bool => is_numeric($v),
            'alphaDash' => fn ($v): bool => is_string($v) && preg_match('/^[a-zA-Z0-9_\-]+$/', $v) === 1,
            'min' => fn ($v, $min): bool => is_numeric($v) && $v >= $min,
            'max' => fn ($v, $max): bool => is_numeric($v) && $v <= $max,
        ];
    }

    /** @param callable $callback */
    public function addRule(string $name, callable $callback): static
    {
        $this->rules[$name] = $callback;
        return $this;
    }

    /**
     * ValidatorRules 経由でエラーを付与する。
     */
    public function setError(?string $msg = null): static
    {
        $errorMsg = $msg ?? $this->methodMessage ?? $this->fieldMessage;
        $this->data->setError($errorMsg);
        return $this;
    }

    /** 現在キーに値があるか（ValidatorData::hasValue の委譲） */
    public function hasValue(): bool
    {
        return $this->data->hasValue();
    }

    /**
     * @throws ReflectionException
     */
    public function __call(string $name, array $args): static
    {
        return $this->apply($name, ...$args);
    }

    /**
     * Set a custom error message for the next rule.
     * This message will be cleared after the next rule (or required) is executed.
     */
    public function message(string $msg): static
    {
        $this->methodMessage = $msg;
        return $this;
    }

    // ----- 組み込みルール（_*） -----
    // ルール一覧を見たいときはこのブロックを参照する。

    protected function _string(): bool
    {
        return is_string($this->data->getCurrentValue());
    }

    protected function _int(): bool
    {
        return is_int($this->data->getCurrentValue());
    }

    protected function _between(int|float $min, int|float $max): bool
    {
        $value = $this->data->getCurrentValue();
        if (!is_numeric($value)) {
            return false;
        }
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }
        return $value >= $min && $value <= $max;
    }

    protected function _filterVar(int $filter): bool
    {
        $value = $this->data->getCurrentValue();
        return is_string($value) && filter_var($value, $filter) !== false;
    }

    protected function _regex(string $pattern): bool
    {
        $value = $this->data->getCurrentValue();
        return is_string($value) && preg_match($pattern, $value) === 1;
    }

    protected function _in(array $choices): bool
    {
        return in_array($this->data->getCurrentValue(), $choices, true);
    }

    protected function _contains(string $needle): bool
    {
        $value = $this->data->getCurrentValue();
        return is_string($value) && str_contains($value, $needle);
    }

    protected function _startsWith(string $prefix): bool
    {
        $value = $this->data->getCurrentValue();
        return is_string($value) && str_starts_with($value, $prefix);
    }

    protected function _endsWith(string $suffix): bool
    {
        $value = $this->data->getCurrentValue();
        return is_string($value) && str_ends_with($value, $suffix);
    }

    protected function _json(): bool
    {
        $value = $this->data->getCurrentValue();
        if (!is_string($value)) {
            return false;
        }
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /** 値が PHP の真偽値型のみであることを検証する（`true` / `false`）。 */
    protected function _bool(): bool
    {
        return is_bool($this->data->getCurrentValue());
    }

    protected function _equalTo(mixed $expect): bool
    {
        return $this->data->getCurrentValue() === $expect;
    }

    /** 現在のフィールドの値が、別キー `$otherKey` の値と厳密一致することを検証する（パスワード確認など）。 */
    protected function _sameAs(string $otherKey): bool
    {
        return $this->data->getCurrentValue() === $this->data->getValueAtKey($otherKey);
    }

    protected function _length(?int $min = null, ?int $max = null): bool
    {
        $value = $this->data->getCurrentValue();
        if (!is_string($value)) {
            return false;
        }
        $len = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        if ($min !== null && $len < $min) {
            return false;
        }
        if ($max !== null && $len > $max) {
            return false;
        }
        return true;
    }

    protected function _date(?string $format = null): bool
    {
        $value = $this->data->getCurrentValue();
        if (!is_string($value) && !is_int($value)) {
            return false;
        }
        $format = $format ?? 'Y-m-d';
        $dt = \DateTime::createFromFormat('!' . $format, (string) $value);
        if ($dt === false) {
            return false;
        }
        return $dt->format($format) === (string) $value;
    }

    protected function _accepted(): bool
    {
        $value = $this->data->getCurrentValue();
        $accepted = [true, '1', 1, 'on', 'yes', 'true'];
        return in_array($value, $accepted, true);
    }

    protected function _notIn(array $choices): bool
    {
        return !in_array($this->data->getCurrentValue(), $choices, true);
    }

    protected function _inKeys(array $allowedKeys): bool
    {
        $key = $this->data->getCurrentValue();
        if (is_string($key) || is_int($key)) {
            return array_key_exists($key, $allowedKeys);
        }
        return false;
    }

    protected function _alphaDash(): bool
    {
        $value = $this->data->getCurrentValue();
        return is_string($value) && preg_match('/^[a-zA-Z0-9_\-]+$/', $value) === 1;
    }

    /**
     * 値のうち、正規表現に一致する文字が少なくとも $min 個あることを検証する。
     * パスワードの「大文字1文字以上」「記号2文字以上」などに利用可能。
     */
    protected function _hasChar(string $pattern, int $min = 1): bool
    {
        $value = $this->data->getCurrentValue();
        if (!is_string($value)) {
            return false;
        }
        $count = preg_match_all($pattern, $value, $m);
        return $count !== false && $count >= $min;
    }
}
