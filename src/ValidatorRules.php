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

    /**
     * ルール名 => [apply の第1引数, 第2引数以降]。
     * 継承でルールを追加する場合はコンストラクタで array_merge する。
     *
     * @var array<string, array{0: string, 1?: array}>
     */
    protected array $rules = [
        'email' => ['filterVar', [FILTER_VALIDATE_EMAIL]],
        'float' => ['filterVar', [FILTER_VALIDATE_FLOAT]],
        'url' => ['filterVar', [FILTER_VALIDATE_URL]],
        'alnum' => ['regex', ['/^[a-zA-Z0-9]+$/']],
        'alpha' => ['regex', ['/^[a-zA-Z]+$/']],
        'numeric' => ['regex', ['/^[0-9]+$/']],
        'alphaDash' => ['regex', ['/^[a-zA-Z0-9_\-]+$/']],
    ];

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
        if (isset($this->rules[$name])) {
            $rule = $this->rules[$name];
            $validator = $rule[0];
            $args = $rule[1] ?? [];
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
        return array_key_exists($this->data->getCurrentValue(), $allowedKeys);
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
