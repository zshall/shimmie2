<?php
/*
 * Name: Group Permissions
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com/]
 * License: GPLv2
 * Description: Groups and Permissions System
 *				Under development.
 *
 *				Prereqs: userprefs class, preferences extension
 *
 *				Additional functionality available with: user_levels extension.
 *
 *				Development TODO now at http://github.com/zshall/shimmie2/issues
 */
 
class Permissions extends SimpleExtension {
	/**
	 * Functions of the Permissions Class:
	 *		+ Err... sending the permission scan event
	 */		
	public function onInitExt(Event $event) {
		// Send the event and let all the extensions add their permissions.
		send_event(new PermissionScanEvent());
	}
}

// Events...
class PermissionScanEvent extends Event {}

/*class Permissions_Test extends SimpleExtension {
	public function onPermissionScan(Event $event) {
		// This test extension does what all extensions would do if this system is implemented.
		// Right now, it just sets a few permissions that would be deemed necessary, but should be set elsewhere, such as in the extensions themselves.
		global $permissions;
		$permissions->add_perm("post", "Post");
		$permissions->add_perm("comment", "Comment");
		$permissions->add_perm("delete_posts", "Delete Posts");
		$permissions->add_perm("delete_comments", "Delete Comments");
		$permissions->add_perm("bulk_upload", "Bulk Upload");
	}
	public function onPageRequest(Event $event) {
		if($event->page_matches("permissions/test")) {
			global $user, $page;
			
			if($user->can("post")) { echo "Yes."; } else { echo "No."; }
			$page->set_mode("data");
			$page->set_data("&nbsp;");
		}
	}
}

class GlobalPermissionTest extends SimpleExtension {
	public function onPermissionScan(Event $event) {
		global $permissions;
		$permissions->add_perm("test_perm","testdescription");
	}
	public function onInitExt(Event $event) {
		global $permissions, $config;
		$version = $config->get_int("test_perm_ext", 0);
		 if($version < 1) {
				$permissions->set_perm("anonymous","test_perm",true);
				$permissions->set_perm("user","test_perm",true);
				$permissions->set_perm("admin","test_perm",true);
				$config->set_int("test_perm_ext", 1);
		}
	}
}*/

class Groups extends SimpleExtension {
	/**
	 * Functions of the groups extension:
	 *		+ Install the groups table (this class) (DONE.)
	 *		+ Group Editor (GroupEditor class) (DONE.)
	 *		+ Assigning permissions to a member of a group (WIP.)
	 */
	public function onInitExt(Event $event) {
		/**
		 * OK... we are going to install some tables.
		 * And by some, I mean one (for now.)
		 */
		global $config;
		$version = $config->get_int("group_list", 0);
		/**
		 * If this version is less than "1", it's time to install.
		 *
		 * REMINDER: If I change the database tables, I must change up version by 1.
		 */
		 if($version < 1) {
		 	/**
		 	* Installer
		 	*/
			global $database;
			$database->create_table("group_list",
                "id SCORE_AIPK
				 , group_name VARCHAR(128) UNIQUE NOT NULL
				 , group_permissions TEXT
                ");
			$database->Execute("INSERT INTO group_list(id, group_name, group_permissions) VALUES(?, ? , ?)", array(NULL, "anonymous", NULL));
			$database->Execute("INSERT INTO group_list(id, group_name, group_permissions) VALUES(?, ? , ?)", array(NULL, "user", NULL));
			$database->Execute("INSERT INTO group_list(id, group_name, group_permissions) VALUES(?, ? , ?)", array(NULL, "admin", NULL));

			$config->set_int("group_list", 1);
		}
	}
}

class GroupEditor extends Groups {
	public function onPageRequest(Event $event) {
		if($event->page_matches("groups/editor")) {
		/**
		 * Displays the groups editor.
		 */
			global $user, $database, $permissions;
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				//$all_perms = $database->get_all("SELECT * FROM permission_list ORDER BY id ASC");
				$all_perms = $permissions->get_perms();
				$groups = $database->get_all("SELECT * FROM group_list ORDER BY id ASC");
				$this->theme->display_editor($all_perms, $groups);
			}
		}
		
		if($event->page_matches("groups/add")) {
			/**
			 * Adds a group
			 */
			global $page, $database, $user, $permissions;
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				/**
				 * Consolidating function to get all of the permissions in order.
				 * Argh this will be tough...
				 */
				$group_name = $_POST['group_name'];
				if($group_name == "") { die("No group name!"); }
				// Get all permissions
				$all_perms = $permissions->get_perms();
				// Now, start a loop...
				for ($i = 0 ; $i < count($all_perms) ; $i++) {
					$pn = $all_perms[$i]['perm_name'];
					if(isset($_POST["$pn"])) { $pe[] = $pn; }
				}
				
				for ($i = 0 ; $i < count($pe) ; $i++) {
					$group_permissions .= $pe[$i];
					$j = $i+1;
					if(isset($pe[$j])) { $group_permissions .= ","; }
				}
				
				// Now insert into db:
				$database->Execute("INSERT INTO group_list(id, group_name, group_permissions) VALUES(?, ? , ?)", array(NULL, $group_name, $group_permissions));
				log_info("group_editor", "Added Group: $group_name");
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("groups/editor"));
			}
		}

		if($event->page_matches("groups/change")) {
			/**
			 * Changes a group's permissions
			 */
			global $page, $database, $user, $permissions;
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				/**
				 * Slightly easier the second time around, though
				 */
				$id = $_POST['id'];
				if($id == "") { die("No ID!"); }
				// Get all permissions
				$all_perms = $permissions->get_perms();
				// Now, start a loop...
				for ($i = 0 ; $i < count($all_perms) ; $i++) {
					$pn = $all_perms[$i]['perm_name'];
					if(isset($_POST["$pn"])) { $pe[] = $pn; }
				}
				
				for ($i = 0 ; $i < count($pe) ; $i++) {
					$group_permissions .= $pe[$i];
					$j = $i+1;
					if(isset($pe[$j])) { $group_permissions .= ","; }
				}
				
				// Now update the DB's records:
				$database->Execute("UPDATE `group_list` SET `group_permissions` = ? WHERE `id` = ?", array($group_permissions, $id));
				log_info("group_editor", "Updated Group Info: $group_name");
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("groups/editor"));
			}
		}
		
		
		if($event->page_matches("groups/remove")) {
			global $page, $database, $user;
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				$id = $_POST['id'];
				$group_name = $_POST['group_name'];
				if(!isset($id)) { die("No ID!"); }
				if(!isset($group_name)) { die("No name!"); }
				$database->Execute("DELETE FROM group_list WHERE id=$id");
				log_info("group_editor", "Removed Group: $group_name");
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("groups/editor"));
			}
		}
	}
	
	public function onUserBlockBuilding(Event $event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Group Editor", make_link("groups/editor"));
		}
	}
}

class GroupConfig extends Groups {
	/**
	 * This class handles configuration of the Groups system.
	 */
	public function onPrefBuilding($event) {
		/**
		 * When we view the user's prefrerences page, if we are admin, show the... something.
		 */
		global $user, $config, $database;
		if($user->is_admin()) {
			$groups = $database->get_all("SELECT * FROM group_list ORDER BY id ASC");

			for ($i = 0 ; $i < count($groups) ; $i++) {
				$gn[] = $groups[$i]['group_name'];
			}

			$pb = new PrefBlock("User Group");
			$pb->add_db_array("user_group", $gn, "<br />User group: ");
			$event->panel->add_block($pb);
		}
	}		
}
?>