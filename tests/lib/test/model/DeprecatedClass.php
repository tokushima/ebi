<?php
namespace test\model;
use \ebi\Attribute\Prop;
/**
 * 推奨しないクラス
 *
 * @deprecated 2017-04-01
 */
class DeprecatedClass extends \ebi\Obj{
	#[Prop(summary:'@deprecated 2017-03-27 あああ')]
	protected ?string $aaa = null;
	#[Prop(expose:false)]
	protected ?string $bbb = null;
	#[Prop(summary:'@deprecated 2017-03-28 いいい', expose:false)]
	protected ?string $ccc = null;
	protected ?string $ddd = null;
}
