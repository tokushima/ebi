<?php
namespace test\flow;

class Helper{
	public function keyword($text){
		$text = str_replace('bbb','<span class="badge text-bg-warning">bbb</span>',$text);
		
		return $text;
	}
}
