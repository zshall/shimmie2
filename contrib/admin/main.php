<?php
/**
 * Name: Admin Controls
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Various things to make admins' lives easier
 * Documentation:
 *  <p>Convert to InnoDB:
 *  <br>Convert your database tables to InnoDB, thus allowing SCore to
 *  take advantage of useful InnoDB-only features (this should be done
 *  automatically, this button only exists as a backup). This only applies
 *  to MySQL -- all other databases come with useful features enabled
 *  as standard.
 *  <p>Database dump:
 *  <br>Download the contents of the database in plain text format, useful
 *  for backups.
 */

/**
 * Sent when the admin page is ready to be added to
 */
class AdminBuildingEvent extends Event {
	var $page;
	public function AdminBuildingEvent($page) {
		$this->page = $page;
	}
}

class AdminPage implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("admin")) {
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			}
			else {
				send_event(new AdminBuildingEvent($page));
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("admin_utils")) {
			if($user->is_admin()) {
				log_info("admin", "Util: {$_POST['action']}");
				set_time_limit(0);
				$redirect = false;

				switch($_POST['action']) {
					case 'convert to innodb':
						$this->convert_to_innodb();
						$redirect = true;
						break;
					case 'database dump':
						$this->dbdump($page);
						break;
				}

				if($redirect) {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("admin"));
				}
			}
		}

		if($event instanceof AdminBuildingEvent) {
			$this->theme->display_page($page);
			$this->theme->display_form($page);
		}

		if($event instanceof UserBlockBuildingEvent) {
			if($user->is_admin()) {
				$event->add_link("Board Admin", make_link("admin"));
			}
		}
	}

	private function dbdump($page) {
		include "config.php";

		$matches = array();
		preg_match("#(\w+)://(\w+):(\w+)@([\w\.\-]+)/([\w_]+)(\?.*)?#", $database_dsn, $matches);
		$software = $matches[1];
		$username = $matches[2];
		$password = $matches[3];
		$hostname = $matches[4];
		$database = $matches[5];

		switch($software) {
			case 'mysql':
				$cmd = "mysqldump -h$hostname -u$username -p$password $database";
				break;
		}

		$page->set_mode("data");
		$page->set_type("application/x-unknown");
		$page->set_filename('SCore-'.date('Ymd').'.sql');
		$page->set_data(shell_exec($cmd));
	}

	private function convert_to_innodb() {
		global $database;
		if($database->engine->name == "mysql") {
			$tables = $database->db->MetaTables();
			foreach($tables as $table) {
				log_info("upgrade", "converting $table to innodb");
				$database->execute("ALTER TABLE $table TYPE=INNODB");
			}
		}
	}
}
add_event_listener(new AdminPage());
?>
