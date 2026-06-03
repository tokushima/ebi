<?php
namespace ebi\exception;
/**
 * プログラムのロジック上のエラーが発生した場合にスローされる例外です
 */
class LogicException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'logic error';
}
