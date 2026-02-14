<?php

namespace Wscore\LeanValidator;

use Closure;
use ReflectionException;
use ReflectionFunction;

/**
 * @method $this int(?int $min = null, ?int $max = null, ?string $msg = null)
 * @method $this string(?string $msg = null)
 * @method $this email(?string $msg = null)
 * @method $this regex(string $pattern, ?string $msg = null)
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

        // 1. 内部メソッドの探索 (_name)
        if (is_string($validator)) {
            $internal = '_' . $validator;
            if (method_exists($this, $internal)) {
                $this->$internal(...$args);
                if ($this->isCurrentOK()) {
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
                    $this->{$methodName}(...$args);
                    if ($this->isCurrentOK()) {
                        $this->validatedData[$this->currentKey] = $this->getCurrentValue();
                    }
                    return $this;
                }
                // First-class callable の場合、メソッド名が int などの場合がある
                if (method_exists($this, '_' . $methodName)) {
                    $this->{'_' . $methodName}(...$args);
                    if ($this->isCurrentOK()) {
                        $this->validatedData[$this->currentKey] = $this->getCurrentValue();
                    }
                    return $this;
                }
            }
            $numParams = $reflection->getNumberOfParameters();
            $numArgs = count($args);
            // クロージャが期待する引数（$value + $argsの一部）より多ければ、最後の引数をメッセージとみなす
            // $value が自動的に渡されるため、実質的な引数数は $numArgs + 1
            $msg = ($numArgs > 0 && is_string(end($args)) && ($numArgs + 1) > $numParams)
                ? array_pop($args)
                : null;
            if ($numParams === 0) {
                $result = $validator->call($this);
            } else {
                $result = $validator->call($this, $this->getCurrentValue(), ...$args);
            }
            if ($result === false) {
                $this->setError($msg);
            } elseif ($this->isCurrentOK()) {
                $this->validatedData[$this->currentKey] = $this->getCurrentValue();
            }
            return $this;
        }

        // 3. 外部ルールクラスの探索 (__invoke)
        if (is_string($validator)) {
            $external = "Asao\\Rules\\" . ucfirst($validator);
            if (class_exists($external)) {
                $msg = (count($args) > 0 && is_string(end($args))) ? array_pop($args) : null;
                $rule = new $external(...$args);
                if (!$rule($this->getCurrentValue())) {
                    $this->setError($msg);
                } else {
                    $this->validatedData[$this->currentKey] = $this->getCurrentValue();
                }
                return $this;
            }
        }

        // 4. その他の callable
        if (is_callable($validator)) {
            $numArgs = count($args);
            // callable の場合は最後の引数が string ならメッセージ候補として扱う
            $msg = ($numArgs > 0 && is_string(end($args))) ? end($args) : null;

            try {
                $result = $validator($this->getCurrentValue(), ...$args);
            } catch (\ArgumentCountError $e) {
                // 引数が多い場合はメッセージを外して再試行
                if ($msg !== null) {
                    array_pop($args);
                    $result = $validator($this->getCurrentValue(), ...$args);
                } else {
                    throw $e;
                }
            }

            if ($result === false) {
                $this->setError($msg);
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
    protected function _required(?string $msg = null): static
    {
        if (!$this->hasValue()) {
            $this->setError($msg);
        }
        return $this;
    }

    /**
     * 文字列型チェック
     */
    protected function _string(?string $msg = null): static
    {
        $value = $this->getCurrentValue();
        if (!is_string($value)) {
            $this->setError($msg);
        }
        return $this;
    }

    /**
     * 整数型チェック
     */
    protected function _int(?int $min = null, ?int $max = null, ?string $msg = null): static
    {
        $value = $this->getCurrentValue();
        if (!is_int($value)) {
            $this->setError($msg);
            return $this;
        }
        if ($min !== null && $value < $min) {
            $this->setError($msg);
        }
        if ($max !== null && $value > $max) {
            $this->setError($msg);
        }
        return $this;
    }

    /**
     * メールアドレス形式チェック
     */
    protected function _email(?string $msg = null): static
    {
        if ($this->_string($msg)->isCurrentOK()) {
            $value = $this->getCurrentValue();
            if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $this->setError($msg);
            }
        }

        return $this;
    }

    /**
     * 正規表現チェック
     *
     * @param string $pattern 正規表現パターン（デリミタ含む）
     */
    protected function _regex(string $pattern, ?string $msg = null): static
    {
        if ($this->_string($msg)->isCurrentOK()) {
            $value = $this->getCurrentValue();
            if (preg_match($pattern, $value) !== 1) {
                $this->setError($msg);
            }
        }
        return $this;
    }

    protected function _arrayCount($min = 1, $max = null, ?string $msg = null): static
    {
        $value = $this->getCurrentValue();

        if (!is_array($value)) {
            $this->setError($msg);
            return $this;
        }
        if (count($value) < $min) {
            $this->setError($msg);
            return $this;
        }
        if ($max !== null && count($value) > $max) {
            $this->setError($msg);
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