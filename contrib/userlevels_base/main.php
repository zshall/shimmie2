<?php
/*
 * Name: User Levels
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com/]
 * License: GPLv2
 * Description: User Incentive System (with extensive documentation)
 *				Counts up all the posts, comments, and tags a user has made and stores
 *				in the database as a level. Expands on the tiny library of userprefs extensions.
 *
 *				Yes, I know this is being stripped down into a lot of tiny extensions, but it's because I work on these things at
 *				different parts of the day, and thus it helps me to keep organized. TODO: consolidate this into one app.
 *
 *				Prereqs: userprefs class
 *
 *				Development TODO now at http://github.com/zshall/shimmie2/issues
 */
 
class User_Levels_Base extends SimpleExtension {
	/**
	 * This section handles invisible functions, such as getting and setting the user level.
	 */
	
	private function get_user_level($userid) {
		/**
		This simple function generates the specified user level and returns it.
		I'll need to decide sometime on what it actually does. :\
		
		Parameters:
			$userid		[]				: The ID of the user we want to work with
		
		Custom and mode won't matter... we'll just return everything in an array ^_^
		**/
		global $database;
		
		// Get count of posts by user
		$level_u_p = ceil($database->db->GetOne('SELECT COUNT(*) FROM `images` WHERE (`owner_id` = "'.$userid.'")'));
		$p = "$level_u_p";
		
		// Get count of comments by user
		$level_u_c = ceil($database->db->GetOne('SELECT COUNT(*) FROM `comments` WHERE (`owner_id` = "'.$userid.'")'));
		$c = "$level_u_c";
		
		// Get count of tags by user (if tag history is enabled)
		$t = 0;
		if(class_exists("Tag_History")) {
			// Prevent cheating:
			$level_u_t = ceil($database->db->GetOne('SELECT COUNT(DISTINCT `image_id`) FROM `tag_histories` WHERE (`user_id` = '.$userid.')'));
			$t = "$level_u_t";
		}
		
		// Let's generate a lot of numbers then. First, get multipliers.
		global $config;
		$mp = $config->get_int("user_level_m_p");
		$mc = $config->get_int("user_level_m_c");
		$mt = $config->get_int("user_level_m_t");
		$ms = $config->get_int("user_level_m_s");
		
		// Get punishment values
		$prefs_user_level_slap = new DatabasePrefs($database, $userid);
		$us = $prefs_user_level_slap->get_int("user_level_s");
		$level['bad'] = $us * $ms;
		
		// Now, contribution points
		$level['c_p'] = $p * $mp;		// Post count
		$level['c_c'] = $c * $mc;		// Comment count
		$level['c_t'] = $t * $mt;		// Tag count
		
		// Generate total contribution point level
		$level['c_total'] = ($level['c_p'] + $level['c_c'] + $level['c_t']) - $level['bad'];
	
		// We are done! Returning generated values as a big array ^_^
		return $level;
	}
	
	private function set_user_level($userid) {
		/**
		Userid is now passed on to this by whatever function is calling it. Makes it more portable.
		The extension will now get the options that have been set, and implement them.
		**/
		global $database;
		
		// Now that we have all the preliminaries, get the big array of levels and ranks.
		$level = $this->get_user_level($userid);
		/** REMINDER ******************
		 * What variables do we have?
		 * 		$level['c_p']
		 *		$level['c_c']
		 *		$level['c_t']
		 *
		 *		$level['c_total']
		 *
		 *		$level['bad']
		 *
		*******************************/
		
		// Let's store all these in the preferences. This won't be changable by the user, but will be good for admins later in development.
		$prefs_user_level = new DatabasePrefs($database, $userid);
		
		$prefs_user_level->set_int("user_level_c_p",$level['c_p'], $userid);
		$prefs_user_level->set_int("user_level_c_c",$level['c_c'], $userid);
		$prefs_user_level->set_int("user_level_c_t",$level['c_t'], $userid);

		$prefs_user_level->set_int("user_level_c_total",$level['c_total'], $userid);
		
		// Send the event to all who might use it. Also, find a way around using globals.
		$GLOBALS['user_level_update_id'] = $userid;
		send_event(new UserLevelsUpdateEvent());
	}
	
