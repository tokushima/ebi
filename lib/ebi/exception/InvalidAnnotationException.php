<?php
namespace ebi\exception;
/**
 * アノテーションの定義が不正な場合にスローされる例外です
 */
class InvalidAnnotationException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'invalid annotation';
}
