<?php
namespace ebi;

interface LogHandler{
	public function output(\ebi\Log $log): void;
}
