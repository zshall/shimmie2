<?php
/**
 * Name: Permissions class
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
 * Description: This is probably better to have as a class.
 *				Based on Shish's config class.
 */
interface Permission {
	/**
	 * Add a permission to the global list.
	 */
	public function add_perm($perm_name, $perm_desc);
	
	/**
	 * Sets a default permission
	 */
	//public function set_perm($group_name, $perm_name, $set=true);
	
	/**
	 * Gets the entire list of permissions (?)
	 */
	public function get_perms();
	
	public function hellow();
}


/**
 * We probably only need one class after that interface.
 */
abstract class BasePermission implements Permission {
	var $values = array();

	public function add_perm($perm_name, $perm_desc) {
		$this->values[] = array("perm_name" => $perm_name, "perm_desc" => $perm_desc);
	}
//	public function set_perm($group_name, $perm_name, $set=true) {
//		// Not sure if this should be here. Not even sure what I'm doing.
//	}
	public function get_perms() {
		return $this->values;
	}
	
	public function hellow() {
		return "Hello World.";
	}
}

class GlobalPermission extends BasePermission {}
?>
