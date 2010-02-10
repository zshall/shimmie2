<?
class UserLevelEditor extends SimpleExtension {
	public function onPageRequest(Event $event) {
		if($event->page_matches("user_levels/editor")) {
		/**
		 * Displays the level editor.
		 */
			global $user, $database;
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				// I have my doubts about this... 
				$levels = $database->get_all("SELECT * FROM user_levels ORDER BY id ASC");
				$this->theme->display_editor($levels);
			}
		}
		if($event->page_matches("user_levels/add")) {
			/**
			 * Adds a user level
			 */
			global $page, $database, $user;
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				$level_name = $_POST['level_name'];
				$level_number = $_POST['level_number'];
				$level_c_total = $_POST['level_c_total'];
				$level_c_p = $_POST['level_c_p'];
				$level_c_c = $_POST['level_c_c'];
				$level_c_t = $_POST['level_c_t'];
				
				if(!isset($level_name)) { die("No name!"); }
				if(!isset($level_number)) { die("No level number!"); }
				if(!isset($level_c_total)) { die("No points!"); }
				if(!isset($level_c_p)) { $level_c_p = 0; }
				if(!isset($level_c_c)) { $level_c_c = 0; }
				if(!isset($level_c_t)) { $level_c_t = 0; }
				
				$database->Execute("INSERT INTO user_levels(id, level_number, level_name, level_c_total, level_c_p, level_c_c, level_c_t) VALUES(?, ? , ?, ?, ?, ?, ?)", array(NULL, $level_number, $level_name, $level_c_total, $level_c_p, $level_c_c, $level_c_t));
				log_info("user_level_editor", "Added User Level: $level_name");
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("user_levels/editor"));
			}
		}
		if($event->page_matches("user_levels/remove")) {
			global $page, $database, $user;
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				$id = $_POST['id'];
				$level_name = $_POST['level_name'];
				if(!isset($id)) { die("No ID!"); }
				if(!isset($level_name)) { die("No name!"); }
				$database->Execute("DELETE FROM user_levels WHERE id=$id");
				log_info("user_level_editor", "Removed User Level: $level_name");
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("user_levels/editor"));
			}
		}
	}
	
	public function onInitExt(Event $event) {
		/**
		 * OK... what are we doing here?
		 * 
		 * First, let's have an extension version.
		 */
		global $config;
		$version = $config->get_int("user_level_editor", 0);
		/**
		 * If this version is less than "1", it's time to install.
		 *
		 * REMINDER: If I change the database tables, I must change up version by 1.
		 *
		 * FUTURE: Add this to the create table once I'm ready for influence points.
		 		 , level_i_p DECIMAL
				 , level_i_c DECIMAL
				 , level_i_t DECIMAL
				 , level_i_total DECIMAL
		 */
		 if($version < 3) {
		 	/**
		 	* Installer
		 	*/
			global $database;
			$database->create_table("user_levels",
                "id SCORE_AIPK
				 , level_number INTEGER
				 , level_name VARCHAR(128)
                 , level_c_p INTEGER
                 , level_c_c INTEGER
                 , level_c_t INTEGER
                 , level_c_total INTEGER
                 , INDEX(id)
                ");
			$config->set_int("user_level_editor", 3);
		}
		   /**
		 	* Now that we've installed, let's get the user level.
		 	*/
		
	}
	
	private function level_test($user_id) {
		/**
		* Steps involved here...
		*		1. Get the user levels, order them ascending by level number.
		*		2. Get the user's total points number (and custom ones later.)
		*		3. Start a loop:
						* Is the user's total points number greater than or equal to the points
						  required by the custom user level?
						+ If so, set an integer equal to that level number.
						- If not, the last integer is 
				4. When the loop is finished,
		*/
		global $database;
		$levels = $database->get_all("SELECT * FROM user_levels ORDER BY level_number ASC");
		$user_level_test_prefs = new DatabasePrefs($database, $user_id);
		$total_user_level = $user_level_test_prefs->get_int("level_c_total",0);
		
	}
}

?>