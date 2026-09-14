<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * 日付情報のみのモデル
 */
trait TraitDate{
	#[Prop(type:'datetime')]
	protected ?int $create_date = null;
	protected ?float $number = null;
}