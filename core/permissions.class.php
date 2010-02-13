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
	public function set_perm($group_name, $perm_name, $set=true);
	
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
	public function set_perm($group_name, $perm_name, $set=true) {
		// I think I've figured it out.
		// This will append $perm_name to the end of `group_permissions`
		global $database;
		$get_current_permissions = $database->get_row("SELECT group_permissions FROM group_list WHERE group_name = ?", array($group_name));
		$perm_array = explode(",",$get_current_permissions["group_permissions"]);
		if(!in_array($perm_name, $perm_array)) {
			if($set == true) {
				$current_perms = $get_current_permissions["group_permissions"];
				$group_permissions = $current_perms.",".$perm_name;
				$database->Execute("UPDATE `group_list` SET `group_permissions` = ? WHERE `group_name` = ?", array($group_permissions, $group_name));
				log_info("permissions","Set default $perm_name for group $group_name");
			}
		} else {
			if($set == false) {
				// Code to remove it from the array.
			}
		}
	}
	public function get_perms() {
		return $this->values;
	}
	
	public function hellow() {
		return "Hello World.";
	}
}

class GlobalPermission extends BasePermission {}
?>
