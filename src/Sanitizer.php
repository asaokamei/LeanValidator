<?php
declare(strict_types=1);

namespace Wscore\LeanValidator;

/**
 * @method self toUtf8(...$fields)
 * @method self toTrim(...$fields)
 * @method self toDigits(...$fields)
 * @method self toLower(...$fields)
 * @method self toUpper(...$fields)
 * @method self toKana(...$fields)
 * @method self toHankaku(...$fields)
 * @method self toZenkaku(...$fields)
 */
class Sanitizer
{
    private array $rules = [];
    private array $schema = [];
    private array $globalDefault = [];

    public function __construct()
    {
        // 1. 基本的な加工ルールの定義
        $this->rules = [
            'utf8' => fn($v) => \preg_match('//u', $v) ? $v : '',
            'trim' => fn($v) => \preg_replace('/^[\\p{Z}\\s]+|[\\p{Z}\\s]+$/u', '', $v),
            'digits' => fn($v) => \preg_replace('/[^0-9]/', '', $v),
            'lower' => fn($v) => \strtolower($v),
            'upper' => fn($v) => \strtoupper($v),
        ];
        $this->globalDefault = ['utf8', 'trim'];

        if (\extension_loaded('mbstring')) {
            $this->rules['utf8'] = fn($v) => \mb_convert_encoding($v, 'UTF-8', 'UTF-8');
            $this->rules['kana'] = fn($v) => \mb_convert_kana($v, 'KVa', 'UTF-8');
            $this->rules['hankaku'] = fn($v) => \mb_convert_kana($v, 'kas', 'UTF-8');
            $this->rules['zenkaku'] = fn($v) => \mb_convert_kana($v, 'KVAS', 'UTF-8');
        }
    }

    // --- 設定用メソッド (Fluent Interface) ---

    /** 全ての処理をスキップ (パスワード等) */
    public function skip(...$fields): self
    {
        foreach ($fields as $f) $this->schema[$f] = [];
        return $this;
    }

    /** trim処理だけをスキップ */
    public function skipTrim(...$fields): self
    {
        foreach ($fields as $f) $this->schema[$f] = ['utf8'];
        return $this;
    }

    /** 内部的なルール登録用 */
    public function apply(string $rule, ...$fields): self
    {
        foreach ($fields as $f) {
            $current = $this->schema[$f] ?? $this->globalDefault;
            $this->schema[$f] = array_unique(array_merge($current, [$rule]));
        }
        return $this;
    }

    public function addRule(string $ruleName, callable $callback): self
    {
        $this->rules[$ruleName] = $callback;
        return $this;
    }

    public function __call(string $name, array $arguments): self
    {
        if (str_starts_with($name, 'to')) {
            $rule = strtolower(substr($name, 2));
            if (isset($this->rules[$rule])) {
                return $this->apply($rule, ...$arguments);
            }
        }
        throw new \BadMethodCallException("Method {$name} does not exist.");
    }

    // --- 実行処理 ---

    /** サニタイズ実行 */
    public function clean(array $data): array
    {
        return $this->runRecursive($data, $this->schema);
    }

    private function runRecursive(array $data, array $schema, string $prefix = ''): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $fullKey = ($prefix === '') ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result[$key] = $this->runRecursive($value, $schema, $fullKey);
            } else {
                $result[$key] = $this->applyRules($value, $fullKey, $schema);
            }
        }
        return $result;
    }

    private function applyRules($value, string $fullKey, array $schema)
    {
        if (!is_string($value)) return $value;

        // フィールド名に合致するルールを取得（なければデフォルト）
        $ruleKeys = $this->findRules($fullKey, $schema);

        foreach ($ruleKeys as $ruleName) {
            if (isset($this->rules[$ruleName])) {
                $value = ($this->rules[$ruleName])($value);
            }
        }
        return $value;
    }

    private function findRules(string $fullKey, array $schema): array
    {
        if (isset($schema[$fullKey])) return $schema[$fullKey];

        // ワイルドカード (*) 対応
        foreach ($schema as $pattern => $rules) {
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace(['.', '*'], ['\\.', '[^.]+'], $pattern) . '$/';
                if (preg_match($regex, $fullKey)) return $rules;
            }
        }
        return $this->globalDefault;
    }
}
