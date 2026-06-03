<?php
namespace ebi\exception;
/**
 * 入出力に失敗した場合にスローされる例外です
 */
class IOException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'I/O error';
}
