<?php
namespace ebi\exception;
/**
 * 実行不可能なメソッドが呼び出された場合にスローされる例外です
 */
class BadMethodCallException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'bad method call';
}
