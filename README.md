# LeanValidator

An AI-friendly and simple validator for PHP.

## Features

- **Fluent Interface**: Easy to read and write validation rules.
- **Whitelist Validation**: `getValidatedData()` returns only the data that has been validated.
- **Nested Structures**: Supports validation of arrays and nested objects using `arrayApply()` and `forEach()`.
- **Flexible Rules**: Use built-in rules, closures, or any PHP callable.
- **AI-Friendly**: Simple and consistent API that is easy for AI to understand and generate.

## Installation

Use composer to install the package.

TODO: register package on packagist.org!!!

```bash
composer require wscore/lean-validator
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

Use `forKey` to specify the field and chain validation rules.

```php
$v->forKey('name')->required()->string();
$v->forKey('age')->int(18, 99);
$v->forKey('email')->email();
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

To override these messages, set `forKey` with a custom message.

```php
$v->forKey('age', 'Please enter your age (must be above 18)')
  ->required('You must be at least 18 years old.')
  ->int(18);
```

You can change the default messages simply by modifying the messages. 

```php
$v->defaultMessage = 'Invalid input!';
$v->defaultMessageRequired = 'Cannot skip this field!';
```

### Custom Error Messages

Use `message()` to set a custom message for the **next** rule in the chain.

```php
$v->forKey('age', 'Please enter your age (must be above 18)')
  ->required()
  ->message('Age must be a number')->int()
  ->message('You must be at least 18')->int(18);
```

### Optional Fields

```php
$v->forKey('extra_info')->optional()->string();

// With default value
$v->forKey('status')->optional('active')->string();
```

### Required Fields

```php
$v->forKey('name')->required()->string();

// With a custom message
$v->forKey('title')->required('Title is required')->string();
```


### Conditional Required Fields (`requiredIf`, `requiredWith`, `requiredWithout`)

Use these methods to make a field required based on another field.

#### `requiredIf(string $otherKey, mixed $expect, ?string $msg = null, mixed $elseOverwrite = null)`
Required if `$otherKey`'s value matches `$expect`.

```php
// 'state' is required only if 'country' is 'US'
$v->forKey('state')->requiredIf('country', 'US')->string();
```

#### `requiredUnless(string $otherKey, mixed $expect, ?string $msg = null, mixed $elseOverwrite = null)`
Required unless `$otherKey`'s value matches `$expect`.

```php
// 'state' is required only if 'country' is 'US'
$v->forKey('state')->requiredUnless('country', 'US')->string();
```

#### `requiredWith(string $otherKey, ?string $msg = null, mixed $elseOverwrite = null)`
Required if `$otherKey` exists in the input data (even if it's null).

```php
// 'confirm_password' is required if 'password' exists
$v->forKey('confirm_password')->requiredWith('password')->string();
```

#### `requiredWithout(string $otherKey, ?string $msg = null, mixed $elseOverwrite = null)`
Required if `$otherKey` does not exist in the input data.

```php
// 'guest_email' is required if 'user_id' is missing
$v->forKey('guest_email')->requiredWithout('user_id')->email();
```

#### `requiredWhen(callable $call, ?string $msg = null, mixed $elseOverwrite = null)`
Required if the callback `$call($data)` returns true. The `$data` contains all input data.

```php
// 'name' is required only if 'type' is 'personal'
$v->forKey('name')->requiredWhen(function($data) {
    return ($data['type'] ?? '') === 'personal';
})->string();
```

#### `elseOverwrite`
For all these methods, if the condition is not met, you can provide an `elseOverwrite` value. The field will be set to this value and further validation rules in the chain will be skipped.

```php
$v->forKey('type')->requiredIf('category', 'special', 'Required', 'default-type')->string();
```

### Built-in Rules

- `string()`: Checks if value is a string.
- `int(?int $min, ?int $max)`: Checks if value is an integer and within range.
- `float()`: Validates if the value is a float.
- `email()`: Validates email format.
- `url()`: Validates URL format.
- `regex(string $pattern)`: Validates against a regular expression.
- `alnum()`: Validates alphanumeric characters.
- `alpha()`: Validates alphabetic characters.
- `numeric()`: Validates numeric characters.
- `in(array $choices)`: Validates if the value is within the given choices.
- `contains(string $needle)`: Validates if the value contains the needle.
- `equalTo(mixed $expect)`: Validates if the value is equal to the expected value.
- `length(?int $min, ?int $max)`: Checks the length of a string.
- `filterVar(int $filter)`: Uses PHP's `filter_var`.

## Advanced Validation

### Validating Arrays of Scalars

Use `arrayApply()` to apply a rule to every element in an array.

```php
$v->forKey('tags')->arrayApply('string');
// Or with parameters
$v->forKey('scores')->arrayApply('int', 0, 100);
```

### Validating Array Size

Use `arrayCount()` to validate the number of elements in an array.

```php
$v->forKey('tags')->arrayCount(1, 5, 'Please provide 1 to 5 tags');
```

### Validating Arrays of Objects (Nested Data)

Use `forEach()` to validate complex nested structures.

```php
$data = [
    'users' => [
        ['name' => 'John', 'email' => 'john@example.com'],
        ['name' => 'Jane', 'email' => 'jane@example.com'],
    ]
];

$v = Validator::make($data);
$v->forKey('users')->forEach(function(Validator $child) {
    $child->forKey('name')->required()->string();
    $child->forKey('email')->required()->email();
});
```

### Custom Validators

You can use `apply()` to use any callable, closure, or rule class as a validation rule.

```php
// Using a closure
$v->forKey('username')->apply(fn($value) => !in_array($value, ['admin', 'root']));

// Using a closure that operates on the validator instance
$v->forKey('zip')->apply(function() {
    $this->regex('/^\d{3}-\d{4}$/');
});

// Using external functions
$v->forKey('count')->apply('is_numeric');

// Using first-class callables
$v->forKey('price')->apply($myValidator->checkPrice(...));

// Using external rule classes (instantiated automatically)
$v->forKey('token')->apply(MyCustomRule::class, $options);

// Using invokable objects
$v->forKey('price')->apply(new MyInvokableRule(), $minPrice);
```

## API Reference

### `Validator::make(array|string|numeric $data): static`
Creates a validator. If a string or number is passed, it treats it as a single item to be validated.

### `forKey(string $key, ?string $errorMsg = null): static`
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
