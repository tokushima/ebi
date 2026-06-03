<?php
namespace ebi\exception;
/**
 * 設定値が不正な場合にスローされる例外です
 */
class InvalidConfigException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'invalid config';
}
