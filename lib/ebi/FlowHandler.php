<?php
namespace ebi;

interface FlowHandler{
    /**
     * @$pattern マッチしたパターン
     * $ins 実行されたActionのインスタンス
     * $e 発生した例外
     */
    public function flow_exception_occurred(string $pathinfo, array $pattern, ?object $ins, \Exception $e): void;
}