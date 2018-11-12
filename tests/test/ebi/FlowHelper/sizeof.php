<?php
$t = new \ebi\FlowHelper();
eq(1,$t->sizeof(0));
eq(1,$t->sizeof('0'));
eq(1,$t->sizeof(true));
eq(1,$t->sizeof([0]));
eq(2,$t->sizeof([0,1]));

