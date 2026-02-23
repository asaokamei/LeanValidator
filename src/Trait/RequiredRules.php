<?php
declare(strict_types=1);

namespace Wscore\LeanValidator\Trait;

/**
 * required / optional / arrayCount など、必須・条件付き必須のルールを提供するトレイト。
 * ValidatorRules で use する。$this->data で ValidatorData にアクセスすること。
 */
trait RequiredRules
{
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
}
