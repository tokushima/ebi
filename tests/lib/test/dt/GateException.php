<?php
namespace test\dt;

/**
 * #[FlowGate(onFail: GateException::class)] の G8 突合テスト用の例外。
 */
class GateException extends \ebi\exception\InvalidArgumentException{
}
