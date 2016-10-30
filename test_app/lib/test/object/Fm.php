<?php
namespace test\object;
/**
 * @var integer $aaa 数値
 * @var boolean $bbb 真偽値
 * @var integer $ccc 数値 @['hash'=>false]
 * @var timestamp $ddd 日付型
 */
class Fm extends \ebi\Object{
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
