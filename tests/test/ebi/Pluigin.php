<?php

ob_start();
$test = new \test\plugin\PluginTest();
$test->abc();
$ob = ob_get_clean();

eq('abcxyz', $ob);
