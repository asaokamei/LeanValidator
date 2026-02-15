<?php /** @noinspection PhpUnused */

namespace Wscore\LeanValidator;

use BadMethodCallException;
use Closure;
use ReflectionException;
use ReflectionFunction;
use RuntimeException;

/**
 * @method $this int(?int $min = null, ?int $max = null)
 * @method $this string()
 * @method $this filterVar(string $filter)
 * @method $this email()
 * @method $this float()
 * @method $this url()
 * @method $this regex(string $pattern)
 * @method $this alnum()
 * @method $this alpha()
 * @method $this numeric()
 * @method $this in(array $choices)
 * @method $this contains(string $needle)
 * @method $this equalTo(mixed $expect)
 * @method $this length(?int $min = null, ?int $max = null)
 */
class Validator
{
    protected ValidationContext $context;
    protected MessageBag $errors;
    protected string $errorMessage = '';

    public array $rules = [
        'email' => ['filterVar', [FILTER_VALIDATE_EMAIL]],
        'float' => ['filterVar', [FILTER_VALIDATE_FLOAT]],
        'url' => ['filterVar', [FILTER_VALIDATE_URL]],
        'alnum' => ['regex', ['/^[a-zA-Z0-9]+$/']],
        'alpha' => ['regex', ['/^[a-zA-Z]+$/']],
        'numeric' => ['regex', ['/^[0-9]+$/']],
    ];

    public function __construct(array $data)
    {
        $this->context = new ValidationContext($data);
        $this->errors = new MessageBag();
    }

    public static function make(mixed $data): static
    {
        if (is_array($data)) {
            return new static($data);
        } elseif (is_string($data) || is_numeric($data)) {
            $self = new static(['__current_item__' => $data]);
            $self->context->setKey('__current_item__');
            return $self;
        }
        return new static([]);
    }

