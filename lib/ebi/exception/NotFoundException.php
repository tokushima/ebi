<?php
namespace ebi\exception;
/**
 * 対象が見つからない場合にスローされる例外です
 */
class NotFoundException extends \ebi\Exception{
	protected ?int $http_status = 404;
	protected $message = 'not found';
}
