<?php
$t = new \ebi\FlowHelper();
eq("00005",$t->zerofill(5,5));
eq("5",$t->zerofill(5));
