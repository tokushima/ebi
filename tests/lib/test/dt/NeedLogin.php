<?php
namespace test\dt;

class NeedLogin extends \ebi\app\Request{
	/**
	 * ログイン必須エンドポイント（未認証時は 401 + {"error":[...]} を返す）
	 * @login_required
	 * @context string $v 値
	 */
	public function get(): array{
		return ['v' => 'x'];
	}
}
