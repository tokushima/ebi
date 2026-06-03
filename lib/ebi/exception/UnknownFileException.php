<?php
namespace ebi\exception;
/**
 * 有効なファイルが存在しない場合にスローされる例外です
 */
class UnknownFileException extends \ebi\Exception{
	protected ?int $http_status = 404;
	protected $message = 'unknown file';
}
