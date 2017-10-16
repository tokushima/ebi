<?php
eq('test_abc',\ebi\Util::camel2snake('TestAbc'));
eq('test_abc_def',\ebi\Util::camel2snake('TestAbcDef'));
eq('test_abc_def123',\ebi\Util::camel2snake('TestAbcDef123'));
eq('test_abc_def123_ghi',\ebi\Util::camel2snake('TestAbcDef123Ghi'));

eq('test_abc_def123_ghi',\ebi\Util::camel2snake('\xxx\yyy\zzz\TestAbcDef123Ghi'));

