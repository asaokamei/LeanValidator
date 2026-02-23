<?php
declare(strict_types=1);

namespace Wscore\LeanValidator;

use BadMethodCallException;
use Closure;
use ReflectionException;
use ReflectionFunction;

/**
 * 1 キー分のルール適用を担当する。
 * ValidatorData::forKey() から返され、required / apply / arrayApply などを提供する。
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

    /**
     * バリデーションを適用する。
     * 内部メソッド名・クロージャ・callable・外部ルールクラスを指定可能。
     *
     * @throws ReflectionException
     */
    public function apply(mixed $validator, mixed ...$args): static
    {
        if ($this->data->isCurrentError() || $this->data->isSkipped()) {
            return $this;
        }

        $value = $this->data->getCurrentValue();
        $endApply = function ($result) use ($value) {
            if ($result === false || $this->data->isCurrentError()) {
                $this->data->setError();
            } else {
                if (!$this->data->isSkipped()) {
                    $this->data->setValidatedCurrentKey($value);
                }
            }
        };

        if (is_string($validator)) {
            $internal = '_' . $validator;
            if (method_exists($this, $internal)) {
                $result = $this->$internal(...$args);
                $endApply($result);
                return $this;
            }
        }

        if ($validator instanceof Closure) {
            $reflection = new ReflectionFunction($validator);
            $methodName = $reflection->getName();
            $rules = $this->data->rules;
            if ($methodName !== '{closure}') {
                if (isset($rules[$methodName])) {
                    return $this->apply($methodName, ...$args);
                }
                if (method_exists($this, $methodName)) {
                    $result = $this->$methodName(...$args);
                    $endApply($result);
                    return $this;
                }
                if (method_exists($this, '_' . $methodName)) {
                    $result = $this->{'_' . $methodName}(...$args);
                    $endApply($result);
                    return $this;
                }
            }
            if ($reflection->getNumberOfParameters() === 0) {
                $result = $validator->call($this);
            } else {
                $result = $validator->call($this, $value, ...$args);
            }
            $endApply($result);
            return $this;
        }

        if (is_callable($validator)) {
            $result = $validator($value, ...$args);
        } elseif (is_string($validator)) {
            if (class_exists($validator)) {
                $rule = new $validator(...$args);
                $result = $rule($value);
            } else {
                throw new BadMethodCallException("Rule [{$validator}] is not defined.");
            }
        } else {
            throw new BadMethodCallException("Rule [{$validator}] is not defined.");
        }

        $endApply($result);
        return $this;
    }

    public function message(string $msg): static
    {
        $this->data->setErrorMessage($msg);
        return $this;
    }

    // ----- required / optional -----

    public function required(?string $msg = null): static
    {
        $msg = $msg ?? $this->data->getErrorMessage() ?? $this->data->defaultMessageRequired;
        if (!$this->data->hasValue()) {
            $this->data->setError($msg);
        }
        return $this;
    }

    public function requiredIf(
        string $otherKey,
        mixed $expect,
        ?string $msg = null,
        mixed $elseOverwrite = null
    ): static {
        $call = function (array $data) use ($otherKey, $expect) {
            $otherValue = $data[$otherKey] ?? null;
            return is_array($expect)
                ? in_array($otherValue, $expect, true)
                : ($otherValue === $expect);
        };
        return $this->requiredWhen($call, $msg, ...array_slice(func_get_args(), 3));
    }

    public function requiredUnless(
        string $otherKey,
        mixed $expect,
        ?string $msg = null,
        mixed $elseOverwrite = null
    ): static {
        $call = function (array $data) use ($otherKey, $expect) {
            $otherValue = $data[$otherKey] ?? null;
            $matched = is_array($expect)
                ? in_array($otherValue, $expect, true)
                : ($otherValue === $expect);
            return !$matched;
        };
        return $this->requiredWhen($call, $msg, ...array_slice(func_get_args(), 3));
    }

    public function requiredWith(string $otherKey, ?string $msg = null, mixed $elseOverwrite = null): static
    {
        $call = fn(array $data) => array_key_exists($otherKey, $data);
        return $this->requiredWhen($call, $msg, ...array_slice(func_get_args(), 2));
    }

    public function requiredWithout(string $otherKey, ?string $msg = null, mixed $elseOverwrite = null): static
    {
        $call = fn(array $data) => !array_key_exists($otherKey, $data);
        return $this->requiredWhen($call, $msg, ...array_slice(func_get_args(), 2));
    }

    public function requiredWhen(callable $call, ?string $msg = null, mixed $elseOverwrite = null): static
    {
        if ($this->data->isCurrentError() || $this->data->isSkipped()) {
            return $this;
        }
        if ($call($this->data->getData())) {
            return $this->required($msg);
        }
        $args = func_get_args();
        if (array_key_exists(2, $args) || array_key_exists('elseOverwrite', $args)) {
            $this->data->setValidatedCurrentKey($elseOverwrite);
            $this->data->setSkipped(true);
            return $this;
        }
        return $this->optional();
    }

    public function optional(mixed $default = null): static
    {
        if ($this->data->hasValue()) {
            return $this;
        }
        $args = func_get_args();
        if (array_key_exists(0, $args) || array_key_exists('default', $args)) {
            $this->data->setValidatedCurrentKey($default);
        }
        $this->data->setSkipped(true);
        return $this;
    }

    public function arrayCount(?int $min = 1, ?int $max = null, ?string $msg = 'Please select the values.'): static
    {
        $value = $this->data->getCurrentValue();
        if (!is_array($value) || ($min !== null && count($value) < $min) || ($max !== null && count($value) > $max)) {
            $this->data->setError($msg);
        }
        return $this;
    }

    // ----- 配列・ネスト -----

    /**
     * 配列の各要素にルールを適用する。
     *
     * @throws ReflectionException
     */
    public function arrayApply(mixed $validator, mixed ...$args): static
    {
        if ($this->data->isCurrentError()) {
            return $this;
        }
        $value = $this->data->getCurrentValue();
        if (!is_array($value)) {
            $this->data->setError();
            return $this;
        }
        $child = $this->makeChild($value);
        foreach ($value as $key => $item) {
            $child->forKey((string) $key)->apply($validator, ...$args);
        }
        if (!$child->isValid()) {
            $this->data->setErrors($child->getErrors()->toArray());
        } else {
            $this->data->setValidatedCurrentKey($child->getValidatedData());
        }
        return $this;
    }

    /**
     * ネストした配列を子 ValidatorData で検証する。
     */
    public function nest(callable $callback, ?string $msg = null): static
    {
        if ($this->data->isCurrentError() || $this->data->isSkipped()) {
            return $this;
        }
        $value = $this->data->getCurrentValue();
        if (!is_array($value)) {
            $msg = $msg ?? $this->data->errorMessage ?? $this->data->defaultMessage;
            $this->data->setError($msg);
            return $this;
        }
        $child = $this->makeChild($value);
        $callback($child);
        if (!$child->isValid()) {
            $this->data->setErrors($child->getErrors()->toArray());
            return $this;
        }
        $this->data->setValidatedCurrentKey($child->getValidatedData());
        return $this;
    }

    /**
     * 配列の各要素を「1件ずつ ValidatorData で検証」する。
     */
    public function forEach(callable $callback): static
    {
        if ($this->data->isCurrentError()) {
            return $this;
        }
        $value = $this->data->getCurrentValue();
        if (!is_array($value)) {
            $this->data->setError();
            return $this;
        }
        $childValidator = $this->makeChild($value);
        foreach ($value as $key => $item) {
            if (!is_array($item)) {
                $childValidator->forKey((string) $key)->setError('Value is not an array.');
                continue;
            }
            $child = $this->makeChild($item);
            $callback($child);
            if (!$child->isValid()) {
                $childValidator->setErrors($child->getErrors()->toArray(), (string) $key);
            } else {
                $childValidator->forKey((string) $key);
                $childValidator->setValidatedCurrentKey($child->getValidatedData());
            }
        }
        if (!$childValidator->isValid()) {
            $this->data->setErrors($childValidator->getErrors()->toArray());
        } else {
            $this->data->setValidatedCurrentKey($childValidator->getValidatedData());
        }
        return $this;
    }

    // ----- 組み込みルール（_*） -----

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

    /** 子インスタンスを生成（nest/forEach/arrayApply で、コールバックに渡す型を親と同じにする） */
    private function makeChild(mixed $value): ValidatorData
    {
        $class = get_class($this->data);
        return $class::make($value);
    }
}
