<?php
namespace ebi\Dt;
/**
 * モック用リクエスト
 * 継承してモック用のクラスを作成する
 * 
 * rewrite_mapで[対象のURLの正規表現パターン => 代わりに実行する自身のメソッド名]を返す
 * 
 * エントリファイルmock.phpを作成する
 * ```
 * \ebi\Flow::app(
 * 	\ebi\Dt::mock_flow_mappings()
 * );
 * ```
 * 
 * commons/local.phpにadd_mockで追加する
 * ```
 * \ebi\Dt::add_mock('\yournamespace\Mock');
 * ```
 */
abstract class MockRequest extends \ebi\flow\Request{
	/**
	 * [$pattern=>$replacement]
	 */
	public function rewrite_map(): array{
		return [];
	}
}
