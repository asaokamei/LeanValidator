<?php

namespace Wscore\LeanValidator;

use Closure;
use ReflectionException;
use ReflectionFunction;

class Validator
{
    protected array $data = [];
    protected array $validatedData = [];
    private MessageBag $errors;
    protected string $currentKey = '';
    private string $currentErrMsg = '';
    protected bool $currentErrFlag = false;

    public function __construct(array $data) 
    {
        $this->setData($data);
        $this->errors = new MessageBag();
    }
    public static function make(mixed $data): static
    {
        if (is_array($data)) {
            return new static($data);
        } elseif (is_string($data) || is_numeric($data)) {
            $self = new static(['__current_item__' => $data]);
            $self->currentKey = '__current_item__';
            return $self;
        }
        return new static([]);
    }
    protected function setData(array $data): void
    {
        $this->data = $data;
    }
    public function forKey(string $key, string $errorMsg = 'Please check the input value.'): static
    {
        $this->currentKey = $key;
        $this->currentErrMsg = $errorMsg;
        $this->currentErrFlag = false;
        return $this;
    }

    protected function getCurrentValue(): mixed
    {
        return $this->data[$this->currentKey] ?? null;
    }

    public function hasValue($option = ''): bool
    {
        if (array_key_exists($this->currentKey, $this->data)) {
            if ((string)$this->data[$this->currentKey] !== '' && !is_null($this->data[$this->currentKey])) {
                return true;
            }
        }
        $this->data[$this->currentKey] = $option;
        return false;
    }
    public function setError(string $msg = null): static
    {
        $this->errors->add($msg ?? $this->currentErrMsg, $this->currentKey);
        $this->currentErrFlag = true;
        return $this;
    }
    public function isCurrentError(): bool
    {
        return $this->currentErrFlag;
    }
    public function isCurrentOK(): bool
    {
        return !$this->currentErrFlag;
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

    public function isArray(): bool
    {
        $value = $this->getCurrentValue();
        return is_array($value);
    }

    /**
     * @return array
     */
    public function getValidatedData(): array
    {
        if (!$this->isValid()) {
            throw new \RuntimeException('Validation failed.');
        }
        return $this->validatedData;
    }

    /**
     * 必須チェック: フィールドが存在し、空でないこと
     */
    public function required(): static
    {
        if (!$this->hasValue()) {
            $this->setError();
        } else {
            $this->validatedData[$this->currentKey] = $this->getCurrentValue();
        }
        return $this;
    }

    /**
     * 文字列型チェック
     */
    public function string(): static
    {
        if ($this->currentErrFlag) return $this;
        $value = $this->getCurrentValue();
        if (!is_string($value)) {
            $this->setError();
        } else {
            $this->validatedData[$this->currentKey] = $value;
        }
        return $this;
    }

    /**
     * 整数型チェック
     */
    public function int(?int $min = null, ?int $max = null): static
    {
        if ($this->currentErrFlag) return $this;
        $value = $this->getCurrentValue();
        if (!is_int($value)) {
            $this->setError();
            return $this;
        }
        if ($min !== null && $value < $min) {
            $this->setError();
        }
        if ($max !== null && $value > $max) {
            $this->setError();
        }
        if ($this->isCurrentOK()) {
            $this->validatedData[$this->currentKey] = $value;
        }
        return $this;
    }

    /**
     * メールアドレス形式チェック
     */
    public function email(): static
    {
        if ($this->currentErrFlag) return $this;
        if ($this->string()->isCurrentOK()) {
            $value = $this->getCurrentValue();
            if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $this->setError();
            } else {
                $this->validatedData[$this->currentKey] = $value;
            }
        }

        return $this;
    }

    /**
     * 正規表現チェック
     *
     * @param string $pattern 正規表現パターン（デリミタ含む）
     */
    public function regex(string $pattern): static
    {
        if ($this->currentErrFlag) return $this;
        if ($this->string()->isCurrentOK()) {
            $value = $this->getCurrentValue();
            if (preg_match($pattern, $value) !== 1) {
                $this->setError();
            } else {
                $this->validatedData[$this->currentKey] = $value;
            }
        }
        return $this;
    }

    public function arrayCount($min = 1, $max = null): static
    {
        if ($this->currentErrFlag) return $this;
        $value = $this->getCurrentValue();

        if (!is_array($value)) {
            $this->setError();
            return $this;
        }
        if (count($value) < $min) {
            $this->setError();
            return $this;
        }
        if ($max !== null && count($value) > $max) {
            $this->setError();
        }
        if ($this->isCurrentOK()) {
            $this->validatedData[$this->currentKey] = $value;
        }
        return $this;
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
        if ($this->currentErrFlag) return $this;
        $value = $this->getCurrentValue();

        if (!is_array($value)) {
            $this->setError();
            return $this;
        }

        $hasItemErrors = false;
        $validatedItems = [];

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $this->setError();
                return $this;
            }
            $child = static::make([$key => $item]);
            $child->forKey($key, $this->currentErrMsg);
            if ($validator instanceof Closure) {
                $reflection = new ReflectionFunction($validator);
                $methodName = $reflection->getName();
                if ($methodName !== '{closure}' && method_exists($child, $methodName)) {
                    $child->{$methodName}(...$args);
                } else {
                    if ($reflection->getNumberOfParameters() === 0) {
                        $result = $validator->call($child);
                    } else {
                        $result = $validator->call($child, $item, ...$args);
                    }
                    if ($result === false) {
                        $child->setError();
                    }
                }
            } elseif (
                is_array($validator)
                && count($validator) === 2
                && is_string($validator[1])
                && method_exists($child, $validator[1])
            ) {
                $child->{$validator[1]}(...$args);
            } elseif (is_string($validator) && method_exists($child, $validator)) {
                $child->{$validator}(...$args);
            } else {
                $callable = $validator;
                $result = $callable($item, ...$args);
                if ($result === false) {
                    $child->setError();
                }
            }

            if ($child->isCurrentError()) {
                $this->errors->add($this->currentErrMsg, $this->currentKey, (string)$key);
                $hasItemErrors = true;
            } else {
                $childData = $child->getValidatedData();
                $validatedItems[$key] = $childData[$key] ?? $item;
            }
        }
        if ($hasItemErrors) {
            $this->currentErrFlag = true;
        } else {
            $this->validatedData[$this->currentKey] = $validatedItems;
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
        if ($this->currentErrFlag) return $this;
        $value = $this->getCurrentValue();

        if (!is_array($value)) {
            $this->setError();
            return $this;
        }

        $hasItemErrors = false;
        $validatedItems = [];

        foreach ($value as $key => $item) {
            if (!is_array($item)) {
                $this->setError();
                return $this;
            }
            $child = self::make($item);
            $callback($child);

            if ($child->isValid() === false) {
                foreach ($child->getErrorsFlat() as $childKey => $message) {
                    $this->errors->add($message, $this->currentKey, (string)$key, $childKey);
                    $hasItemErrors = true;
                }
            } else {
                $validatedItems[$key] = $child->getValidatedData();
            }
        }
        if ($hasItemErrors) {
            $this->currentErrFlag = true;
        } else {
            $this->validatedData[$this->currentKey] = $validatedItems;
        }
        return $this;
    }

}