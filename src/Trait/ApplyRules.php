<?php
declare(strict_types=1);

namespace Wscore\LeanValidator\Trait;

use BadMethodCallException;
use Closure;
use ReflectionException;
use ReflectionFunction;

/**
 * バリデーション適用（apply）の分岐ロジックを提供するトレイト。
 * ValidatorRules で use する。$this->data で ValidatorData にアクセスすること。
 */
trait ApplyRules
{
    /**
     * バリデーションを適用する。
     * 内部メソッド名・登録名（$rules）・クロージャ・callable・外部ルールクラスを指定可能。
     *
     * @throws ReflectionException
     */
    public function apply(mixed $validator, mixed ...$args): static
    {
        if ($this->data->isCurrentError() || $this->data->isSkipped()) {
            return $this;
        }

        $value = $this->data->getCurrentValue();
        $result = $this->applyRule($validator, $value, ...$args);

        if ($result === false || $this->data->isCurrentError()) {
            $this->setError();
        } else {
            if (!$this->data->isSkipped()) {
                $this->data->setValidatedCurrentKey($value);
            }
            $this->methodMessage = null;
        }
        return $this;
    }

    /**
     * バリデーションルールを実際に呼び出す。
     *
     * @throws ReflectionException
     */
    protected function applyRule(mixed $validator, mixed $value, mixed ...$args): mixed
    {
        if (is_string($validator)) {
            $internal = '_' . $validator;
            if (method_exists($this, $internal)) {
                return $this->$internal(...$args);
            }
            if (isset($this->rules[$validator])) {
                return ($this->rules[$validator])($value, ...$args);
            }
        }

        if ($validator instanceof Closure) {
            $reflection = new ReflectionFunction($validator);
            $methodName = $reflection->getName();
            if ($methodName !== '{closure}') {
                if (isset($this->rules[$methodName])) {
                    $this->apply($methodName, ...$args);
                    return $this->data->isCurrentOK();
                }
                if (method_exists($this, $methodName)) {
                    return $this->$methodName(...$args);
                }
                if (method_exists($this, '_' . $methodName)) {
                    return $this->{'_' . $methodName}(...$args);
                }
            }
            return $reflection->getNumberOfParameters() === 0
                ? $validator->call($this)
                : $validator->call($this, $value, ...$args);
        }

        if (is_callable($validator)) {
            return $validator($value, ...$args);
        }

        if (is_string($validator) && class_exists($validator)) {
            $rule = new $validator(...$args);
            return $rule($value);
        }

        throw new BadMethodCallException("Rule [{$validator}] is not defined.");
    }
}
