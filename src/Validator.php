<?php
declare(strict_types=1);

namespace Wscore\LeanValidator;

/**
 * エントリポイント。データ管理は ValidatorData、ルール適用は forKey() が返す ValidatorRules に委譲する。
 *
 * 利用例:
 *   $v = Validator::make($data);
 *   $v->forKey('name')->required()->string();
 *   $v->forKey('age')->int(18, 99);
 *   if ($v->isValid()) { $safe = $v->getValidatedData(); }
 *
 * @see ValidatorData データ・現在キー・結果の管理
 * @see ValidatorRules ルール適用（required, apply, arrayApply, nest, forEach など）
 */
class Validator extends ValidatorData
{
}
