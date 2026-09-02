<?php
namespace ebi\Attribute;

/**
 * レスポンス変数を定義するAttribute（OpenAPI responses相当）
 *
 * @example
 * #[Response(name: 'user', type: 'App\Model\User')]
 * public function show() {}
 *
 * required/nullable はモデル層スキーマと同一の2軸・同一の既定：
 *   required=true  … result 内にキーが必ず存在する（条件付き省略キーは required:false）
 *   nullable=null  … 未指定は nullable ON 扱い（値が null になり得る）。非nullが確定なら nullable:false
 *
 * root=true … このレスポンスを result の「名前付きプロパティ」ではなく 200 ボディ全体の
 *   スキーマとして扱う（{type:object, properties} ラップをバイパス）。トップレベルが
 *   bare 配列 / 単一オブジェクトの応答を型化するために使う。1 メソッドにつき root は 1 つ想定で、
 *   root が指定された場合は同メソッドの他の（非 root）Response/@context は無視される。
 *   例: #[Response(name:'body', root:true, type:'array', items:'\App\Model\Kit', nullable:false)]
 *
 * format='binary' … 画像/PDF 等のバイナリ応答。200 の content を JSON ではなく mediaType（既定
 *   application/octet-stream）＋ {type:string, format:binary} に上書きする。パスにサフィックス
 *   （.png/.jpg 等）が無くバイナリを配信するエンドポイントで使う（例: /book/preview/{code}/{fcode}）。
 *   例: #[Response(name:'body', format:'binary', mediaType:'image/jpeg', summary:'プレビュー画像')]
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Response{
	public function __construct(
		public string $name,
		public string $type='mixed',
		public ?string $items=null,
		public ?string $summary=null,
		public bool $deprecated=false,
		public bool $required=true,
		public ?bool $nullable=null,
		public bool $root=false,
		public ?string $format=null,
		public ?string $mediaType=null,
	){}
}
