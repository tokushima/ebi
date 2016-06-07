# Install

##ebi install
  
```
$ mkdir hello
$ cd hello
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar require tokushima/ebi dev-master
```

## create entry file

index.php

```php
<?php
include 'vendor/autoload.php';

\ebi\Flow::app([
	'patterns'=>[
		''=>[
			'action'=>function(){
				return ['message'=>'Hello World'];
			}
		]
	]
]);
```


##Start the development server:

```
$ php -S localhost:8080
```

## Hello World

```
http://localhost:8080
```

### result

```json
{"result":{"message":"Hello World"}}
```
