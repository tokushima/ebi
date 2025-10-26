<?php

eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('38 year','1976/10/04')));
eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('1 day','2014/10/03')));
eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('1 month','2014/09/04')));

eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('-1 month','2014/11/04')));

try{
	eq('2014/10/04',\ebi\Util::add_date('1','2014/11/04'));
	fail();
}catch(\ebi\exception\InvalidArgumentException $e){

}


eq('2016/10/03 00:00:00',date('Y/m/d H:i:s',\ebi\Util::add_date('yesterday','2016/10/04 12:34:56')));
eq('2016/10/04 00:00:00',date('Y/m/d H:i:s',\ebi\Util::add_date('today','2016/10/04 12:34:56')));
eq('2016/10/05 00:00:00',date('Y/m/d H:i:s',\ebi\Util::add_date('tomorrow','2016/10/04 12:34:56')));
eq('2016/10/01 00:00:00',date('Y/m/d H:i:s',\ebi\Util::add_date('first','2016/10/04 12:34:56')));
eq('2016/10/31 23:59:59',date('Y/m/d H:i:s',\ebi\Util::add_date('last','2016/10/04 12:34:56')));



