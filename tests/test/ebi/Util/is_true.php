<?php
eq(true,\ebi\Util::is_true(true));
eq(false,\ebi\Util::is_true(false));

eq(true,\ebi\Util::is_true('true'));
eq(false,\ebi\Util::is_true('false'));

eq(false,\ebi\Util::is_true([]));
eq(false,\ebi\Util::is_true([1]));
eq(false,\ebi\Util::is_true([true]));

eq(true,\ebi\Util::is_true(1));
eq(false,\ebi\Util::is_true(0));
eq(false,\ebi\Util::is_true(100));


eq(true,\ebi\Util::is_true(true,true));
eq(false,\ebi\Util::is_true(true,false));

eq(true,\ebi\Util::is_true(true,true,true));
eq(false,\ebi\Util::is_true(true,true,0));
