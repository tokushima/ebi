<?php
namespace test\flow\model;

class Note extends \ebi\Dao{
	use \ebi\DaoBasicProps;
	
	protected $value;
	protected $vote = 0;
}

