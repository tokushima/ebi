<?php
namespace ebi\Dt;

abstract class MockRequest extends \ebi\flow\Request{
	/**
	 * [$pattern=>$replacement]
	 */
	public function rewrite_map(): array{
		return [];
	}
}
