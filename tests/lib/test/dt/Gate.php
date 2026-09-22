<?php
namespace test\dt;

use \ebi\Attribute\Parameter;
use \ebi\Attribute\FlowGate;
use \ebi\Attribute\FlowToken;

/**
 * #[FlowGate]（述語前提）の配線・G検証フィクスチャ。
 * gate token は #[FlowToken]（生産者を持たない ambient 語彙）で定義しておき、G1(未定義)を回避する。
 */
#[FlowToken('product.category', kind:'ambient', summary:'商品カテゴリ')]
#[FlowToken('kit.orderable', kind:'ambient', summary:'キット発注可否')]
class Gate extends \ebi\app\Request{
	/**
	 * onFail(GateException) を @throws で宣言済み → G8 は出ない。token 定義済み → G1 も出ない。
	 * @throws \test\dt\GateException 対象外の商品
	 */
	#[Parameter(name:'product_code', type:'string')]
	#[FlowGate(token:'product.category', in:['photobook'], bind:'product_code', onFail: GateException::class, reason:'フォトブック専用')]
	public function ok(){
		return ['ok' => true];
	}

	/**
	 * onFail(GateException) を @throws 宣言していない → G8。token は定義済み → G1 は出ない。
	 */
	#[FlowGate(token:'kit.orderable', equals:true, onFail: GateException::class)]
	public function missing_throws(){
		return ['ok' => true];
	}

	/**
	 * 未定義トークンを gate に指定 → G1。onFail 無しなので G8 は対象外。
	 */
	#[FlowGate(token:'unknown.token', in:['x'])]
	public function unknown_token(){
		return ['ok' => true];
	}
}
