<?php
namespace ebi\exception;

class MaxSizeExceededException extends \ebi\Exception{
	protected $message = 'max size exceeded';
	protected ?int $http_status = 413;
}
