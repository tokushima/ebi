<?php
namespace test\model;

class Member1 extends \ebi\User{
	use \ebi\UserRole;
	
	protected $id;
	protected $name;
}