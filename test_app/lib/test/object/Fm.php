<?php
namespace test\object;
/**
 * @var integer $aaa
 * @var boolean $bbb
 * @var integer $ccc
 * @var timestamp $ddd
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
}
