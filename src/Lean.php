<?php
namespace Wscore\LeanValidator;

class Lean extends Validator
{
    /**
     * @method $this int(?int $min = null, ?int $max = null, ?string $msg = null)
     * @method $this email(?string $msg = null)
     * @method $this regex(string $pattern, ?string $msg = null)
     * @method $this sameWith(mixed $compareTarget, ?string $msg = null)
     * @method $this unique(\PDO $db, string $table, ?string $msg = null)
     */
    public function __call(string $name, array $args): static
    {
        // 1. ガード節：既にエラーがあるキーなら何もしない（即終了）
        if ($this->currentErrFlag) {
            return $this;
        }

        // 2. 内部メソッドの探索 (_name)
        $internal = '_' . $name;
        if (method_exists($this, $internal)) {
            return $this->$internal(...$args);
        }

        // 3. 外部ルールクラスの探索 (__invoke)
        $external = "Asao\\Rules\\" . ucfirst($name);
        if (class_exists($external)) {
            // 最後の引数が string かつ、メソッドの期待する引数より多ければメッセージとみなす
            $msg = (count($args) > 0 && is_string(end($args))) ? array_pop($args) : null;

            // インスタンス化して実行（DIが必要な場合は $args に入っている想定）
            $rule = new $external(...$args);

            if (!$rule($this->getCurrentValue())) {
                $this->setError($msg);
            } else {
                $this->validatedData[$this->currentKey] = $this->getCurrentValue();
            }
            return $this;
        }

        throw new \BadMethodCallException("Rule [{$name}] is not defined.");
    }
    /**
     * 実際のロジックはアンダースコア付きで定義
     * ifチェックが不要になり、ロジックが超Leanに！
     */
    protected function _int(?int $min = null, ?int $max = null, ?string $msg = null): static
    {
        $value = $this->getCurrentValue();
        if (!is_int($value) || ($min !== null && $value < $min) || ($max !== null && $value > $max)) {
            return $this->setError($msg);
        }
        $this->validatedData[$this->currentKey] = $value;
        return $this;
    }

}