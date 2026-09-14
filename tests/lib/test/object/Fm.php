<?php
namespace test\object;
use \ebi\Attribute\Prop;
class Fm extends \ebi\Obj{
	/** 数値 */
	protected ?int $aaa = null;
	/** 真偽値 */
	protected ?bool $bbb = null;
	/** 数値 */
	#[Prop(expose:false)]
	protected ?int $ccc = null;
	/** 日付型 */
	#[Prop(type:'datetime')]
	protected ?int $ddd = null;

	protected function __get_ccc__(){
		$this->ddd(time());
		return 2;
	}

	public function aaabbb(){
		return $this->aaa.$this->bbb;
	}
}
