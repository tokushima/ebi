<?php
namespace ebi;
/**
 * 権限
 * @author tokushima
 *
 */
trait UserRole{
	private $user_role = [];
	
	/**
	 * 権限値を設定する
	 * @param array $roles
	 */
	public function set_role(array $roles){
		$this->user_role = $roles;
	}
	/**
	 * 権限値を取得する
	 * @return array
	 */
	public function get_role(){
		return $this->user_role;
	}
	/**
	 * 指定の権限があるか
	 * @param string $role
	 * @return boolean
	 */
	public function has_role($role){
		return in_array($role,$this->get_role());
	}
}