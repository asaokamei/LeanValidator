<?php

namespace Wscore\LeanValidator;

class ValidationContext
{
    protected array $data = [];
    protected array $validatedData = [];
    protected string $currentKey = '';
    protected bool $currentErrFlag = false;
    protected bool $currentSkipped = false;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function setKey(string $key): void
    {
        $this->currentKey = $key;
        $this->currentErrFlag = false;
        $this->currentSkipped = false;
    }

    public function getCurrentKey(): string
    {
        return $this->currentKey;
    }

    public function getCurrentValue(): mixed
    {
        return $this->data[$this->currentKey] ?? null;
    }

    public function hasValue(): bool
    {
        $value = $this->data[$this->currentKey] ?? null;
        if ($value !== '' && !is_null($value)) {
            return true;
        }
        return false;
    }

    public function setError(): void
    {
        $this->currentErrFlag = true;
    }

    public function setValidatedData(mixed $value): void
    {
        if ($this->currentErrFlag || $this->currentSkipped) {
            return;
        }
        $this->validatedData[$this->currentKey] = $value;
    }

    public function setSkipped(bool $skipped): void
    {
        $this->currentSkipped = $skipped;
    }

    public function isCurrentError(): bool
    {
        return $this->currentErrFlag;
    }

    public function isSkipped(): bool
    {
        return $this->currentSkipped;
    }

    public function isCurrentOK(): bool
    {
        return !$this->currentErrFlag;
    }

    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    public function getValue(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}
