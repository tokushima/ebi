<?php
namespace ebi\exception;
/**
 * 最大サイズを超えた場合にスローされる例外です
 */
class MaxSizeExceededException extends \ebi\Exception{
	protected ?int $http_status = 413;
	protected $message = 'max size exceeded';
}
