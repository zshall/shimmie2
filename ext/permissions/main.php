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
	 * This section handles the "master list" of permissions.
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
		 if($version < 1) {
		 	/**
		 	* Installer
		 	*/
			global $database;
			$database->create_table("permission_list",
                "id SCORE_AIPK
				 , perm_name VARCHAR(128) UNIQUE NOT NULL
                 , INDEX(id)
                ");
			$config->set_int("permission_list", 3);
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
	public function add_perm($name) {
		/**
		 * This simple function adds a permission to the list.
		 *
		 * If the permission already exists, don't do this.
		 */
		global $database;
		$exists = $database->get_row("SELECT perm_name FROM permission_list WHERE perm_name = ?", array($name));

		if($exists['perm_name'] != $name) {
			// Looks good. Let's add it.
			$database->execute("INSERT INTO `permission_list` (`id`, `perm_name`) VALUES ('', ?)", array($name));
			log_info("permissions","Added permission $name.");
		}
	}
}

class Permissions_Test extends SimpleExtension {
	public function onPermissionsSet(Event $event) {
		// This test extension does what all extensions would do if this system is implemented.
		// Right now, it just sets a permission.
		// This function might make globals unnecessary.
		$event->add_perm("Hello World");
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
		 if($version < 1) {
		 	/**
		 	* Installer
		 	*/
			global $database;
			$database->create_table("permission_list",
                "id SCORE_AIPK
				 , perm_name VARCHAR(128) UNIQUE NOT NULL
                 , INDEX(id)
                ");
			$config->set_int("permission_list", 3);
		}
	}
}
?>