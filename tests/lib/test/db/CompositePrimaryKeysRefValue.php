<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * 末尾 id1 = 自テーブル ref_id で閉じる case1（Prop::SELF）の実証。
 * type_id / ref_id は親 CompositePrimaryKeysRef の自テーブル列。
 */
class CompositePrimaryKeysRefValue extends \test\db\CompositePrimaryKeysRef{
	#[Prop(from: [['type_id', CompositePrimaryKeys::class, 'id2'], ['id1', Prop::SELF, 'ref_id']])]
	protected ?string $value = null;
}
