<?php
declare(strict_types=1);

namespace Wscore\LeanValidator;

use ReflectionException;
use Wscore\LeanValidator\Rule\ApplyRules;
use Wscore\LeanValidator\Rule\ArrayRules;
use Wscore\LeanValidator\Rule\RequiredRules;

/**
 * 1 キー分のルール適用を担当する。
 * ValidatorData::forKey() から返され、required / apply / arrayApply などを提供する。
 *
 * ルール（int, string などの組み込み述語）はこのクラスに定義している。
 * 適用ロジック・required 系・配列系は Rule\* トレイトに分離。
 *
 * @method $this int(?int $min = null, ?int $max = null)
 * @method $this string()
 * @method $this filterVar(string $filter)
 * @method $this email()
 * @method $this float()
 * @method $this url()
 * @method $this regex(string $pattern)
 * @method $this alnum()
 * @method $this alpha()
 * @method $this numeric()
 * @method $this in(array $choices)
 * @method $this contains(string $needle)
 * @method $this equalTo(mixed $expect)
 * @method $this length(?int $min = null, ?int $max = null)
 */
class ValidatorRules
{
    use ApplyRules;
    use RequiredRules;
    use ArrayRules;

    private ValidatorData $data;

    public function __construct(ValidatorData $data)
    {
        $this->data = $data;
    }

    /**
     * ValidatorRules 経由でエラーを付与する（主に forEach 内で使用）。
     */
    public function setError(?string $msg = null): static
    {
        $this->data->setError($msg);
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
        $rules = $this->data->rules;
        if (isset($rules[$name])) {
            $validator = $rules[$name][0];
            $args = $rules[$name][1] ?? [];
            return $this->apply($validator, ...$args);
        }
        return $this->apply($name, ...$args);
    }

    public function message(string $msg): static
    {
        $this->data->setErrorMessage($msg);
        return $this;
    }

    // ----- 組み込みルール（_*） -----
    // ルール一覧を見たいときはこのブロックを参照する。

    protected function _string(): bool
    {
        return is_string($this->data->getCurrentValue());
    }

    protected function _int(?int $min = null, ?int $max = null): bool
    {
        $value = $this->data->getCurrentValue();
        if (!is_int($value)) {
            return false;
        }
        if ($min !== null && $value < $min) {
            return false;
        }
        if ($max !== null && $value > $max) {
            return false;
        }
        return true;
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

    protected function _equalTo(mixed $expect): bool
    {
        return $this->data->getCurrentValue() === $expect;
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
}
