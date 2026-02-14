<?php

namespace Wscore\LeanValidator;

use BadMethodCallException;
use Closure;
use ReflectionException;
use ReflectionFunction;
use RuntimeException;

/**
 * @method $this int(?int $min = null, ?int $max = null)
 * @method $this string()
 * @method $this email()
 * @method $this filterVar(string $filter)
 * @method $this regex(string $pattern)
 * @　method $this unique(\PDO $db, string $table, ?string $msg = null)
 */
class Validator
{
    protected ValidationContext $context;
    protected MessageBag $errors;
    protected string $errorMessage = '';

    public array $rules = [
        'email' => ['filterVar', [FILTER_VALIDATE_EMAIL]],
    ];

    public function __construct(array $data)
    {
        $this->context = new ValidationContext($data);
        $this->errors = new MessageBag();
    }

    public static function make(mixed $data): static
    {
        if (is_array($data)) {
            return new static($data);
        } elseif (is_string($data) || is_numeric($data)) {
            $self = new static(['__current_item__' => $data]);
            $self->context->setKey('__current_item__');
            return $self;
        }
        return new static([]);
    }

    /**
     * @throws ReflectionException
     */
    public function __call(string $name, array $args): static
    {
        if (isset($this->rules[$name])) {
            $validator = $this->rules[$name][0];
            $args = $this->rules[$name][1] ?? [];
            return $this->apply($validator, ...$args);
        }
        return $this->apply($name, ...$args);
    }

