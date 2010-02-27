<?php
/**
 * Name: Permissions class
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
 * Description: Sets a permission.
 */
interface PermInt {
	/**
	 * Sets a default permission
	 */
	public static function set_perm($group_name, $perm_name, $set=true);
}

abstract class Permissions implements PermInt {
	public static function set_perm($group_name, $perm_name, $set=true) {
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
				// Not tested yet.
				$perm_array = explode(",", $get_current_permissions['group_permissions']);
				if(in_array($perm_name, $perm_array)) {
					$perm_array = array_values(array_diff($perm_array,array($perm_name)));
					$group_permissions = implode(",", $perm_array);
					$database->Execute("UPDATE `group_list` SET `group_permissions` = ? WHERE `group_name` = ?", array($group_permissions, $group_name));
				}
			}
		}
	}
}
?>
