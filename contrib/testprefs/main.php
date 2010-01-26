<?php
/*
 * Name: Test Prefs App
 * Author: Zach Hall <zach@sosguy.net>
 * License: Public Domain
 * Description: Greets a user with text of his or her choice on the sidebar if they so choose.
 *				This could be good for displaying text only to anonymous users if I can get it so the admin can
 *				edit preferences for user_id 1.
 */
 
class TestPrefs extends SimpleExtension {
	public function onPostListBuilding($event) {
		global $userprefs, $page;
		if(strlen($userprefs->get_string("testprefs_text")) > 0 && $userprefs->get_bool("testprefs_display") == TRUE) {
			$this->theme->greeting($page, $userprefs->get_string("testprefs_text"));
		}
	}
	
	public function onPrefBuilding($event) {
		$pb = new PrefBlock("Sample Extension Prefs");
		$pb->add_bool_option("testprefs_display", "Greeting off / on");
		$pb->add_longtext_option("testprefs_text","<br />Write a custom greeting for yourself to see in the sidebar!");
		$event->panel->add_block($pb);
	}
}
?>