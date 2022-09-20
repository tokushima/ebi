<?php

$date = strtotime('2022-03-14');
$time = \ebi\Util::next_business_day($date, 5);
eq('2022-03-21', date('Y-m-d', $time));

$date = strtotime('2022-03-14');
$time = \ebi\Util::next_business_day($date, 5, ['20220321']);
eq('2022-03-22', date('Y-m-d', $time));
