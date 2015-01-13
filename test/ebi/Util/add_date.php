<?php

eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('38 year','1976/10/04')));
eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('1 day','2014/10/03')));
eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('1 month','2014/09/04')));

eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('-1 month','2014/11/04')));

try{
	eq('2014/10/04',\ebi\Util::add_date('1','2014/11/04'));
	fail();
}catch(\InvalidArgumentException $e){

}

