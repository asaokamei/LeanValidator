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

### Custom Error Messages

Use `message()` to set a custom message for the **next** rule in the chain.

```php
$v->forKey('age', 'Please enter your age')
  ->required()
  ->message('Age must be a number')->int()
  ->message('You must be at least 18')->int(18);
```

### Nullable Fields

TODO: streamline this feature!!!

```php
if ($v->hasValue('extra_info')) {
  $v->forKey('extra_info')->string();
}
```

### Quick Entry Points

TODO: this feature is not implemented yet!!!

`required()` and `arrayCount()` can be used as entry points without `forKey()`.

```php
$v->required('name', 'Name is required')->string();
$v->arrayCount('tags', 1, 5, 'Select 1 to 5 tags')->arrayApply('string');
```

### Built-in Rules

- `string()`: Checks if value is a string.
- `int(?int $min, ?int $max)`: Checks if value is an integer and within range.
- `email()`: Validates email format.
- `regex(string $pattern)`: Validates against a regular expression.
- `filterVar(int $filter)`: Uses PHP's `filter_var`.

## Advanced Validation

### Validating Arrays of Scalars

Use `arrayApply()` to apply a rule to every element in an array.

```php
$v->forKey('tags')->arrayApply('string');
// Or with parameters
$v->forKey('scores')->arrayApply('int', 0, 100);
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
    $child->required('name')->string();
    $child->required('email')->email();
});
```

### Custom Validators

You can use `apply()` to use any callable or closure as a validation rule.

```php
$v->forKey('username')->apply(function($value) {
    return !in_array($value, ['admin', 'root']);
});

// Using external functions
$v->forKey('count')->apply('is_numeric');

// Using first-class callables
$v->forKey('price')->apply($myValidator->checkPrice(...));
```

## API Reference

### `Validator::make(array|string|numeric $data): static`
Creates a validator. If a string or number is passed, it treats it as a single item to be validated.

### `getValidatedData(): array`
Returns the validated data. Throws `RuntimeException` if validation failed.

### `isValid(): bool`
Returns true if there are no validation errors.

### `getErrorsFlat(): array`
Returns a flat array of error messages where keys are the field names.
