<?php
namespace test\db;
use ebi\Q;

class AbcFind extends Find{
	protected function __find_conds__(): \ebi\Q{
		return Q::b(Q::eq('value1','abc'));
	}
}
