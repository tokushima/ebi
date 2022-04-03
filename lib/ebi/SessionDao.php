<?php
namespace ebi;
/**
 * @var string $id @['primary'=>true,'max'=>256]
 * @var text $data
 * @var int $expires
 */
class SessionDao extends \ebi\Dao implements \ebi\SessionHandler{
	protected $id;
	protected $data;
	protected $expires;

	protected function __before_save__(bool $is_update): void{
		$this->expires = time();
	}

	protected function __set_data__(?string $value): void{
		$this->data = ($value === null) ? '' : $value;
	}

	/**
	 * @return mixed
	 */
	public function session_read(string $id){
		try{
			$obj = static::find_get(Q::eq('id',$id));
			return $obj->data();
		}catch(\Exception $e){
		}
		return '';
	}
	/**
	 * @param mixed $data
	 */
	public function session_write(string $id, $data): bool{
		$obj = new self();
		$obj->id($id);
		$obj->data($data);
		$obj->save();

		return true;
	}

	public function session_destroy(string $id): bool{
		try{
			static::find_delete(Q::eq('id',$id));
			return true;
		}catch(\Exception $e){
		}
		return false;
	}

	public function session_gc(int $maxlifetime): bool{
		try{
			static::find_delete(Q::lt('expires',time() - $maxlifetime));
			static::commit();
			return true;
		}catch(\Exception $e){
		}
		return false;
	}
	/**
	 * 初期処理
	 * @param $path セッションを格納/取得するパス。
	 * @param $name セッション名
	 */
	public function session_open(string $path, string $name): bool{
		return true;
	}

	/**
	 * writeが実行された後で実行される
	 */
	public function session_close(): bool{
		return true;
	}

}
