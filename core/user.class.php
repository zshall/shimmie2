<?php
/** @private */
function _new_user($row) {
	return new User($row);
}

/**
 * An object representing a row in the "users" table.
 *
 * The currently logged in user will always be accessable via the global variable $user
 */
class User {
	var $id;
	var $name;
	var $email;
	var $join_date;
	var $admin;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	* Initialisation                                               *
	*                                                              *
	* User objects shouldn't be created directly, they should be   *
	* fetched from the database like so:                           *
	*                                                              *
	*    $user = User::by_name("bob");                             *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * One will very rarely construct a user directly, more common
	 * would be to use User::by_id, User::by_session, etc
	 */
	public function User($row) {
		$this->id = int_escape($row['id']);
		$this->name = $row['name'];
		$this->email = $row['email'];
		$this->join_date = $row['joindate'];
		$this->admin = ($row['admin'] == 'Y');
	}

	public static function by_session($name, $session) {
		global $config, $database;
		if($database->engine->name == "mysql") {
			$query = "SELECT * FROM users WHERE name = ? AND md5(concat(pass, ?)) = ?";
		}
		else {
			$query = "SELECT * FROM users WHERE name = ? AND md5(pass || ?) = ?";
		}
		$row = $database->get_row($query, array($name, get_session_ip($config), $session));
		return is_null($row) ? null : new User($row);
	}

	public static function by_id($id) {
		assert(is_numeric($id));
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE id = ?", array($id));
		return is_null($row) ? null : new User($row);
	}

	public static function by_name($name) {
		assert(is_string($name));
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE name = ?", array($name));
		return is_null($row) ? null : new User($row);
	}

	public static function by_name_and_hash($name, $hash) {
		assert(is_string($name));
		assert(is_string($hash));
		assert(strlen($hash) == 32);
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE name = ? AND pass = ?", array($name, $hash));
		return is_null($row) ? null : new User($row);
	}

	public static function by_list($offset, $limit=50) {
		assert(is_numeric($offset));
		assert(is_numeric($limit));
		global $database;
		$rows = $database->get_all("SELECT * FROM users WHERE id >= ? AND id < ?", array($offset, $offset+$limit));
		return array_map("_new_user", $rows);
	}


	/*
	 * useful user object functions start here
	 */

	/**
	 * Test if this user is anonymous (not logged in)
	 *
	 * @retval bool
	 */
	public function is_anonymous() {
		global $config;
		return ($this->id == $config->get_int('anon_id'));
	}

	/**
	 * Test if this user is logged in
	 *
	 * @retval bool
	 */
	public function is_logged_in() {
		global $config;
		return ($this->id != $config->get_int('anon_id'));
	}

	/**
	 * Test if this user is an administrator
	 *
	 * @retval bool
	 */
	public function is_admin() {
		return $this->admin;
	}

	public function set_admin($admin) {
		assert(is_bool($admin));
		global $database;
		$yn = $admin ? 'Y' : 'N';
		$database->Execute("UPDATE users SET admin=? WHERE id=?", array($yn, $this->id));
		log_info("core-user", "Made {$this->name} admin=$yn");
	}
	
	/**
	 * Zach: Added can() function: can(a user do $something)?
	 *		 Added get_permissions_for_user function: less database connections this way.
	 */
	private function get_permissions_for_user($userid) {
		/**
		 * This function isn't $user->can(), but rather will be implemented by it.
		 * We will:
		 *		Get preferences for $userid;
		 *		Get a list of permissions for group $prefs->get_string("user_group");
		 *		Set these as an array: $up and return.
		 */
		global $database;
		$user_group_prefs = new DatabasePrefs($database, $userid);
		$user_group = $user_group_prefs->get_string("user_group", "user");
		$user_permissions = $database->get_row("SELECT group_permissions FROM group_list WHERE group_name = ?", array($user_group));
		$up = explode(",", $user_permissions['group_permissions']);
		return $up;
	}
	
	public function can($do_something) {
		$perm_array = $this->get_permissions_for_user($this->id);
		if(in_array($do_something, $perm_array)) { return true; } else { return false; }
	}

	public function set_password($password) {
		global $database;
		$hash = md5(strtolower($this->name) . $password);
		$database->Execute("UPDATE users SET pass=? WHERE id=?", array($hash, $this->id));
		log_info("core-user", "Set password for {$this->name}");
	}

	public function set_email($address) {
		global $database;
		$database->Execute("UPDATE users SET email=? WHERE id=?", array($address, $this->id));
		log_info("core-user", "Set email for {$this->name}");
	}

	/**
	 * Get a snippet of HTML which will render the user's avatar, be that
	 * a local file, a remote file, a gravatar, a something else, etc
	 */
	public function get_avatar_html() {
		// FIXME: configurable
		global $config;
		if($config->get_string("avatar_host") == "gravatar") {
			if(!empty($this->email)) {
				$hash = md5(strtolower($this->email));
				$args = $config->get_string("avatar_gravatar_type").$config->get_string("avatar_gravatar_rating");
				return "<img class=\"avatar gravatar\" src=\"http://www.gravatar.com/avatar/$hash.jpg?$args\">";
			}
		}
		return "";
	}
}
?>