	// onEvents go here.
	
	public function onUserLevelsPunishment(Event $event) {
		if(isset($GLOBALS['punish_user_id'])) {$this->set_user_level($GLOBALS['punish_user_id']);}
	}
	
	public function onPrefSave(Event $event) {
		if(isset($GLOBALS['uid-preferences'])) {
			$userid = $GLOBALS['uid-preferences'];
			$this->set_user_level($userid);
		}
	}
	
	public function onImageUpload(Event $event) {
		global $user;
		$userid = $user->id;
		$this->set_user_level($userid);
	}
	
	public function onCommentPosting(Event $event) {
		global $user;
		$userid = $user->id;
		$this->set_user_level($userid);
	}
	
	public function onTagSet(Event $event) {
		global $user;
		$userid = $user->id;
		$this->set_user_level($userid);
	}
	
	public function onInitExt(Event $event) {
		/**
		 * How wrong I was when I first started with this project. onInitExt would cause
		 * unnecessarily lengthened load times.
		 */
		global $config;
		$config->set_default_int("user_level_m_p", 3);	// Multiplier for posts
		$config->set_default_int("user_level_m_c", 2);	// Multiplier for comments
		$config->set_default_int("user_level_m_t", 1);	// Multiplier for tags
		$config->set_default_int("user_level_m_s", 8);	// Multiplier for slaps (negative points)
	}
}


class User_Levels_Config extends SimpleExtension {
	/**
	 * This class handles configuration of the User_Levels system.
	 */
	
	public function onSetupBuilding($event) {
		$sb = new SetupBlock("User Levels");
		$sb->add_label("<i>Multipliers</i>");
		$sb->add_label("<br />(Encourage some things more than others)<br />DO NOT SET TO NEGATIVE VALUES.");
		$sb->add_int_option("user_level_m_p", "<br />Post Multiplier: ");
		$sb->add_int_option("user_level_m_c", "<br />Comment Multiplier: ");
		$sb->add_int_option("user_level_m_t", "<br />Tag Multiplier: ");
		$sb->add_int_option("user_level_m_s", "<br />Slap Multiplier: ");
		$sb->add_label("<br /><i>Level-up Multiplier:</i>");
		$sb->add_label("<br />(Decimals OK) The higher this is, the faster people will 'level up.'");
		$sb->add_text_option("user_level_m_l", "<br />Level-up Multiplier: ");
		$event->panel->add_block($sb);
	}
	/**
	 * When we view the user's prefrerences page, if we are admin, show the levels (not really to be set, but for observation.)
	 */
	public function onPrefBuilding($event) {
		global $user, $config;
		if($user->is_admin()) {
			$pb = new PrefBlock("User Level");
			$pb->add_label("<i>Changable fields</i>");
			$pb->add_int_option("user_level_s", "<br />Punishment points: ");
			$ms = $config->get_int("user_level_m_s");
			$pb->add_label(" * ".$ms);
			
			$pb->add_label("<br /><i>Unchangable stats (for viewing)</i>");
			
			$pb->add_label("<br />Points (with multipliers applied)");
			$pb->add_int_option("user_level_c_p", "<br />Post points", true);
			$pb->add_int_option("user_level_c_c", "<br />Comment points", true);
			$pb->add_int_option("user_level_c_t", "<br />Tag points", true);
			
			$pb->add_int_option("user_level_c_total", "<br />Total points", true);
			$event->panel->add_block($pb);
		}
	}		
}


class User_Levels_Profile extends SimpleExtension {
	/**
	 * This section puts the user's level on their profile.
	 *
	 * ... or not. The user experience system would be a lot more cool
	 * than this. Scroll down a bit to see how development on it is going.
	 */
	
//	public function onUserPageBuilding(Event $event) {
//		global $database, $user, $page;
//		$ul = new DatabasePrefs($database, $event->display_user->id);
//		$user_level_total = $ul->get_int("user_level_c_total",0);
//		$page->add_block(new Block('User Level','Total user level: '.$user_level_total.'.'));
//	}
}

