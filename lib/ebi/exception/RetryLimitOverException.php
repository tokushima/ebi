<?php
namespace ebi\exception;
/**
 * リトライ上限を超えた場合にスローされる例外です
 */
class RetryLimitOverException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'retry limit over';
}
