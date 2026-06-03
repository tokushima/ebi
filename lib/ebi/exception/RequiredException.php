<?php
namespace ebi\exception;
/**
 * 必須項目が空の場合にスローされる例外です
 */
class RequiredException extends \ebi\Exception{
	protected ?int $http_status = 422;
	protected $message = 'required';
}
