<?php

$t = new \ebi\FlowHelper();

eq(true,$t->is_true(true,true,true));
eq(false,$t->is_true(true,true,0));