// Null events
class UserLevelsUpdateEvent extends Event {}
class UserLevelsPunishmentEvent extends Event {}


class User_Levels_Punishment extends SimpleExtension {	
	/**
	 * This section deals with the punishment system.
	 */
	
	private function slap_user($userid, $points=null) {
		/**
		 * Slap function.
		 */
		//Don't let these errors mess us up.
		if($userid==NULL) { die('No userid.'); }
		if($points==NULL) { $points = 1; }
		global $database, $page;
		// Start a user_prefs with data for the current user.
			$prefs_user_level_slap = new DatabasePrefs($database, $userid);
		// Get the current value of punishment
			$current_punishment_level = $prefs_user_level_slap->get_int("user_level_s");
		// Increase this number by $points
			$new_punishment_level = $current_punishment_level + $points;
		// Save the new value of punishment
			$prefs_user_level_slap->set_int("user_level_s", $new_punishment_level, $userid);
	}
	
	public function onUserPageBuilding(Event $event) {
		/**
		 * When we view the user's profile page, if we are admin, display a "slap" button.
		 */
		global $user, $page, $config;
		if($user->is_admin()) {
			$uid = $event->display_user->id;
			$un = $event->display_user->name;
			$ms = $config->get_int("user_level_m_s");
			$page->add_block(new Block("Punish User", "
				If a user is misbehaving, click this button to slap sense into him/her.<br />
				<form action='".make_link("user_levels/slap")."' method='POST'>
				Hate level: <input type='text' name='points' size='5' style='text-align:center;'> * $ms<br />
				<input type='hidden' name='user_name' value='$un'>
				<input type='hidden' name='user_id' value='$uid'>
				<input type='submit' value='Slap'>
				</form>
			"));
		}
	}
	
	public function onPageRequest(Event $event) {
		/**
		 * The second part of the punishment form...
		 */
		global $user, $page;
		if($event->page_matches("user_levels/slap")) {
			if($user->is_admin()) {
				if(isset($_POST['user_id']) && isset($_POST['user_name']) && isset($_POST['points'])) {
					if($_POST['points'] > 0) {
						$this->slap_user($_POST['user_id'], $_POST['points']);
						$GLOBALS['punish_user_id'] = $_POST['user_id'];
						send_event(new UserLevelsPunishmentEvent());
						$page->set_mode("redirect");
						$page->set_redirect(make_link("user/{$_POST['user_name']}"));
					} else { die("Error: did you enter a number greater than 1?"); }
				} else { die("Error: did you submit the form?"); }
			} else { die("Error: must be admin"); }
		} 
	}
	
	// onEvents go here.
	
	public function onCommentDeletion(Event $event) {
		/**
		 * Slaps a user with 1 un-multiplied point when a comment of theirs needs to be deleted.
		 * Looks like I'm only provided the comment id, not the ID of the user who posted it.
		 * Oh well, time to break out the database.
		 *
		 * FIXME: We can't select the owner_id... after the comment has been deleted. Argh.
		global $database;
		$userid = $database->get_row("SELECT owner_id FROM comments WHERE id = ?", array($event->comment_id));
		$GLOBALS['punish_user_id'] = $userid;
        $this->slap_user($userid,1);
		send_event(new UserLevelsPunishmentEvent());
		
		*/
	}

	public function onImageDeletion($event) {
		/**
		 * Slaps a user with 1 un-multiplied point when an image of theirs needs to be deleted.
		 */
		global $database;
		$userid = $event->image->owner_id;
		$GLOBALS['punish_user_id'] = $userid;
		$this->slap_user($userid,1);
		send_event(new UserLevelsPunishmentEvent());
	}
}

class User_Level_Experience extends SimpleExtension {
	private function get_level($exp_points, $multiplier) {
	/**
	 * If we have exp points, we can find out what level someone is.
	 * This basically takes the formula in reverse, solving for x.
	 */
		$level = floor(sqrt($exp_points / $multiplier));
		return $level;
	}
	
	private function get_exp($level,$multiplier) {
	/**
	 * If we know a level, we can find out how many experience points
	 * are required to reach it. This interprets the formula in a fashion
	 * that solves for y.
	 */
		$exp_reqd = floor($multiplier*(pow($level,2)));
		return $exp_reqd;
	}

	private function show_exp_bar() {
	/**
	 * FINISH THIS TOMORROW!
	 */

		// Get variables
		$ulprefs = new DatabasePrefs($database, $userid);
	
		$html = '<link type="text/css" href="/contrib/userlevels_base/css/custom-theme/jquery-ui-1.7.2.custom.css" rel="stylesheet" /> 
		<script type="text/javascript" src="/contrib/userlevels_base/js/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="/contrib/userlevels_base/js/jquery-ui-1.7.2.custom.min.js"></script>';
		$html .=   '<script type="text/javascript"> 
				$(function() {
					$("#progressbar'.$up.'").progressbar({
						value: '.$pp.'	});
				});
				</script>
				<div align="center"><div id="progressbar'.$up.'" title="'.$pp.'%" style="height: 20px;"></div></div>';
		$testing = 0;
		if($testing ==1) {
		$html .= "<br />User's points level: $up<br />
			  Level multiplier: $lm<br />
			  <br />
			  User's level: $ul<br />
			  Level $ul's exp: $lc<br />
			  Next level's exp: $ln<br />
			  <br />
			  Points required until next level-up: $pl<br />
			  Progress bar: $pn / $pm = $pp%";
		} else {
		$html .= "<b>User's level: $ul</b><br />
			  
			  <br />Experience: $up<br />

			  Points required until next level-up: $pl<br />";
		}
		
		return $html;
		
	}

	private function generate_exp_bar($userid) {
		/**
		 * In development. Perhaps this should go in the theme file?
		 *
		 * Actually... the generating of this file should happen during user level determination!
		 */

		/**
		 * This page will get the current user's exp and display it to him/her.
		 * Include the jQueryUI and related theme files.
		 */
		global $config, $database;
		$ulprefs = new DatabasePrefs($database, $userid);
		/**
		 * We shall do the following:
		 * 1. Get the user's total points ($up)
		 * 2. Get the multiplier ($lm)
		 * 3. Get the level from the total points and the multiplier ($ul)
		 * 4. Get the current level's required points ($lc)
		 * 5. Get the next level's required points ($ln)
		 * 6. Calculate the points until the next level ($pl)
		 * 7. Calculate the maximum progress bar value ($pm)
		 * 8. Calculate the progress bar's numerator ($pn)
		 * 9. Calculate the progress bar's percentage ($pp)
		 */
		$up = $ulprefs->get_int("user_level_c_total");
		$lm = $config->get_string("user_level_m_l");
		
		$ul = $this->get_level($up,$lm);
		$lc = $this->get_exp($ul, $lm);
		$ln = $this->get_exp(($ul+1), $lm);
		
		$pl = $ln - $up;
		$pm = $ln - $lc;
		$pn = $up - $lc;
		
		$pp = ($pn / $pm) * 100;
		
		/**
		 * Now that we have all of this information, let's save it.
		 */
		$ulprefs->set_int("user_level_l",$ul,$userid);
		$ulprefs->set_int("user_level_c_remain",$pl,$userid);
		$ulprefs->set_int("user_level_p_percent",$pp,$userid);
	}
	
	public function onUserPageBuilding(Event $event) {
		global $database, $user, $page;
		$html = $this->generate_exp_bar($event->display_user->id);
		$page->add_block(new Block('Level Stats',$html, "main", 5));
	}
	
	public function onUserLevelsUpdate(Event $event) {
		if(isset($GLOBALS['user_level_update_id'])) {$this->generate_exp_bar();} else {echo "argh no userid";}
	}
}

?>