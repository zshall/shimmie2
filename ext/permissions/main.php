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
	 *		+ Managing the "master list" of permissions.
	 */
	
	// onEvents go here.
	
	public function onInitExt(Event $event) {
		/**
		 * OK... we are going to install some tables.
		 * And by some, I mean one (for now.)
		 */
		global $config;
		$version = $config->get_int("permission_list", 0);
		/**
		 * If this version is less than "1", it's time to install.
		 *
		 * REMINDER: If I change the database tables, I must change up version by 1.
		 */
		 if($version < 2) {
		 	/**
		 	* Installer
		 	*/
			global $database;
			$database->create_table("permission_list",
                "id SCORE_AIPK
				 , perm_name VARCHAR(128) UNIQUE NOT NULL
				 , perm_desc TEXT
                ");
			$config->set_int("permission_list", 2);
		}
		// Send the event and let all the extensions add their permissions.
		send_event(new PermissionsSetEvent());
	}
}

// Events...
class PermissionsSetEvent extends Event {
	/**
	 * Hm... If we "event-ize" this, it would eliminate the need to
	 * create a bunch of globals junk. Let's try it ^_^
	 */
	public function add_perm($name, $desc) {
		/**
		 * This simple function adds a permission to the list.
		 *
		 * If the permission already exists, don't do this.
		 */
		global $database;
		if(!isset($name)) { die("No name permission error."); }
		if(!isset($desc)) { $desc = "Unknown Permission."; }
		$exists = $database->get_row("SELECT perm_name FROM permission_list WHERE perm_name = ?", array($name));

		if($exists['perm_name'] != $name) {
			// Looks good. Let's add it.
			$database->execute("INSERT INTO `permission_list` (`id`, `perm_name`, `perm_desc`) VALUES ('', ?, ?)", array($name, $desc));
			log_info("permissions","Added permission $name.");
		}
	}
}

class Permissions_Test extends SimpleExtension {
	public function onPermissionsSet(Event $event) {
		// This test extension does what all extensions would do if this system is implemented.
		// Right now, it just sets a few permissions that would be deemed necessary, but should be set elsewhere, such as in the extensions themselves.
		// This function might make globals unnecessary.
		$event->add_perm("can_post", "Post");
		$event->add_perm("can_comment", "Comment");
		$event->add_perm("can_delete_posts", "Delete Posts");
		$event->add_perm("can_delete_comments", "Delete Comments");
		$event->add_perm("can_bulk_upload", "Bulk Upload");
	}
}

//class Permissions_Config extends SimpleExtension {
//	/**
//	 * This class handles configuration of the Permissions system.
//	 */
//	
//	public function onSetupBuilding($event) {
//		$sb = new SetupBlock("User Levels");
//		/**
//		 * We'll have something here soon.
//		 */
//		$event->panel->add_block($sb);
//	}
//	/**
//	 * When we view the user's prefrerences page, if we are admin, show the... something.
//	 */
//	public function onPrefBuilding($event) {
//		global $user, $config;
//		if($user->is_admin()) {
//			$pb = new PrefBlock("User Level");
//			/**
//			 * We'll have something here soon.
//			 */
//			$event->panel->add_block($pb);
//		}
//	}		
//}

class Groups extends SimpleExtension {
	/**
	 * Functions of the groups extension:
	 *		+ Install the groups table (this class)
	 *		+ Group Editor (GroupEditor class)
	 *		+ Assigning permissions to a member of a group
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
		 if($version < 2) {
		 	/**
		 	* Installer
		 	*/
			global $database;
			$database->create_table("group_list",
                "id SCORE_AIPK
				 , group_name VARCHAR(128) UNIQUE NOT NULL
				 , group_permissions TEXT
				 , group_members TEXT
                ");
			$config->set_int("group_list", 2);
		}
	}
}

class GroupEditor extends SimpleExtension {
	public function onPageRequest(Event $event) {
		if($event->page_matches("groups/editor")) {
		/**
		 * Displays the groups editor.
		 */
			global $user, $database;
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				$permissions = $database->get_all("SELECT * FROM permission_list ORDER BY id ASC");
				$groups = $database->get_all("SELECT * FROM group_list ORDER BY id ASC");
				$this->theme->display_editor($permissions, $groups);
			}
		}
		
		if($event->page_matches("groups/add")) {
			/**
			 * Adds a group
			 */
			global $page, $database, $user;
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				/**
				 * Consolidating function to get all of the permissions in order.
				 * Argh this will be tough...
				 */
				$group_name = $_POST['group_name'];
				// Get all permissions
				$permissions = $database->get_all("SELECT * FROM permission_list ORDER BY id ASC");
				// Now, start a loop...
				for ($i = 0 ; $i < count($permissions) ; $i++) {
					$pn = $permissions[$i]['perm_name'];
					if(isset($_POST["$pn"])) { $pe[] = $pn; }
					//$pe[] = isset($_POST["$pn"])? "$pn" : ""; // will this work?
				}
				
				for ($i = 0 ; $i < count($pe) ; $i++) {
				$group_permissions .= $pe[$i];
				$j = $i+1;
				if(isset($pe[$j])) { $group_permissions .= ","; } // how about this?
				}
				
				// Now insert into db:
				$database->Execute("INSERT INTO group_list(id, group_name, group_permissions, group_members) VALUES(?, ? , ?, ?)", array(NULL, $group_name, $group_permissions, NULL));
				log_info("user_level_editor", "Added User Level: $level_name");
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("groups/editor"));
			}
		}
	}
}
?>