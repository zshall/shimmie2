<?php
/*
 * Name: Theme Chooser
 * Author: Zach Hall <zach@sosguy.net>
 * License: GPLv2
 * Description: Designed for seemslegit.com - Theme selector
 *				Copying from myself copying from others. Using userprefs system
 *				as a theme selector, and the testprefs statistics graph to show
 *				who's using it.
 *
 *				Prereqs: modified theme (example in git), userprefs (development
 *						 version in git), preferences extension (for showing the
 *						 options) (development version in git)
 */
 
class ThemeChooser extends SimpleExtension {
	// Set defaults
	public function onInitExt($event) {
			global $userprefs;
			$userprefs->set_default_string("themechooser_theme", "style.css");
		}
	// Kinda like the config interface
	public function onPrefBuilding($event) {		
		// Testing other options
		$pb = new PrefBlock("Theme Options");
		$pb->add_choice_option("themechooser_theme",
			array('Cookies &#146;n cream'=>'style.css',
			'Chocolate'=>'chocolate.css',
			'Strawberry'=>'strawberry.css'), "<br>Choose a flavor."); //oh god... the copying!
			//Anyways, add your CSS files to that array to customize.
		$event->panel->add_block($pb);
	}
	// Stats
	public function onPageRequest($event) {
		global $page;
		if($event->page_matches("theme_stats")) {
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			} else {
				$body = $this->icecream_body();
				$this->theme->display_page($page, $body);
			}
		}
	}
	public function onUserBlockBuilding($event) {
		global $user;
			if(!$user->is_admin()) {
				$event->add_link("Theme Stats", make_link("theme_stats"));
			} 
	}
	// Show a chart of who liked what theme better.
	private function page_body() {
		global $database;
		global $config;
		$base_href = $config->get_string('base_href');
		$data_href = get_base_href();
		
		$vanilla = ceil($database->db->GetOne('SELECT COUNT(*) FROM `user_prefs` WHERE (`name` = "themechooser_icecream" AND `value` = "style.css")'));
		$v = "$vanilla"; // copying >_>
		
		$chocolate = ceil($database->db->GetOne('SELECT COUNT(*) FROM `user_prefs` WHERE (`name` = "themechooser_icecream" AND `value` = "chocolate.css")'));
		$c = "$chocolate";
		
		$strawberry = ceil($database->db->GetOne('SELECT COUNT(*) FROM `user_prefs` WHERE (`name` = "themechooser_icecream" AND `value` = "strawberry.css")'));
		$s = "$strawberry";
		
		// Add your own here.
		
		$body = "<h1>Results:</h1>";
		$body .= "<p>Who liked what theme the best?</p>";
		$body .= "<img src='".$data_href."/ext/theme_chooser/piechart.php?data=$v*$c*$s&label=Cookies%20&#146;n%20cream*Chocolate*Strawberry' />";
		return $body;
	}
}
?>