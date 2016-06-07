<?php
namespace ebi\exception;
/**
 * 許可されていない
 * @author tokushima
 */
class NotPermittedException extends \ebi\Exception{
	public $message = 'not permitted';
}
