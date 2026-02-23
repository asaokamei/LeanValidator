<?php
declare(strict_types=1);

namespace Wscore\LeanValidator\Trait;

use ReflectionException;
use Wscore\LeanValidator\ValidatorData;

/**
 * 配列・ネスト（arrayApply / nest / forEach）を提供するトレイト。
 * ValidatorRules で use する。$this->data で ValidatorData にアクセスすること。
 */
trait ArrayRules
{
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
            $msg = $msg ?? $this->data->getErrorMessage() ?? $this->data->defaultMessage;
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

    /** 子インスタンスを生成（nest/forEach/arrayApply で、コールバックに渡す型を親と同じにする） */
    private function makeChild(mixed $value): ValidatorData
    {
        /** @var ValidatorData $class */
        $class = get_class($this->data);
        return $class::make($value);
    }
}
