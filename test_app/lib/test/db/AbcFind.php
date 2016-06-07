<?php
namespace test\db;
use ebi\Q;

class AbcFind extends Find{
	protected function __find_conds__(){
		return Q::b(Q::eq('value1','abc'));
	}
}
