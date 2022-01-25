<?php
namespace ebi;

trait UserRole{
	private $user_role = [];
	
	/**
	 * 権限値を設定する
	 */
	public function set_role(array $roles): void{
		$this->user_role = $roles;
	}
	/**
	 * 権限値を取得する
	 * @return array
	 */
	public function get_role(): array{
		return $this->user_role;
	}
	/**
	 * 指定の権限があるか
	 */
	public function has_role(int|string $role): bool{
		return in_array($role, $this->get_role());
	}
}