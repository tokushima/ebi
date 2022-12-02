<?php
namespace ebi\Dt;

interface DocOptIf{
	public function set_opt(string $n, $val): void;
	public function opt(string $n, $def=null); 
}