<?php
/**
 * testman install
 */
$path = getcwd();


if(!is_file($f=$path.'/test/testman.phar')){
	\ebi\Util::mkdir($path.'/test');

	file_put_contents($f=$path.'/test/testman.phar',file_get_contents('http://git.io/testman.phar'));
	\cmdman\Std::println_success('Created '.$f);
}

if(!is_file($f=$path.'/test/testman.settings.php')){
	file_put_contents($f,<<< '_C'
<?php
\ebi\Conf::set(ebi.Db::class,'autocommit',true);
\testman\Conf::set('urls',\ebi\Dt::get_urls());
_C
	);
	\cmdman\Std::println_success('Created '.$f);
}

if(!is_file($f=$path.'/test/testman.fixture.php')){
	file_put_contents($f,<<< '_C'
<?php
_C
	);
	\cmdman\Std::println_success('Created '.$f);
}

if(!is_file($f=$path.'/test/__setup__.php')){
		file_put_contents($f,<<< '_C'
<?php
\ebi\Exceptions::clear();
_C
	);
	\cmdman\Std::println_success('Created '.$f);
}

