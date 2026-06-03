<?php
namespace ebi\exception;
/**
 * テンプレートの処理に失敗した場合にスローされる例外です
 */
class InvalidTemplateException extends \ebi\Exception{
	protected ?int $http_status = 500;
	protected $message = 'invalid template';
}
