<?php

namespace Wscore\LeanValidator;

use Closure;
use ReflectionException;
use ReflectionFunction;

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
    protected array $data = [];
    protected array $validatedData = [];
    private MessageBag $errors;
    protected string $currentKey = '';
    private string $currentErrMsg = '';
    protected bool $currentErrFlag = false;
    public array $rules = [
        'email' => ['filterVar', [FILTER_VALIDATE_EMAIL]],
    ];
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
     */
    public function apply(mixed $validator, mixed ...$args): static
    {
        if ($this->currentErrFlag) {
            return $this;
        }

        $result = true;

        // 1. 内部メソッドの探索 (_name)
        if (is_string($validator)) {
            $internal = '_' . $validator;
            if (method_exists($this, $internal)) {
                $result = $this->$internal(...$args);
                if ($result === false) {
                    $this->setError();
                } elseif ($this->isCurrentOK()) {
                    $this->validatedData[$this->currentKey] = $this->getCurrentValue();
                }
                return $this;
            }
        }

        // 2. クロージャの実行
        if ($validator instanceof Closure) {
            $reflection = new ReflectionFunction($validator);
            $methodName = $reflection->getName();
            if ($methodName !== '{closure}') {
                if (method_exists($this, $methodName)) {
                    $this->apply($methodName, ...$args);
                    return $this;
                }
                // First-class callable の場合、メソッド名が int などの場合がある
                if (method_exists($this, '_' . $methodName)) {
                    $this->apply($methodName, ...$args);
                    return $this;
                }
            }
            $numParams = $reflection->getNumberOfParameters();
            if ($numParams === 0) {
                $result = $validator->call($this);
            } else {
                $result = $validator->call($this, $this->getCurrentValue(), ...$args);
            }
            if ($result === false) {
                $this->setError();
            } elseif ($this->isCurrentOK()) {
                $this->validatedData[$this->currentKey] = $this->getCurrentValue();
            }
            return $this;
        }

        // 3. 外部ルールクラスの探索 (__invoke)
        if (is_string($validator)) {
            $external = "Asao\\Rules\\" . ucfirst($validator);
            if (class_exists($external)) {
                $rule = new $external(...$args);
                if (!$rule($this->getCurrentValue())) {
                    $this->setError();
                } else {
                    $this->validatedData[$this->currentKey] = $this->getCurrentValue();
                }
                return $this;
            }
        }

        // 4. その他の callable
        if (is_callable($validator)) {

            $result = $validator($this->getCurrentValue(), ...$args);

            if ($result === false) {
                $this->setError();
            } elseif ($this->isCurrentOK()) {
                $this->validatedData[$this->currentKey] = $this->getCurrentValue();
            }
            return $this;
        }

        throw new \BadMethodCallException("Rule [{$validator}] is not defined.");
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

    public function required(?string $msg = 'Name is required'): static
    {
        if (!$this->hasValue()) {
            $this->setError($msg);
        }
        return $this;
    }

    public function arrayCount(?int $min = 1, ?int $max = null, ?string $msg = 'Please select the values.'): static
    {
        $value = $this->getCurrentValue();

        if (!is_array($value)) {
            $this->setError($msg);
        } elseif ($min !== null && count($value) < $min) {
            $this->setError($msg);
        } elseif ($max !== null && count($value) > $max) {
            $this->setError($msg);
        }
        return $this;
    }

    public function message(string $msg): static
    {
        $this->currentErrMsg = $msg;
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
    protected function setError(string $msg = null): static
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
     * 文字列型チェック
     */
    protected function _string(): bool
    {
        $value = $this->getCurrentValue();
        if (!is_string($value)) {
            return false;
        }
        return true;
    }

    /**
     * 整数型チェック
     */
    protected function _int(?int $min = null, ?int $max = null): bool
    {
        $value = $this->getCurrentValue();
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

    protected function _filterVar(string $filter): bool
    {
        if ($this->_string()) {
            $value = $this->getCurrentValue();
            if (filter_var($value, $filter) === false) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * 正規表現チェック
     *
     * @param string $pattern 正規表現パターン（デリミタ含む）
     */
    protected function _regex(string $pattern): bool
    {
        if ($this->_string()) {
            $value = $this->getCurrentValue();
            if (preg_match($pattern, $value) !== 1) {
                return false;
            }
            return true;
        }
        return false;
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

            $child->apply($validator, ...$args);

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