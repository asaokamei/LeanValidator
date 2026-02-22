<?php
declare(strict_types=1);

namespace Wscore\LeanValidator;

class Sanitizer
{
    private array $rules = [];
    private array $schema = [];
    private array $globalDefault = ['utf8', 'trim'];

    public function __construct()
    {
        // 1. 基本的な加工ルールの定義
        $this->rules = [
            'utf8' => fn($v) => \mb_convert_encoding($v, 'UTF-8', 'UTF-8'),
            'trim' => fn($v) => \preg_replace('/^[\\p{Z}\\s]+|[\\p{Z}\\s]+$/u', '', $v),
            'kana' => fn($v) => \mb_convert_kana($v, 'KVa', 'UTF-8'), // カナ全角・英数半角
            'digits' => fn($v) => \preg_replace('/[^0-9]/', '', $v),
            'lower' => fn($v) => \mb_strtolower($v, 'UTF-8'),
        ];
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

    /** 数字のみに変換 */
    public function toDigits(...$fields): self
    {
        return $this->apply('digits', ...$fields);
    }

    /** 日本語正規化 (半角カナ→全角 / 全角英数→半角) */
    public function toKana(...$fields): self
    {
        return $this->apply('kana', ...$fields);
    }

    /** 小文字に変換 */
    public function toLower(...$fields): self
    {
        return $this->apply('lower', ...$fields);
    }

    /** 内部的なルール登録用 */
    private function apply(string $rule, ...$fields): self
    {
        foreach ($fields as $f) {
            $current = $this->schema[$f] ?? $this->globalDefault;
            $this->schema[$f] = array_unique(array_merge($current, [$rule]));
        }
        return $this;
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
