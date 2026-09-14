<?php
namespace test\model;
use \ebi\Attribute\Prop;
/**
 * 推奨しないプロパティ
 */
class DeprecatedModel extends \ebi\Obj{
	#[Prop(summary:'@deprecated 2017-03-27 あああ')]
	protected ?string $aaa = null;
	#[Prop(expose:false)]
	protected ?string $bbb = null;
	#[Prop(summary:'@deprecated 2017-03-28 いいい', expose:false)]
	protected ?string $ccc = null;
	protected ?string $ddd = null;
	protected ?\test\model\DeprecatedClass $eee = null;

	/**
	 * @deprecated 2017-03-29 ううう
	 */
	public function hoge(){

	}
}
