<?php
/**
 * HTMLを書き出す
 * @param string $path
 */
if(!isset($path)) $path = getcwd().'/contents/';

foreach(\ebi\flow\Pages::publish($path) as $p){
	\brev\Std::println('Written '.$p);
}