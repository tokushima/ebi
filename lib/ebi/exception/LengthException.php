<?php
namespace ebi\exception;
/**
 * 長さが期待値に一致しなかった場合にスローされる例外です
 */
class LengthException extends \ebi\Exception{
	protected ?int $http_status = 422;
	protected $message = 'invalid length';
}
