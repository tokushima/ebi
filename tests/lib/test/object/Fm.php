<?php
namespace test\object;
/**
 * @var int $aaa 数値
 * @var bool $bbb 真偽値
 * @var int $ccc 数値 @['hash'=>false]
 * @var timestamp $ddd 日付型
 */
class Fm extends \ebi\Obj{
	protected $aaa;
	protected $bbb;
	protected $ccc;
	protected $ddd;

	protected function __get_ccc__(){
		$this->ddd(time());
		return 2;
	}
	
	public function aaabbb(){
		return $this->aaa.$this->bbb;
	}
}
