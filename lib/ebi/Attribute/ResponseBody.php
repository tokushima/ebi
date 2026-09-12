<?php
namespace ebi\Attribute;

/**
 * 200 レスポンスの「ボディ全体」のスキーマを定義する Attribute。
 *
 * 標準の #[Response] は result オブジェクト内の「名前付きフィールド」を表す。それに対し ResponseBody は
 * 200 ボディ全体が {type:object, properties} ラップに収まらない形のときに使う：
 *   - bare 配列（例: Kit の配列がそのままボディ）
 *   - 単一オブジェクト
 *   - バイナリ（画像/PDF 等）
 *
 * 1 メソッドにつき 1 個（非 repeatable）。#[Response]（名前付きフィールド）との併記は不可
 * ＝「ボディ全体か、result 内の名前付きフィールドか」は排他。併記された場合、Dt 画面の
 * スペック生成時に当該エンドポイントが x-skipped として理由付きで警告表示される（実行時例外ではない）。
 *
 * nullable はモデル層スキーマと同一の既定：
 *   nullable=null … 未指定は nullable ON 扱い（値が null になり得る）。非 null が確定なら nullable:false
 *   （ボディ全体には「result 内にキーが存在するか」という概念が無いため required は持たない）
 *
 * format='binary' … 画像/PDF 等のバイナリ応答。200 の content を JSON ではなく mediaType（既定
 *   application/octet-stream）＋ {type:string, format:binary} に上書きする。パスにサフィックス
 *   （.png/.jpg 等）が無くバイナリを配信するエンドポイントで使う。
 *
 * @example bare 配列ボディ:
 *   #[ResponseBody(type:'array', items:'\App\Model\Kit', nullable:false, summary:'キットの配列')]
 *
 * @example 単一オブジェクトボディ:
 *   #[ResponseBody(type:\App\Model\Foo::class, nullable:false, summary:'...')]
 *
 * @example バイナリ応答:
 *   #[ResponseBody(format:'binary', mediaType:'image/jpeg', summary:'プレビュー画像')]
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class ResponseBody{
	public function __construct(
		public \ebi\T|string $type=\ebi\T::Mixed,
		public ?string $items=null,
		public ?string $summary=null,
		public bool $deprecated=false,
		public ?bool $nullable=null,
		public ?string $format=null,
		public ?string $mediaType=null,
	){}
}