    /**
     * バリデーションを適用します。
     *
     * @param mixed $validator
     * @param mixed ...$args
     * @return $this
     * @throws ReflectionException
     */
    public function apply(mixed $validator, mixed ...$args): static
    {
        if ($this->context->isCurrentError() || $this->context->isSkipped()) {
            return $this;
        }

        $value = $this->getCurrentValue();
        $endApply = function ($result) use ($value) {
            if ($result === false || $this->context->isCurrentError()) {
                $this->setError();
            } else {
                $this->context->setValidatedData($value);
            }
        };

        // 1. 内部メソッドの探索 (_name)
        if (is_string($validator)) {
            $internal = '_' . $validator;
            if (method_exists($this, $internal)) {
                $result = $this->$internal(...$args);
                $endApply($result);
                return $this;
            }
        }
        // 2. クロージャの実行
        if ($validator instanceof Closure) {
            $reflection = new ReflectionFunction($validator);
            $methodName = $reflection->getName();
            if ($methodName !== '{closure}') {
                if (method_exists($this, $methodName)) {
                    return $this->apply($methodName, ...$args);
                }
                if (method_exists($this, '_' . $methodName)) {
                    return $this->apply($methodName, ...$args);
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
        // 3. その他の callable または 外部ルールクラスの探索
        if (is_callable($validator)) {
            $result = $validator($value, ...$args);
        } elseif (is_string($validator)) {
            $external = $validator;
            if (class_exists($external)) {
                $rule = new $external(...$args);
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

    public function forKey(string $key, ?string $errorMsg = null): static
    {
        $this->context->setKey($key);
        $this->errorMessage = $errorMsg ?? 'Please check the input value.';
        return $this;
    }

    public function required(?string $msg = 'Name is required'): static
    {
        if (!$this->hasValue()) $this->setError($msg);
        return $this;
    }

    public function optional(mixed $default = null): static
    {
        if (!$this->hasValue($default)) {
            $this->context->setValidatedData($default);
            $this->context->setSkipped(true);
        }
        return $this;
    }

    public function arrayCount(?int $min = 1, ?int $max = null, ?string $msg = 'Please select the values.'): static
    {
        $value = $this->getCurrentValue();
        if (!is_array($value) || ($min !== null && count($value) < $min) || ($max !== null && count($value) > $max)) {
            $this->setError($msg);
        }
        return $this;
    }

    public function message(string $msg): static
    {
        $this->errorMessage = $msg;
        return $this;
    }

    protected function getCurrentValue(): mixed
    {
        return $this->context->getCurrentValue();
    }

    public function hasValue($option = ''): bool
    {
        return $this->context->hasValue($option);
    }

    protected function setError(string $msg = null): static
    {
        $currentKey = $this->context->getCurrentKey();
        $errorMsg = $msg ?? $this->errorMessage;
        if ($currentKey === '__current_item__') {
            $this->errors->add($errorMsg);
        } else {
            $this->errors->add($errorMsg, $currentKey);
        }
        $this->context->setError();
        return $this;
    }

    public function setErrors(array $errors, string ...$path): static
    {
        $currentKey = $this->context->getCurrentKey();
        if (empty($path) && $currentKey !== '' && $currentKey !== '__current_item__') {
            $path = [$currentKey];
        }
        $this->errors->setErrors($errors, ...$path);
        $this->context->setError();
        return $this;
    }

    public function isCurrentError(): bool
    {
        return $this->context->isCurrentError();
    }

    public function isCurrentOK(): bool
    {
        return $this->context->isCurrentOK();
    }

    public function getErrors(): MessageBag
    {
        return $this->errors;
    }

    public function getErrorsFlat(): array
    {
        return array_map(function ($messages) {
            return $messages[0] ?? '';
        }, $this->errors->toArray());
    }

    public function getMessageBag(): MessageBag
    {
        return $this->errors;
    }

    public function isValid(): bool
    {
        return $this->errors->isEmpty();
    }

    /**
     * @return array
     */
    public function getValidatedData(): array
    {
        if (!$this->isValid()) {
            throw new RuntimeException('Validation failed.');
        }
        return $this->context->getValidatedData();
    }

    protected function _string(): bool
    {
        return is_string($this->getCurrentValue());
    }

    protected function _int(?int $min = null, ?int $max = null): bool
    {
        $value = $this->getCurrentValue();
        if (!is_int($value)) return false;
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }

    protected function _filterVar(string $filter): bool
    {
        $value = $this->getCurrentValue();
        return is_string($value) && filter_var($value, $filter) !== false;
    }

    protected function _regex(string $pattern): bool
    {
        $value = $this->getCurrentValue();
        return is_string($value) && preg_match($pattern, $value) === 1;
    }

    /**
     * 配列の各要素を検証します。
     *
     * 様々な形式の callable を受け取ることができます：
     *
     * 1. Validator メソッドを文字列で指定:
     *    $v->forKey('tags')->arrayApply('string');
     *
     * 2. Validator メソッドを First-class callable で指定 (PHP 8.1+):
     *    $v->forKey('ages')->arrayApply($v->int(...), 0, 150);
     *
     * 3. グローバル関数を指定:
     *    $v->forKey('ids')->arrayApply('is_numeric');
     *
     * 4. クロージャを指定 (第1引数に要素、第2引数以降に $args が渡されます):
     *    $v->forKey('items')->arrayApply(function($item, $min) {
     *        return strlen($item) >= $min;
     *    }, 5);
     *
     * 5. Validator インスタンスを操作するクロージャを指定 (引数なしの場合):
     *    $v->forKey('codes')->arrayApply(function() {
     *        $this->string()->regex('/^[A-Z]+$/');
     *    });
     *
     * @param callable $validator
     * @param mixed ...$args
     * @return $this
     * @throws ReflectionException
     */
    public function arrayApply(mixed $validator, mixed ...$args): static
    {
        if ($this->context->isCurrentError()) return $this;
        $value = $this->getCurrentValue();
        if (!is_array($value)) return $this->setError();

        $itemErrors = [];
        $validatedItems = [];
        foreach ($value as $key => $item) {
            $child = static::make(['v' => $item])->forKey('v');
            $child->apply($validator, ...$args);
            if ($child->isCurrentError()) {
                $itemErrors[$key] = [$this->errorMessage];
            } else {
                $validatedItems[$key] = $child->getValidatedData()['v'] ?? $item;
            }
        }
        if ($itemErrors) {
            $this->setErrors($itemErrors);
        } else {
            $this->context->setValidatedData($validatedItems);
        }
        return $this;
    }

    /**
     * example:
     * $this->forEach(function(Validator $child) {
     *     $child->forKey('name', 'Please check the name.')->required()->string();
     *     $child->forKey('age', 'Please check the age.')->required()->int(18);
     * });
     * @param callable $callback
     * @return $this
     */
    public function forEach(callable $callback): static
    {
        if ($this->context->isCurrentError()) return $this;
        $value = $this->getCurrentValue();
        if (!is_array($value)) return $this->setError();

        $itemErrors = [];
        $validatedItems = [];
        foreach ($value as $key => $item) {
            $child = self::make($item);
            $callback($child);
            if (!$child->isValid()) {
                $itemErrors[$key] = $child->getErrors()->toArray();
            } else {
                $validatedItems[$key] = $child->getValidatedData();
            }
        }
        if ($itemErrors) {
            $this->setErrors($itemErrors);
        } else {
            $this->context->setValidatedData($validatedItems);
        }
        return $this;
    }

}