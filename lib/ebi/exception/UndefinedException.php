<?php
namespace ebi\exception;
/**
 * 未定義の値や識別子を参照した場合にスローされる例外です
 */
class UndefinedException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'undefined';
}
