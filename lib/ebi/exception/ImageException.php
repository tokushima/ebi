<?php
namespace ebi\exception;
/**
 * 画像処理に失敗した場合にスローされる例外です
 */
class ImageException extends \ebi\Exception{
	protected ?int $http_status = 400;
	protected $message = 'image error';
}
