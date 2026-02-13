<?php
namespace Wscore\SandBoxes\LeanValidator;

class Lean extends Validator
{
    /**
     * @method $this int(int $min = null, int $max = null, ?string $msg = null)
     * @method $this email(?string $msg = null)
     * @method $this url(?string $msg = null)
     */
    public function __call(string $name, array $args): static
    {
        // 1. ガード節：既にエラーがある場合は即リターン（各メソッドにif文を書かなくて済む）
        if ($this->currentErrFlag) {
            return $this;
        }

        // 2. 内部メソッド（アンダースコア付き）の探索
        $internalMethod = '_' . $name;
        if (method_exists($this, $internalMethod)) {
            return $this->$internalMethod(...$args);
        }

        // 3. 外部ルールクラスの探索 (Asao\Rules\JpPhone など)
        $externalClass = "Asao\\Rules\\" . ucfirst($name);
        if (class_exists($externalClass)) {
            $rule = new $externalClass(...$args);
            // ルール側で判定。setError呼び出しなどはRuleオブジェクトに任せるか、戻り値で判定
            if (!$rule->passes($this->getCurrentValue())) {
                $this->setError($args[count($args) - 1] ?? null); // 最後の引数をメッセージと仮定
            }
            return $this;
        }

        throw new \BadMethodCallException("バリデーションルール [{$name}] は定義されていません。");
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
        return $this;
    }

}