<?php
namespace ebi\exception;
/**
 * 引数が期待値に一致しなかった場合にスローされる例外です
 */
class InvalidArgumentException extends \ebi\Exception{
	protected ?int $http_status = 400;
}
