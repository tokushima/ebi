ebi 
====
__2012-12-25__

Web Framework (PHP 5 >= 5.4.0)

## Install

### composer install
	$ curl -s http://getcomposer.org/installer | php

### edit composer.json
	{
    	"require": {
			"tokushima/ebi":"master-dev"
    	}
	}

### ebi install
	$ php composer.phar install

### brev install
	$ curl -LO http://raw.github.com/tokushima/brev/master/brev.php

### create start file
	$ php brev.php ebi.Dt::setup --create


