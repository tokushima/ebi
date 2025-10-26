<?php
$t = new \ebi\FlowHelper();
$time = time();
eq(date("YmdHis",$time),$t->date_format("YmdHis",$time));

