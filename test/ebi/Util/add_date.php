<?php

eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('1976/10/04','38 year')));
eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('2014/10/03','1 day')));
eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('2014/09/04','1 month')));

eq('2014/10/04',date('Y/m/d',\ebi\Util::add_date('2014/11/04','-1 month')));
