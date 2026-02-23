<?php
declare(strict_types=1);

namespace Wscore\LeanValidator;

use BadMethodCallException;
use RuntimeException;

/**
 * データの保持と「現在キー」の状態を管理する。
 * ルールの適用は forKey() が返す ValidatorRules に委譲する。
 */
class ValidatorData
{
    protected array $data = [];
    protected array $validatedData = [];
    protected string $currentKey = '';
    protected bool $isError = false;
    protected bool $isSkipped = false;
    protected MessageBag $errors;
    protected ?string $errorMessage = null;
    public string $defaultMessage = 'Please check the input value.';
    public string $defaultMessageRequired = 'This field is required.';

    /** @var array<string, array{0: string, 1?: array}> 名前 => [applyの第1引数, 第2引数以降] */
    public array $rules = [
        'email' => ['filterVar', [FILTER_VALIDATE_EMAIL]],
        'float' => ['filterVar', [FILTER_VALIDATE_FLOAT]],
        'url' => ['filterVar', [FILTER_VALIDATE_URL]],
        'alnum' => ['regex', ['/^[a-zA-Z0-9]+$/']],
        'alpha' => ['regex', ['/^[a-zA-Z]+$/']],
        'numeric' => ['regex', ['/^[0-9]+$/']],
        'alphaDash' => ['regex', ['/^[a-zA-Z0-9_\-]+$/']],
    ];

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->errors = new MessageBag();
    }

    public static function make(mixed $data): static
    {
        if (is_array($data)) {
            return new static($data);
        }
        if (is_string($data) || is_numeric($data)) {
            $self = new static(['__current_item__' => $data]);
            $self->currentKey = '__current_item__';
            return $self;
        }
        return new static([]);
    }

    /**
     * 指定キーに対するルール適用オブジェクトを返す。
     * サブクラスで createRules() をオーバーライドすると、独自の ValidatorRules を差し替え可能。
     */
    public function forKey(string $key, ?string $errorMsg = null): ValidatorRules
    {
        $this->currentKey = $key;
        $this->isError = false;
        $this->isSkipped = false;
        $this->errorMessage = $errorMsg;
        return $this->createRules();
    }

    /**
     * forKey() が返すルール適用オブジェクトを生成する。
     * プロジェクトごとのカスタムルールや ValidatorRulesLang を使う場合はオーバーライドする。
     */
    protected function createRules(): ValidatorRules
    {
        return new ValidatorRules($this);
    }

    /**
     * make('string') のときなど、currentKey が既に設定されている場合に
     * forKey(currentKey)->$name(...$args) へ委譲する。
     */
    public function __call(string $name, array $args): ValidatorRules
    {
        if ($this->currentKey !== '') {
            return $this->forKey($this->currentKey)->$name(...$args);
        }
        throw new BadMethodCallException("Call forKey() first or use make(array).");
    }

    // ----- 結果取得（公開API） -----

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

    public function isValid(): bool
    {
        return $this->errors->isEmpty();
    }

    public function getValidatedData(): array
    {
        if (!$this->isValid()) {
            throw new RuntimeException('Validation failed.');
        }
        return $this->validatedData;
    }

    public function getValueAtKey(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /** 生の入力データ（ValidatorRules の requiredWhen 等で使用） */
    public function getData(): array
    {
        return $this->data;
    }

    public function isSkipped(): bool
    {
        return $this->isSkipped;
    }

    public function isCurrentError(): bool
    {
        return $this->isError;
    }

    public function isCurrentOK(): bool
    {
        return !$this->isError;
    }

    // ----- ValidatorRules から使うための API -----

    public function getCurrentValue(): mixed
    {
        return $this->data[$this->currentKey] ?? null;
    }

    public function getCurrentKey(): string
    {
        return $this->currentKey;
    }

    public function hasValue(): bool
    {
        $value = $this->getCurrentValue();
        return $value !== '' && $value !== null;
    }

    public function setError(?string $msg = null): static
    {
        $errorMsg = $msg ?? $this->errorMessage ?? $this->defaultMessage;
        if ($this->currentKey === '' || $this->currentKey === '__current_item__') {
            $this->errors->add($errorMsg);
        } else {
            $this->errors->add($errorMsg, $this->currentKey);
        }
        $this->isError = true;
        return $this;
    }

    public function setErrors(array $errors, string ...$path): static
    {
        if ($path === [] && $this->currentKey !== '' && $this->currentKey !== '__current_item__') {
            $path = [$this->currentKey];
        }
        $this->errors->setErrors($errors, ...$path);
        $this->isError = true;
        return $this;
    }

    public function setValidatedCurrentKey(mixed $value): static
    {
        $this->validatedData[$this->currentKey] = $value;
        return $this;
    }

    public function setSkipped(bool $skipped = true): static
    {
        $this->isSkipped = $skipped;
        return $this;
    }

    /** ValidatorRules::message() / required() から使用 */
    public function setErrorMessage(?string $msg): void
    {
        $this->errorMessage = $msg;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
