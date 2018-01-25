<?php
namespace test\Util;

class ParentClass{
	public function get_name(){
		return get_called_class();
	}
}