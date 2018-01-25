<?php

eq([
	'test\Util\aaa\Abc',
	'test\Util\aaa\Def',
	'test\Util\aaa\Ghi',
],\ebi\Util::ls_classes(\test\Util\aaa\Abc::class));

eq([
	'test\Util\aaa\Abc',
	'test\Util\aaa\Ghi',
],\ebi\Util::ls_classes(\test\Util\aaa\Abc::class,\test\Util\ParentClass::class));

eq([
	'test\Util\aaa\Abc',
	'test\Util\aaa\Ghi',
	'test\Util\aaa\bbb\Babc',
	'test\Util\aaa\bbb\Bghi',
],\ebi\Util::ls_classes(\test\Util\aaa\Abc::class,\test\Util\ParentClass::class,true));

eq([
	'test\Util\aaa\Abc',
	'test\Util\aaa\Def',
	'test\Util\aaa\Ghi',
	'test\Util\aaa\bbb\Babc',
	'test\Util\aaa\bbb\Bdef',
	'test\Util\aaa\bbb\Bghi',
],\ebi\Util::ls_classes(\test\Util\aaa\Abc::class,null,true));


