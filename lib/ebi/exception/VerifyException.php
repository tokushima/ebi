<?php
namespace ebi\exception;
/**
 * 引数が検証に失敗した場合にスローされる例外です
 */
class VerifyException extends \ebi\Exception{
	protected ?int $http_status = 422;
}
