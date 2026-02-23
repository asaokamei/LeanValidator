<?php
declare(strict_types=1);

namespace Wscore\LeanValidator\Rule;

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
}