    /**
     * @throws ReflectionException
     */
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
     * $validator には以下のいずれかを指定できます：
     *
     * 1. 内部メソッドを文字列で指定:
     *    $v->forKey('age')->apply('int', 18, 100);
     *
     * 2. クロージャを指定 ($this がバインドされ、引数として $value と $args が渡されます):
     *    $v->forKey('name')->apply(function($value) {
     *        return strlen($value) > 5;
     *    });
     *
     * 3. $this を操作するクロージャを指定 (引数なしの場合):
     *    $v->forKey('zip')->apply(function() {
     *        $this->regex('/^\d{3}-\d{4}$/');
     *    });
     *
     * 4. その他の callable を指定 (関数、インスタンス化した invokable オブジェクトなど):
     *    $v->forKey('email')->apply('is_string');
     *    $v->forKey('price')->apply(new MyInvokableRule(), $minPrice);
     *
     * 5. 外部ルールクラス名を指定 (文字列として指定、実行時にインスタンス化されます):
     *    $v->forKey('price')->apply(MyCustomRule::class, $minPrice);
     *
     * @param mixed $validator
     * @param mixed ...$args
     * @return $this
     * @throws ReflectionException
     */
    public function apply(mixed $validator, mixed ...$args): static
    {
        if ($this->context->isCurrentError() || $this->context->isSkipped()) {
            return $this;
        }

        $value = $this->getCurrentValue();
        $endApply = function ($result) use ($value) {
            if ($result === false || $this->context->isCurrentError()) {
                $this->setError();
            } else {
                $this->context->setValidatedData($value);
            }
        };

        // 1. 内部メソッドの探索 (_name)
        if (is_string($validator)) {
            $internal = '_' . $validator;
            if (method_exists($this, $internal)) {
                $result = $this->$internal(...$args);
                $endApply($result);
                return $this;
            }
        }
        // 2. クロージャの実行
        if ($validator instanceof Closure) {
            $reflection = new ReflectionFunction($validator);
            $methodName = $reflection->getName();
            if ($methodName !== '{closure}') {
                if (method_exists($this, $methodName)) {
                    return $this->apply($methodName, ...$args);
                }
                if (method_exists($this, '_' . $methodName)) {
                    return $this->apply($methodName, ...$args);
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
        // 3. その他の callable または 外部ルールクラスの探索
        if (is_callable($validator)) {
            $result = $validator($value, ...$args);
        } elseif (is_string($validator)) {
            $external = $validator;
            if (class_exists($external)) {
                $rule = new $external(...$args);
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

    public function forKey(string $key, ?string $errorMsg = null): static
    {
        $this->context->setKey($key);
        $this->errorMessage = $errorMsg ?? 'Please check the input value.';
        return $this;
    }

    public function required(?string $msg = null): static
    {
        if (!$this->hasValue()) $this->setError($msg);
        return $this;
    }

    /**
     * 条件付き required.
     *
     * - 条件が true: required($msg) を適用（以降のルールも通常通り評価）
     * - 条件が false:
     *     - elseOverwrite が指定されていれば、その値で強制上書きして以降を skip
     *     - 指定されていなければ optional($default) 扱い（else不要）
     *
     * $expect はスカラーまたは配列（複数ケース）を受け付けます。
     */
    public function requiredIf(
        string $otherKey,
        mixed $expect,
        ?string $msg = null,
        mixed $elseOverwrite = null
    ): static {
        if ($this->context->isCurrentError() || $this->context->isSkipped()) {
            return $this;
        }

        $otherValue = $this->getValueAtKey($otherKey);

        $matched = is_array($expect)
            ? in_array($otherValue, $expect, true)
            : ($otherValue === $expect);

        if ($matched) {
            return $this->required($msg);
        }

        $args = func_get_args();
        $hasElseOverwrite = array_key_exists(3, $args) || array_key_exists('elseOverwrite', $args);
        if ($hasElseOverwrite) {
            $this->context->setValidatedData($elseOverwrite);
            $this->context->setSkipped(true);
            return $this;
        }

        return $this->optional();
    }

    /**
     * 条件付き required (否定).
     *
     * $otherKey の値が $expect と一致しない場合に required.
     * 一致する場合は optional 扱い。
     */
    public function requiredUnless(
        string $otherKey,
        mixed $expect,
        ?string $msg = null,
        mixed $elseOverwrite = null
    ): static {
        if ($this->context->isCurrentError() || $this->context->isSkipped()) {
            return $this;
        }

        $otherValue = $this->getValueAtKey($otherKey);

        $matched = is_array($expect)
            ? in_array($otherValue, $expect, true)
            : ($otherValue === $expect);

        if (!$matched) {
            return $this->required($msg);
        }

        $args = func_get_args();
        $hasElseOverwrite = array_key_exists(3, $args) || array_key_exists('elseOverwrite', $args);
        if ($hasElseOverwrite) {
            $this->context->setValidatedData($elseOverwrite);
            $this->context->setSkipped(true);
            return $this;
        }

        return $this->optional();
    }

    /**
     * $otherKey が存在する場合に required.
     * それ以外は optional 扱い。
     */
    public function requiredWith(
        string $otherKey,
        ?string $msg = null,
        mixed $elseOverwrite = null
    ): static {
        if ($this->context->isCurrentError() || $this->context->isSkipped()) {
            return $this;
        }

        if ($this->context->hasKey($otherKey)) {
            return $this->required($msg);
        }

        $args = func_get_args();
        $hasElseOverwrite = array_key_exists(2, $args) || array_key_exists('elseOverwrite', $args);
        if ($hasElseOverwrite) {
            $this->context->setValidatedData($elseOverwrite);
            $this->context->setSkipped(true);
            return $this;
        }

        return $this->optional();
    }

    /**
     * $otherKey が存在しない場合に required.
     * それ以外は optional 扱い。
     */
    public function requiredWithout(
        string $otherKey,
        ?string $msg = null,
        mixed $elseOverwrite = null
    ): static {
        if ($this->context->isCurrentError() || $this->context->isSkipped()) {
            return $this;
        }

        if (!$this->context->hasKey($otherKey)) {
            return $this->required($msg);
        }

        $args = func_get_args();
        $hasElseOverwrite = array_key_exists(2, $args) || array_key_exists('elseOverwrite', $args);
        if ($hasElseOverwrite) {
            $this->context->setValidatedData($elseOverwrite);
            $this->context->setSkipped(true);
            return $this;
        }

        return $this->optional();
    }

    /**
     * コールバックが true を返した場合に required.
     * それ以外は optional 扱い。
     */
    public function requiredWhen(
        callable $call,
        ?string $msg = null,
        mixed $elseOverwrite = null
    ): static {
        if ($this->context->isCurrentError() || $this->context->isSkipped()) {
            return $this;
        }

        if ($call($this->context->getData())) {
            return $this->required($msg);
        }

        $args = func_get_args();
        $hasElseOverwrite = array_key_exists(2, $args) || array_key_exists('elseOverwrite', $args);
        if ($hasElseOverwrite) {
            $this->context->setValidatedData($elseOverwrite);
            $this->context->setSkipped(true);
            return $this;
        }

        return $this->optional();
    }

    public function optional(mixed $default = null): static
    {
        if ($this->hasValue()) {
            return $this;
        }

        $args = func_get_args();
        $hasDefault = array_key_exists(0, $args) || array_key_exists('default', $args);

        if ($hasDefault) {
            $this->context->setValidatedData($default);
        }

        $this->context->setSkipped(true);
        return $this;
    }

    public function arrayCount(?int $min = 1, ?int $max = null, ?string $msg = 'Please select the values.'): static
    {
        $value = $this->getCurrentValue();
        if (!is_array($value) || ($min !== null && count($value) < $min) || ($max !== null && count($value) > $max)) {
            $this->setError($msg);
        }
        return $this;
    }

    public function message(string $msg): static
    {
        $this->errorMessage = $msg;
        return $this;
    }

    protected function getCurrentValue(): mixed
    {
        return $this->context->getCurrentValue();
    }

    public function hasValue(): bool
    {
        return $this->context->hasValue();
    }

    protected function setError(string $msg = null): static
    {
        $currentKey = $this->context->getCurrentKey();
        $errorMsg = $msg ?? $this->errorMessage;
        if ($currentKey === '__current_item__') {
            $this->errors->add($errorMsg);
        } else {
            $this->errors->add($errorMsg, $currentKey);
        }
        $this->context->setError();
        return $this;
    }

    protected function setErrors(array $errors, string ...$path): static
    {
        $currentKey = $this->context->getCurrentKey();
        if (empty($path) && $currentKey !== '' && $currentKey !== '__current_item__') {
            $path = [$currentKey];
        }
        $this->errors->setErrors($errors, ...$path);
        $this->context->setError();
        return $this;
    }

    public function isCurrentError(): bool
    {
        return $this->context->isCurrentError();
    }

    public function isCurrentOK(): bool
    {
        return $this->context->isCurrentOK();
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

    public function getValidatedData(): array
    {
        if (!$this->isValid()) {
            throw new RuntimeException('Validation failed.');
        }
        return $this->context->getValidatedData();
    }

    protected function _string(): bool
    {
        return is_string($this->getCurrentValue());
    }

    protected function _int(?int $min = null, ?int $max = null): bool
    {
        $value = $this->getCurrentValue();
        if (!is_int($value)) return false;
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }

    protected function _filterVar(string $filter): bool
    {
        $value = $this->getCurrentValue();
        return is_string($value) && filter_var($value, $filter) !== false;
    }

    protected function _regex(string $pattern): bool
    {
        $value = $this->getCurrentValue();
        return is_string($value) && preg_match($pattern, $value) === 1;
    }

    protected function _in(array $choices): bool
    {
        return in_array($this->getCurrentValue(), $choices, true);
    }

    protected function _contains(string $needle): bool
    {
        $value = $this->getCurrentValue();
        return is_string($value) && str_contains($value, $needle);
    }

    protected function _equalTo(mixed $expect): bool
    {
        return $this->getCurrentValue() === $expect;
    }

    protected function _length(?int $min = null, ?int $max = null): bool
    {
        $value = $this->getCurrentValue();
        if (!is_string($value)) return false;
        if (function_exists('mb_strlen')) {
            $len = mb_strlen($value);
        } else {
            $len = strlen($value);
        }
        if ($min !== null && $len < $min) return false;
        if ($max !== null && $len > $max) return false;
        return true;
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
        if ($this->context->isCurrentError()) return $this;
        $value = $this->getCurrentValue();
        if (!is_array($value)) return $this->setError();

        $child = static::make($value);
        foreach ($value as $key => $item) {
            $child->forKey((string)$key)->apply($validator, ...$args);
        }
        if (!$child->isValid()) {
            $this->setErrors($child->getErrors()->toArray());
        } else {
            $this->context->setValidatedData($child->getValidatedData());
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
        if ($this->context->isCurrentError()) return $this;
        $value = $this->getCurrentValue();
        if (!is_array($value)) return $this->setError();

        $childValidator = static::make($value);
        foreach ($value as $key => $item) {
            if (!is_array($item)) {
                $childValidator->forKey((string)$key)->setError('Value is not an array.');
                continue;
            }
            $child = static::make($item);
            $callback($child);
            if (!$child->isValid()) {
                $childValidator->setErrors($child->getErrors()->toArray(), (string)$key);
            } else {
                $childValidator->forKey((string)$key);
                $childValidator->context->setValidatedData($child->getValidatedData());
            }
        }

        if (!$childValidator->isValid()) {
            $this->setErrors($childValidator->getErrors()->toArray());
        } else {
            $this->context->setValidatedData($childValidator->getValidatedData());
        }
        return $this;
    }

    public function getValueAtKey(string $key): mixed
    {
        return $this->context->getValue($key);
    }

}