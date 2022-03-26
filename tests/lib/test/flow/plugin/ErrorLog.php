<?php
namespace test\flow\plugin;

class ErrorLog implements \ebi\FlowExceptionCallback{
	public function flow_exception_occurred(string $pathinfo, array $pattern, ?object $ins, \Exception $e): void{
		if(!($e instanceof \ebi\exception\UnauthorizedException)){
			$info = [
				'type'=>get_class($e),
				'type'=>$e->getMessage(),
				'path'=>$pathinfo,
				'pattern'=>$pattern,
				'action'=>(is_object($ins) ? get_class($ins) : ''),
				'exception'=>$e->getTraceAsString()
			];
			\ebi\Log::error($info);
		}
	}
}

