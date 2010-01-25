<?php
/**
 * User preferences - user_id, name, value. Rename hack of config.
 */
interface UserPrefs {
	/**
	 * Save the list of name:value pairs to wherever they came from,
	 * so that the next time a page is loaded it will use the new
	 * configuration
	 */
	public function save_prefs($name=null);

	/** @name set_*
	 * Set a configuration option to a new value, regardless
	 * of what the value is at the moment
	 */
	//@{
	public function set_int_userprefs($name, $value);
	public function set_string_userprefs($name, $value);
	public function set_bool_userprefs($name, $value);
	public function set_array_userprefs($name, $value);
	//@}

	/** @name set_default_*
	 * Set a configuration option to a new value, if there is no
	 * value currently. Extensions should generally call these
	 * from their InitExtEvent handlers. This has the advantage
	 * that the values will show up in the "advanced" setup page
	 * where they can be modified, while calling get_* with a
	 * "default" paramater won't show up.
	 */
	//@{
	public function set_default_int_userprefs($name, $value);
	public function set_default_string_userprefs($name, $value);
	public function set_default_bool_userprefs($name, $value);
	public function set_default_array_userprefs($name, $value);
	//@}

	/** @name get_*
	 * pick a value out of the table by name, cast to the
	 * appropritate data type
	 */
	//@{
	public function get_int_userprefs($name, $default=null);
	public function get_string_userprefs($name, $default=null);
	public function get_bool_userprefs($name, $default=null);
	public function get_array_userprefs($name, $default=array());
	//@}
}


/**
 * Common methods for manipulating the list, loading and saving is
 * left to the concrete implementation
 */
abstract class BasePrefs implements UserPrefs {
	var $values = array();

	public function set_int_userprefs($name, $value) {
		$this->values[$name] = parse_shorthand_int($value);
		$this->save_prefs($name);
	}
	public function set_string_userprefs($name, $value) {
		$this->values[$name] = strip_tags( $value ); // These aren't admins we're talking about. Not sure they
		$this->save_prefs($name);					 // should be able to use tags. Besides, BBcode is implemented.
	}
	public function set_bool_userprefs($name, $value) {
		$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		$this->save_prefs($name);
	}
	public function set_array_userprefs($name, $value) {
		assert(is_array($value));
		$this->values[$name] = implode(",", $value);
		$this->save_prefs($name);
	}

	public function set_default_int_userprefs($name, $value) {
		if(is_null($this->get_userprefs($name))) {
			$this->values[$name] = parse_shorthand_int($value);
		}
	}
	public function set_default_string_userprefs($name, $value) {
		if(is_null($this->get_userprefs($name))) {
			$this->values[$name] = $value;
		}
	}
	public function set_default_bool_userprefs($name, $value) {
		if(is_null($this->get_userprefs($name))) {
			$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		}
	}
	public function set_default_array_userprefs($name, $value) {
		assert(is_array($value));
		if(is_null($this->get_userprefs($name))) {
			$this->values[$name] = implode(",", $value);
		}
	}
	public function get_int_userprefs($name, $default=null) {
		return (int)($this->get_userprefs($name, $default));
	}
	public function get_string_userprefs($name, $default=null) { 
		return $this->get_userprefs($name, $default);
	}
	public function get_bool_userprefs($name, $default=null) {
		return undb_bool($this->get_userprefs($name, $default));
	}
	public function get_array_userprefs($name, $default=array()) {
		return explode(",", $this->get_userprefs($name, ""));
	}

	private function get_userprefs($name, $default=null) {
		if(isset($this->values[$name])) { 
			return $this->values[$name]; 
		}
		else {
			return $default;
		}
	}
}


/**
 * Loads the config list from a PHP file; the file should be in the format:
 *
 *  <?php
 *  $config['foo'] = "bar";
 *  $config['baz'] = "qux";
 *  ?>
 *  Unnecessary for what we're doing here... since it's different
 *  for every user, we wouldn't need static anything.
 */
/*class StaticPrefs extends BasePrefs {
	public function __construct($filename) {
		if(file_exists($filename)) {
			require_once $filename;
			if(isset($config)) {
				$this->values = $config;
			}
			else {
				throw new Exception("Config file '$filename' doesn't contain any config");
			}
		}
		else {
			throw new Exception("Config file '$filename' missing");
		}
	}

	public function save_prefs($name=null) {
		// static config is static
	}
}*/


/**
 * Loads the config list from a table in a given database, the table should
 * be called config and have the schema:
 *
 * \code
 *  CREATE TABLE config(
 *      name VARCHAR(255) NOT NULL,
 *      value TEXT
 *  );
 * \endcode
 */
class DatabasePrefs extends BasePrefs {
	var $database = null;
	
	/*
	 * Load user preferences from a the database.
	 */
	public function DatabasePrefs($database) {
		$this->database = $database;
		global $user;
		$userid = $user->id;
		$cached = $this->database->cache->get("user_prefs");
		if($cached) {
			$this->values = $cached;
		}
		else {
			$this->values = $this->database->db->GetAssoc("SELECT name, value FROM user_prefs WHERE user_id = $userid");
			$this->database->cache->set("user_prefs", $this->values);
		}
	}

	/*
	 * Save the current values for the current user.
	 */
	public function save_prefs($name=null) {
		global $user;
		$userid = $user->id;
		if(is_null($name)) {
			foreach($this->values as $name => $value) {
				$this->save_prefs($name);
			}
		}
		else {
			$this->database->Execute("DELETE FROM user_prefs WHERE name = ? AND user_id = ?", array($name, $userid));
			$this->database->Execute("INSERT INTO  `user_prefs` (  `user_id` ,  `name` ,  `value` ) VALUES ('$userid',  '$name',  '".$this->values[$name]."')");
		}
		$this->database->cache->delete("user_prefs");
	}
}
?>
