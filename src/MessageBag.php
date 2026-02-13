<?php
namespace Wscore\LeanValidator;


class MessageBag
{
    private array $messages = [];

    public function __construct(array $messages = [])
    {
        foreach ($messages as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $message) {
                    $this->add((string)$message, (string)$key);
                }
                continue;
            }
            $this->add((string)$value, (string)$key);
        }
    }

    public function add(string $message, string ...$path): void
    {
        $key = self::buildPath($path);
        $this->messages[$key][] = $message;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->messages);
    }

    public function get(string $key): array
    {
        return $this->messages[$key] ?? [];
    }

    public function first(string $key): ?string
    {
        return $this->messages[$key][0] ?? null;
    }

    public function getFromFormName(string $name): array
    {
        return $this->get(self::normalizeFormName($name));
    }

    public function firstFromFormName(string $name): ?string
    {
        return $this->first(self::normalizeFormName($name));
    }

    public function all(): array
    {
        $all = [];
        foreach ($this->messages as $items) {
            foreach ($items as $message) {
                $all[] = $message;
            }
        }
        return $all;
    }

    public function toArray(): array
    {
        return $this->messages;
    }

    public function isEmpty(): bool
    {
        return empty($this->messages);
    }

    private static function buildPath(array $path, string $separator = '.'): string
    {
        $segments = [];
        foreach ($path as $segment) {
            $segment = (string)$segment;
            if ($segment === '') {
                continue;
            }
            $segments[] = $segment;
        }
        return implode($separator, $segments);
    }

    private static function normalizeFormName(string $name, string $separator = '.'): string
    {
        if ($name === '') {
            return '';
        }
        $normalized = str_replace(['[', ']'], [$separator, ''], $name);
        return self::buildPath(explode($separator, $normalized), $separator);
    }
}
