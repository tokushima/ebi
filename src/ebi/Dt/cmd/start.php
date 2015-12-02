<?php
/**
 * 簡易ランチャー作成
 */
include(__DIR__.'/setup.php');

\ebico\Std::println(PHP_EOL);

$port = \ebico\Std::read('port?','8000');
$entry = \ebico\Std::read('entry?','index.php');

\ebi\Util::file_write('start.sh',sprintf(<<< '__SRC__'
cd `dirname $0`
CURDIR=`pwd`

open file://${CURDIR}/start.html

php -S 0.0.0.0:%s

__SRC__
	,$port));

\ebi\Util::file_write('start.html',sprintf(<<< '__SRC__'
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="refresh" content="2; url=http://localhost:%s/%s">
</head>
<body>
	<h1>Please wait..</h1>
	<a href="http://localhost:%s/%s">http://localhost:%s/%s</a>
</body>
</html>

__SRC__
	,$port,$entry,$port,$entry,$port,$entry));

chmod('start.sh',0755);
chmod('start.html',0666);

\ebico\Std::println_success('Written: '.realpath('start.sh'));
\ebico\Std::println_success('Written: '.realpath('start.html'));


if(substr($entry,-4) === '.php' && !is_file($entry)){
	\ebi\Util::file_write($entry,<<< '__SRC__'
<?php
include('bootstrap.php');

\ebi\Flow::app();
__SRC__
	);
	
	\ebico\Std::println_success('Written: '.realpath($entry));
}

