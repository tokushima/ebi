<?php
$t = new \ebi\FlowHelper();
$time = time();
eq(date("YmdHis",$time),$t->df("YmdHis",$time));

