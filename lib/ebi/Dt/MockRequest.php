<?php
namespace ebi\Dt;

abstract class MockRequest extends \ebi\flow\Request{
	public function __construct(){		
	}

	/**
	 * [$pattern=>$replacement]
	 */
	public function rewrite_map(): array{
		return [];
	}

	protected function output_json(array $vars): void{
		\ebi\HttpHeader::send('Content-Type', 'application/json');
		print(json_encode($vars));
		exit;	
	}
}
