<?php
namespace test\flow\model;

class Note extends \ebi\Dao{
	use \ebi\DaoBasicProps;
	
	protected $value;
	protected $vote = 0;

	protected function __before_save__(): void{
		if($this->vote > 5){
			$this->vote = 5;
		}else if($this->vote < 0){
			$this->vote = 0;
		}
	}
}

