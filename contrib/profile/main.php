<?php
/*
 * Name: Profile Extension
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
 * License: GPLv2
 * Description: Shows custom profile information.
 *
 *				I'm hoping to have this integrate into /user/[username]... I think it'll work.
 *
 *				Prereqs: userprefs (development version in git), 
 *						 preferences extension (for showing the options) (development version in git)
 */
 
/* Things the profile will show:
 * (--) determined automatically, (\\) entered manually
 * -- Username
 * -- Avatar
 * \\ Real Name
 * -- Email Address				(TODO: take this from /user/)
 * \\ AIM Address				(TODO: put it in as an aim:sendim?screenname=[]
 * \\ MSN Address				(TODO: same thing)
 * \\ [other addresses...]
 * \\ Birthday					(TODO: display this in MM\DD\YY format, not age in years)
 * -- Images uploaded			(TODO: get this from /user/)
 * -- Comments made				(TODO: get this from /user/)
 * \\ About me(longtext)
 * \\ Website
 * -- Tag Edits					(TODO: figure out how to get this. (tag_history perhaps?))
 * -- Note Edits				(TODO: once the notes extension is ready, figure out how to get this value)
 * -- Wiki Edits				(TODO: get count from database table)
 * -- Forum Posts				(TODO: once forum extension is ready, implement this value)
 * -- Pool Updates				(TODO: get count from database table if this exists)
 */
 
class Profile extends SimpleExtension {
	// Kinda like the config interface
	public function onPrefBuilding($event) {
		$pb = new PrefBlock("User Profile Info");
		$pb->add_text_option("profile_name","<br />Real Name");
		$pb->add_int_option("profile_age", "<br />Age in years");
		$pb->add_text_option("profile_website","<br />Website");
		$pb->add_text_option("profile_aim","<br />AIM Address");
		$pb->add_text_option("profile_msn","<br />MSN Address");
		//$pb->add_string_option("profile_yim","<br />YIM Address");		// Any other chat services add here.
		//$pb->add_string_option("profile_gtalk","<br />Gtalk Address");
		$pb->add_longtext_option("profile_aboutme","<br />About me (no HTML)");
		$event->panel->add_block($pb);
	}
	public function onUserPageBuilding(Event $event) { // This function appears to work.
		global $database, $user;
		$pi = new DatabasePrefs($database, $event->display_user->id); // testing...
		$realname = $pi->get_string("profile_name","No real name given");
		$age = $pi->get_int("profile_age", "Too many");
		$web = $pi->get_string("profile_website");
		$aim = $pi->get_string("profile_aim");
		$msn = $pi->get_string("profile_msn");
		//$yim = $pb->get_string("profile_yim");		// Any other chat services add here.
		//$gtalk = $pb->get_string("profile_gtalk");
		$about = $pi->get_string("profile_aboutme","No information given.");
		if($realname) $event->add_stats('Name: '.$realname);
		if($age) $event->add_stats($age.' years old');
		if($web) $event->add_stats('Website: <a href="'.$web.'">'.$web.'</a>');
		if($aim) $event->add_stats('AIM Address: <a href="aim:goim?screenname='.$aim.'&message=Hello!">'.$aim.'</a>');
		if($msn) $event->add_stats('MSN Address: '.$msn);
		//if($realname) $event->add_stats('YIM Address: '.$yim);
		//if($realname) $event->add_stats('Gtalk Address: '.$gtalk);
		if($about) $event->add_stats($about);
	}
}
?>