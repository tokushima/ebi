<?php
namespace test\flow;

use \ebi\Attribute\FlowRequires;
use \ebi\Attribute\FlowProduces;

/**
 * ambient establishedBy 検証用フィクスチャ。
 * grant() が session.user を ambient 確立（plan 非表示・establishedBy 用）、
 * consume() が session.user を要求しつつ flowamb.done を produce する。
 */
class FlowAmbientAction{
	#[FlowProduces('session.user', via: 'effect', when: 'success', kind: 'state', ambient: true, summary: 'セッション確立(email/password)')]
	public function grant(){
		return ['ok' => true];
	}

	#[FlowRequires('session.user')]
	#[FlowProduces('flowamb.done', via: 'effect', when: 'success', summary: 'ambient 検証: 完了状態')]
	public function consume(){
		return ['ok' => true];
	}
}
