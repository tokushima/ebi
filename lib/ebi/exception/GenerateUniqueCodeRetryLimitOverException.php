<?php
namespace ebi\exception;
/**
 * ユニークなコード生成のリトライ上限を超えた場合にスローされる例外です
 */
class GenerateUniqueCodeRetryLimitOverException extends \ebi\exception\RetryLimitOverException{
	protected $message = 'generate unique code retry limit over';
}
