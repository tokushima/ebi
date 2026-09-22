<?php
namespace test\dt;

use \ebi\Attribute\FlowGate;
use \ebi\Attribute\FlowProduces;
use \ebi\Attribute\FlowToken;

/**
 * Mcp get_flow の plan 段に gate が併記されることを確かめるフィクスチャ。
 * make() は gate 付きで book.made を produce するので、get_flow('book.made') の plan に make が現れる。
 */
#[FlowToken('product.category', kind:'ambient', summary:'商品カテゴリ')]
class GateFlow extends \ebi\app\Request{
	/**
	 * gate 付きで book.made を成立させる。
	 * @throws \test\dt\GateException 対象外の商品
	 */
	#[FlowGate(token:'product.category', in:['photobook'], bind:'product_code', onFail: GateException::class)]
	#[FlowProduces('book.made', via:'effect', summary:'フォトブック作成完了')]
	public function make(){
		return ['ok' => true];
	}
}
