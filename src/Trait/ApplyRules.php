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

        $temporaryMessage = $this->temporaryMessage;
        $this->temporaryMessage = null;

        $value = $this->data->getCurrentValue();
        $endApply = function ($result) use ($value, $temporaryMessage) {
            if ($result === false || $this->data->isCurrentError()) {
                $originalMessage = $this->data->getErrorMessage();
                if ($temporaryMessage) {
                    $this->data->setErrorMessage($temporaryMessage);
                }
                $this->data->setError();
                $this->data->setErrorMessage($originalMessage);
            } else {
                if (!$this->data->isSkipped()) {
                    $this->data->setValidatedCurrentKey($value);
                }
            }
        };

        if (is_string($validator)) {
            $internal = '_' . $validator;
            if (method_exists($this, $internal)) {
                $endApply($this->$internal(...$args));
                return $this;
            }
            if (isset($this->rules[$validator])) {
                $endApply(($this->rules[$validator])($value, ...$args));
                return $this;
            }
        }

        if ($validator instanceof Closure) {
            $reflection = new ReflectionFunction($validator);
            $methodName = $reflection->getName();
            if ($methodName !== '{closure}') {
                if (isset($this->rules[$methodName])) {
                    return $this->apply($methodName, ...$args);
                }
                if (method_exists($this, $methodName)) {
                    $endApply($this->$methodName(...$args));
                    return $this;
                }
                if (method_exists($this, '_' . $methodName)) {
                    $endApply($this->{'_' . $methodName}(...$args));
                    return $this;
                }
            }
            $result = $reflection->getNumberOfParameters() === 0
                ? $validator->call($this)
                : $validator->call($this, $value, ...$args);
            $endApply($result);
            return $this;
        }

        if (is_callable($validator)) {
            $endApply($validator($value, ...$args));
            return $this;
        }

        if (is_string($validator) && class_exists($validator)) {
            $rule = new $validator(...$args);
            $endApply($rule($value));
            return $this;
        }

        throw new BadMethodCallException("Rule [{$validator}] is not defined.");
    }
}
