<?php
/*
 * Name: Downtime
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show a "down for maintenance" page
 * Documentation:
 *  Once installed there will be some more options on the config page --
 *  Ticking "disable non-admin access" will mean that regular and anonymous
 *  users will be blocked from accessing the site, only able to view the
 *  message specified in the box.
 */

class Downtime implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Downtime");
			$sb->add_bool_option("downtime", "Disable non-admin access: ");
			$sb->add_longtext_option("downtime_message", "<br>");
			$event->panel->add_block($sb);
		}

		if($event instanceof PageRequestEvent) {
			if($config->get_bool("downtime")) {
				$this->check_downtime($event);
				$this->theme->display_notification($page);
			}
		}
		
		if($event instanceof InitExtEvent) {
			global $config;
			$version = $config->get_int("pdef_downtime", 0);
			if($version < 1) {
				PermissionManager::set_perm("admin","login_while_down",true);
				$config->set_int("pdef_downtime", 1);
			}
		}
		
		if($event instanceof PermissionScanEvent) {
			$event->add_perm("login_while_down","Use site in downtime mode");
		}
	}

	private function check_downtime(PageRequestEvent $event) {
		global $user, $config;

		if($config->get_bool("downtime") && !$user->can("login_while_down") &&
				($event instanceof PageRequestEvent) && !$this->is_safe_page($event)) {
			$msg = $config->get_string("downtime_message");
			$this->theme->display_message($msg);
		}
	}

	private function is_safe_page(PageRequestEvent $event) {
		if($event->page_matches("user_admin/login")) return true;
		else return false;
	}
}
add_event_listener(new Downtime(), 10);
?>
