# LeanValidator

[![PhpUnit](https://github.com/asaokamei/LeanValidator/actions/workflows/phpunit.yml/badge.svg)](https://github.com/asaokamei/LeanValidator/actions/workflows/phpunit.yml)

An AI-friendly and simple validator for PHP.

## Features

- **Fluent Interface**: Easy to read and write validation rules.
- **Whitelist Validation**: `getValidatedData()` returns only the data that has been validated.
- **Nested Structures**: Supports validation of arrays and nested objects using `asList()` and `asObject()`.
- **Flexible Rules**: Use built-in rules, closures, or any PHP callable.
- **AI-Friendly**: Simple and consistent API that is easy for AI to understand and generate.

### Sanitizer:

This package also provides a sanitization utility that can be used to clean and transform input data.

- **Sanitization**: Automatic UTF-8 conversion and trimming of strings. Supports custom rules and nested data using dot-notation and wildcards.


## Installation

Use composer to install the package.

```bash
composer require wscore/leanvalidator
```

## Basic Usage

### Initialization

You can create a validator instance using the `make` method.

```php
use Wscore\LeanValidator\Validator;

$data = [
    'name' => 'John Doe',
    'age' => 25,
    'email' => 'john@example.com'
];

$v = Validator::make($data);
```

### Defining Rules

Use `field` to specify the field and chain validation rules.

```php
$v->field('name')->required()->string();
$v->field('age')->int()->between(18, 99);
$v->field('email')->email();
```

### Checking Results

```php
if ($v->isValid()) {
    // Get only the validated data
    $validated = $v->getValidatedData();
} else {
    // Get error messages as a flat array [key => message]
    $errors = $v->getErrorsFlat();
}
```

## Rules and Messages

### Default Error Messages

The validator provides two default error messages for common validation rules.
- `required`: "This field is required."
- `email`: "Please enter a valid email address."

To override these messages, set `field` with a custom message.

```php
$v->field('age', 'Please enter your age (must be above 18)')
  ->required('You must be at least 18 years old.')
  ->int()
  ->min(18);
```

You can change the default messages simply by modifying the messages. 

```php
$v->defaultMessage = 'Invalid input!';
$v->defaultMessageRequired = 'Cannot skip this field!';
```

### Custom Error Messages

Use `message()` to set a custom message for the **next** rule in the chain.

```php
$v->field('age', 'Please enter your age (must be above 18)')
  ->required()
  ->message('Age must be an integer')->int()
  ->message('You must be at least 18')->min(18);
```

### Optional Fields

```php
$v->field('extra_info')->optional()->string();

// With default value
$v->field('status')->optional('active')->string();
```

### Required Fields

```php
$v->field('name')->required()->string();

// With a custom message
$v->field('title')->required('Title is required')->string();
```


### Conditional Required Fields (`requiredIf`, `requiredWith`, `requiredWithout`)

Use these methods to make a field required based on another field.

#### `requiredIf(string $otherKey, mixed $expect, ?string $msg = null, mixed $elseOverwrite = null)`
Required if `$otherKey`'s value matches `$expect`.

```php
// 'state' is required only if 'country' is 'US'
$v->field('state')->requiredIf('country', 'US')->string();
```

#### `requiredUnless(string $otherKey, mixed $expect, ?string $msg = null, mixed $elseOverwrite = null)`
Required unless `$otherKey`'s value matches `$expect`.

```php
// 'state' is required only if 'country' is 'US'
$v->field('state')->requiredUnless('country', 'US')->string();
```

#### `requiredWith(string $otherKey, ?string $msg = null, mixed $elseOverwrite = null)`
Required if `$otherKey` exists in the input data (even if it's null).

```php
// 'confirm_password' is required if 'password' exists
$v->field('confirm_password')->requiredWith('password')->string();
```

#### `requiredWithout(string $otherKey, ?string $msg = null, mixed $elseOverwrite = null)`
Required if `$otherKey` does not exist in the input data.

```php
// 'guest_email' is required if 'user_id' is missing
$v->field('guest_email')->requiredWithout('user_id')->email();
```

#### `requiredWhen(callable $call, ?string $msg = null, mixed $elseOverwrite = null)`
Required if the callback `$call($data)` returns true. The `$data` contains all input data.

```php
// 'name' is required only if 'type' is 'personal'
$v->field('name')->requiredWhen(function($data) {
    return ($data['type'] ?? '') === 'personal';
})->string();
```

#### `elseOverwrite`
For all these methods, if the condition is not met, you can provide an `elseOverwrite` value. The field will be set to this value and further validation rules in the chain will be skipped.

```php
$v->field('type')->requiredIf('category', 'special', 'Required', 'default-type')->string();
```

### Built-in Rules

- `string()`: Checks if value is a string.
- `int()`: Checks that the value is a PHP integer (`is_int`).
- `min(int $min)`: Value must be a PHP integer and `>= $min`.
- `max(int $max)`: Value must be a PHP integer and `<= $max`.
- `between(int $min, int $max)`: Value must be a PHP integer and between `$min` and `$max` (inclusive; if `$min > $max` the bounds are swapped).
- `float()`: Validates if the value is a float.
- `email()`: Validates email format.
- `url()`: Validates URL format.
- `regex(string $pattern)`: Validates against a regular expression.
- `alnum()`: Validates alphanumeric characters.
- `alpha()`: Validates alphabetic characters.
- `digit()`: Validates that the value is a string containing only ASCII digits (`0`–`9`), one or more characters.
- `numeric()`: Validates that the value is numeric in the PHP sense (`is_numeric()`), including int/float and numeric strings (e.g. `"123"`, `"1.5"`).
- `in(array $choices)`: Validates if the value is within the given choices.
- `contains(string $needle)`: Validates if the value contains the needle.
- `equalTo(mixed $expect)`: Validates if the value is equal to the expected value.
- `length(?int $min, ?int $max)`: Checks the length of a string.
- `filterVar(int $filter)`: Uses PHP's `filter_var`.

## Advanced Validation

### Validating Arrays of Scalars

Use `asList()` to apply a rule to every element in an array.

```php
$v->field('tags')->asList('string');
// Integer elements between 0 and 100 (each element must be a PHP int)
$v->field('scores')->asList('between', 0, 100);
```

### Validating Array Size

Use `arrayCount()` to validate the number of elements in an array.

```php
$v->field('tags')->arrayCount(1, 5, 'Please provide 1 to 5 tags');
```

### Validating Nested Objects (Associative Arrays)

Use `asObject()` to validate a nested associative array without using dot-notation in `field`.
Only the validated fields will be included in `getValidatedData()` (whitelist).

```php
$data = ['address' => [
    'post_code' => '123-1234', 
    'town' => 'TOKYO', 
    'city' => 'Meguro',
]];
$v = Validator::make($data);

$v->field('address')->required()->asObject(function (Validator $child) {
    $child->field('post_code')->required()->regex('/^\d{3}-\d{4}$/');
    $child->field('town')->required()->string();
    $child->field('city')->required()->string();
});
if ($v->isValid()) {
    $validated = $v->getValidatedData();
} // ['address' => ['post_code' => '123-1234', 'town' => 'TOKYO', 'city' => 'Meguro']] }
```
### Validating Arrays of Objects (Nested Data)

Use `asListObject()` to validate complex nested structures.

```php
$data = [
    'users' => [
        ['name' => 'John', 'email' => 'john@example.com'],
        ['name' => 'Jane', 'email' => 'jane@example.com'],
    ]
];

$v = Validator::make($data);
$v->field('users')->asListObject(function(Validator $child) {
    $child->field('name')->required()->string();
    $child->field('email')->required()->email();
});
```

### Custom Validators

You can use `apply()` to use any callable, closure, or rule class as a validation rule.

```php
// Using a closure
$v->field('username')->apply(fn($value) => !in_array($value, ['admin', 'root']));

// Using a closure that operates on the validator instance
$v->field('zip')->apply(function() {
    $this->regex('/^\d{3}-\d{4}$/');
});

// Using external functions
$v->field('count')->apply('is_numeric');

// Using first-class callables
$v->field('price')->apply($myValidator->checkPrice(...));

// Using external rule classes (instantiated automatically)
$v->field('token')->apply(MyCustomRule::class, $options);

// Using invokable objects
$v->field('price')->apply(new MyInvokableRule(), $minPrice);
```

### Extending: Custom Rules and IDE Completion

You can add project-specific rules **with IDE code completion** by extending `ValidatorRules` and overriding `createRules()` on your Validator (or ValidatorData) subclass.

1. **Extend ValidatorRules** and add your methods (or register names in `$rules` and use `@method` in the docblock).
2. **Override `createRules()`** in your Validator subclass to return your rules instance.
3. **Override `field()` with `@return YourValidatorRules`** so that `$v->field('x')` is inferred as your class and your custom methods appear in autocomplete.

```php
use Wscore\LeanValidator\Validator;
use Wscore\LeanValidator\ValidatorData;
use Wscore\LeanValidator\ValidatorRules;

class MyValidatorRules extends ValidatorRules
{
    public function postCode(): static
    {
        return $this->regex('/^\d{3}-\d{4}$/');
    }

    public function hiragana(): static
    {
        return $this->regex('/^[\x{3040}-\x{309F}\s]+$/u');
    }
}

class MyValidator extends Validator
{
    protected function createRules(): ValidatorRules
    {
        return new MyValidatorRules($this);
    }

    /** @return MyValidatorRules */
    public function field(string $key, ?string $errorMsg = null): ValidatorRules
    {
        return parent::field($key, $errorMsg);
    }
}

// Usage: IDE will suggest postCode() and hiragana()
$v = MyValidator::make($data);
$v->field('zip')->required()->postCode();
$v->field('name_kana')->required()->hiragana();
```

### Language- or context-specific Rules (Japanese, etc.)

For Japanese-specific validations, use the `Wscore\LeanValidator\Rule\Ja` class.

```php
use Wscore\LeanValidator\Rule\Ja;

$v = Validator::make($data);
$v->field('name_kana')->required()->apply(Ja::kana());
$v->field('zip')->required()->apply(Ja::zip());
```

Available rules in `Ja` class:
- `hiragana()`: Hiragana only.
- `katakana()`: Katakana only.
- `kana()`: Hiragana and Katakana.
- `hankakuKana()`: Hankaku-Katakana.
- `kanji()`: Kanji only.
- `zenkaku()`: Zenkaku characters (non-ASCII).
- `zip()`: Japanese Zip code (000-0000).
- `tel()`: Japanese Phone number.

#### Network-specific Rules (IP, UUID, etc.)

For network-related validations, use the `Wscore\LeanValidator\Rule\Net` class.

```php
use Wscore\LeanValidator\Rule\Net;
use Wscore\LeanValidator\Rule\Date;

$v = Validator::make($data);
$v->field('ip_address')->required()->apply(Net::ip());
$v->field('uuid')->required()->apply(Net::uuid());
$v->field('start_date')->required()->apply(Date::htmlDate());
$v->field('start_date')->apply(Date::notFutureDate());
```

Available rules in `Net` class:
- `ip(int $flags = 0)`: IP address (v4 or v6).
- `ipv4()`: IPv4 address.
- `ipv6()`: IPv6 address.
- `mac()`: MAC address.
- `uuid()`: UUID.
- `domain()`: Domain name.

#### Date-specific Rules (HTML date/time, etc.)

For HTML date/time formats and date constraints, use the `Wscore\LeanValidator\Rule\Date` class.

```php
use Wscore\LeanValidator\Rule\Date;

$v = Validator::make($data);
$v->field('birthday')->required()->apply(Date::htmlDate());
$v->field('appointment_at')->apply(Date::htmlDateTimeLocal());
$v->field('work_time')->apply(Date::htmlTime());
$v->field('billing_month')->apply(Date::htmlMonth());
$v->field('reporting_week')->apply(Date::htmlWeek());

// Disallow future dates (including today is allowed)
$v->field('birthday')->apply(Date::notFutureDate());
```

Available rules in `Date` class:
- `htmlDate()`: HTML5 `<input type="date">` (`Y-m-d`).
- `htmlMonth()`: HTML5 `<input type="month">` (`Y-m`).
- `htmlTime()`: HTML5 `<input type="time">` (`H:i` or `H:i:s`).
- `htmlDateTimeLocal()`: HTML5 `<input type="datetime-local">` (`Y-m-d\TH:i` or `Y-m-d\TH:i:s`).
- `htmlWeek()`: HTML5 `<input type="week">` (`YYYY-Www`).
- `notFutureDate(?string $format = 'Y-m-d')`: Rejects dates strictly in the future, using the given format.

You can also create a Rules subclass for better IDE support:

```php
class ValidatorRulesJa extends ValidatorRules
{
    public function hiragana(): static { return $this->apply(Ja::hiragana()); }
    public function zip(): static { return $this->apply(Ja::zip()); }
}
```

Rules added with `addRule()` or by merging callables into `$this->rules` in the constructor (same shape as `Sanitizer::$rules`: `fn ($value) => bool`, e.g. `'ip' => fn ($v) => is_string($v) && filter_var($v, FILTER_VALIDATE_IP) !== false`) work with `apply('name')` and `__call`, but will not show in IDE completion unless you add a corresponding `@method` on your Rules subclass.

## API Reference

### `Validator::make(array|string|numeric $data): static`
Creates a validator. If a string or number is passed, it treats it as a single item to be validated.

### `field(string $key, ?string $errorMsg = null): static`
Specifies the field to validate. Optionally sets a default error message for any rule in the chain.

### `required(?string $msg = null): static`
Marks the field as required.

### `optional(mixed $default = null): static`
Marks the field as optional. If the field is missing, it will be set to the default value.

### `requiredIf(string $otherKey, mixed $expect, ?string $msg = null, mixed $elseOverwrite = null): static`
Marks the field as required if the value of `$otherKey` matches `$expect`. 
- If matched: acts like `required($msg)`.
- If not matched: 
    - If `$elseOverwrite` is provided, sets the field to this value and skips further validation.
    - Otherwise, acts like `optional()`.

### `requiredWith(string $otherKey, ?string $msg = null, mixed $elseOverwrite = null): static`
Marks the field as required if `$otherKey` exists.
- If exists: acts like `required($msg)`.
- Otherwise: acts like `optional()` or overwrites if `$elseOverwrite` is provided.

### `requiredWithout(string $otherKey, ?string $msg = null, mixed $elseOverwrite = null): static`
Marks the field as required if `$otherKey` does not exist.
- If not exists: acts like `required($msg)`.
- Otherwise: acts like `optional()` or overwrites if `$elseOverwrite` is provided.

### `requiredWhen(callable $call, ?string $msg = null, mixed $elseOverwrite = null): static`
Marks the field as required if `$call($data)` returns true.
- If true: acts like `required($msg)`.
- Otherwise: acts like `optional()` or overwrites if `$elseOverwrite` is provided.

### `isValid(): bool`
Returns true if there are no validation errors.

### `getValidatedData(): array`
Returns the validated data. Throws `RuntimeException` if validation failed.

### `getErrorsFlat(): array`
Returns a flat array of error messages where keys are the field names.


## Sanitizer Features

The `Sanitizer` class provides various methods to clean and transform input data.

### Default Sanitization

- **UTF-8 Conversion**: Ensures strings are valid UTF-8.
- **Trimming**: Removes surrounding whitespace using Unicode-aware regex.

### Built-in Rules

Use these methods to apply specific transformations:

- `toUtf8(...$fields)`: Ensures valid UTF-8.
- `toTrim(...$fields)`: Trims whitespace.
- `toDigits(...$fields)`: Removes all non-digit characters.
- `toLower(...$fields)`: Converts to lowercase.
- `toUpper(...$fields)`: Converts to uppercase.
- `toKana(...$fields)`: Converts to Zenkaku-Kana (needs `mbstring`).
- `toHankaku(...$fields)`: Converts to Hankaku-Kana (needs `mbstring`).
- `toZenkaku(...$fields)`: Converts to Zenkaku-Kana/Alphanumeric (needs `mbstring`).

### Skipping Sanitization

- `skip(...$fields)`: Skips all sanitization (useful for passwords).
- `skipTrim(...$fields)`: Skips only the trimming process.

### Dot-Notation and Wildcards

You can target nested data using dot-notation and wildcards.

```php
$s->toDigits('user.tel');           // Target 'tel' inside 'user'
$s->toDigits('items.*.code');       // Target 'code' in all elements of 'items'
$s->skipTrim('user.*');             // Skip trim for all direct children of 'user'
```

### Adding Custom Rules

You can add your own sanitization rules using `addRule()`.

```php
$s = new Sanitizer();
$s->addRule('stars', function($value) {
    return str_repeat('*', strlen($value));
});

$s->apply('stars', 'password');
```

### Data Sanitization

By default, the `Validator` can sanitize input data using the `Sanitizer` class.
To apply sanitization, you must explicitly call the `sanitize()` method before validation.

```php
use Wscore\LeanValidator\Sanitizer;

$data = [
    'name' => '  John Doe  ',
    'tel' => '03-1234-5678',
    'password' => '  secret  ',
    'items' => [
        ['code' => ' A-123 '],
        ['code' => ' B-456 '],
    ]
];

$s = new Sanitizer();

// Configure sanitization
$s->skip('password')           // Do not trim or clean password
    ->toDigits('tel')             // Remove non-digit characters
    ->toDigits('items.*.code');   // Use dot-notation and wildcards for nested data
// Apply sanitization
$cleanData = $s->clean($data);
// $cleanData['name'] => 'John Doe'
// $cleanData['tel'] => '0312345678'
// $cleanData['password'] => '  secret  '
// $cleanData['items'][0]['code'] => '123'
```

By default, the `Sanitizer` converts strings to UTF-8 and trims surrounding whitespace.
If you don't call `sanitize()`, the validation will be performed on the raw input data.


