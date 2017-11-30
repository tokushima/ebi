<?php
namespace test\flow\plugin;

class ErrorLog{
	public function flow_exception_log($pathinfo,$pattern,$ins,$e){
		\ebi\Log::error($pathinfo,$pattern,$ins,$e);
	}
}

