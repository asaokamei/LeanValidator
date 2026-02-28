<?php
declare(strict_types=1);

namespace Wscore\LeanValidator;

/**
 * エントリポイント。データ管理は ValidatorData、ルール適用は field() が返す ValidatorRules に委譲する。
 *
 * 利用例:
 *   $v = Validator::make($data);
 *   $v->field('name')->required()->string();
 *   $v->field('age')->int(18, 99);
 *   if ($v->isValid()) { $safe = $v->getValidatedData(); }
 *
 * @see ValidatorData データ・現在キー・結果の管理
 * @see ValidatorRules ルール適用（required, apply, asList, asObject, asListObject など）
 */
class Validator extends ValidatorData
{
}
