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
		
/*		// Get count of posts overall (TODO: exclude admins)
		$level_t_p = ceil($database->db->GetOne('SELECT COUNT(*) FROM `images`'));
		$tp = "$level_t_p";
		
		// Get count of comments overall (TODO: exclude admins)
		$level_t_c = ceil($database->db->GetOne('SELECT COUNT(*) FROM `comments`'));
		$tc = "$level_t_c";
		
		// Get count of tags overall (if tag history is enabled) (TODO: exclude admins)
		$tt = 0;
		if(class_exists("Tag_History")) {
			$level_t_t = ceil($database->db->GetOne('SELECT COUNT(*) FROM `tag_histories`'));
			$tt = "$level_t_t";
		}*/
		
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
		
/*		// Influence points are going to be extremely tough to generate actually, as when one person's influence increases, another's influence decreases.
		// To spare us the trouble right now, I'll leave it out.
		if($tp > 0) { $level['i_p'] = $p / $tp; } else { $level['i_p'] = 0; } // Influence on posts
		if($tc > 0) { $level['i_c'] = $c / $tc; } else { $level['i_c'] = 0; } // Influence on comments
		if($tt > 0) { $level['i_t'] = $t / $tt;	} else { $level['i_t'] = 0; } // Influence on tags
		
		// Finally, generate total influence points mode.
		if($tp==0 && $tc==0 && $tt==0) {
			$level['i_total'] = 0;
		} else {
			$level['i_total'] = ($p + $c + $t) / ($tp + $tc + $tt); 
		}
*/	
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
		 *		$level['i_p']@
		 *		$level['i_c']@
		 *		$level['i_t']@
		 *
		 *		$level['c_total']
		 *		$level['i_total']@
		 *
		 *		$level['bad']
		 * @ Disabled for now.
		*******************************/
		
		// Let's store all these in the preferences. This won't be changable by the user, but will be good for admins later in development.
		$prefs_user_level = new DatabasePrefs($database, $userid);
		
		$prefs_user_level->set_int("user_level_c_p",$level['c_p'], $userid);
		$prefs_user_level->set_int("user_level_c_c",$level['c_c'], $userid);
		$prefs_user_level->set_int("user_level_c_t",$level['c_t'], $userid);
		
/*		$prefs_user_level->set_int("user_level_i_p",$level['i_p'], $userid);
		$prefs_user_level->set_int("user_level_i_c",$level['i_c'], $userid);
		$prefs_user_level->set_int("user_level_i_t",$level['i_t'], $userid);*/
		
		$prefs_user_level->set_int("user_level_c_total",$level['c_total'], $userid);
//		$prefs_user_level->set_int("user_level_i_total",$level['i_total'], $userid);
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
		Probably most of the stuff we do will be done here.
		First, let's take care of modes.
		"Contributions" mode is going to be what I'm going to add right now,
		and "Influence" mode will be for the future. I'm not sure how
		both of these will coexist with each other, I'm thinking a database
		table that is as followed:
		
		Table user_levels:
			level_id	[primary key]	: Identifier (for coding reasons)
			level_name					: Identifier (for practical reasons)
			
			level_mode_c				: Total "Contributions" mode level equivelent (default)
				level_mode_c_p			: Posts required for level
				level_mode_c_c			: Comments required for level
				level_mode_c_t			: Tags required for level
			
			level_mode_i				: Total "Influence" mode level equivelent
				level_mode_i_p			: % of Posts required for level
				level_mode_i_c			: % of Comments required for level
				level_mode_i_t			: % of Tags required for level
		
		Now that we have the concept down, time for more advanced parts.
		"Multipliers" are basically incentives or demerits for each individual thing. For example:
			+ Gain 2 points for each image you upload, 1 for every tag, and 3 for each comment.
			- Lose 5 points for every "slap" or punishment.
				
		TODO: Create a database table that is like this.
		**/
		global $config;
		$config->set_default_int("user_level_m_p", 3);	// Multiplier for posts
		$config->set_default_int("user_level_m_c", 2);	// Multiplier for comments
		$config->set_default_int("user_level_m_t", 1);	// Multiplier for tags
		$config->set_default_int("user_level_m_s", 8);	// Multiplier for slaps (negative points)
		
		//global $user;
		//$userid = $user->id;
		//$this->set_user_level($userid);
	}
}


class User_Levels_Config extends SimpleExtension {
	/**
	 * This class handles configuration of the User_Levels system.
	 */
	
	public function onSetupBuilding($event) {
		$sb = new SetupBlock("User Levels");
		$sb->add_label("<i>Multipliers</i>");
		$sb->add_label("<br />(Encourage some things more than others)");
		$sb->add_int_option("user_level_m_p", "<br />Post Multiplier: ");
		$sb->add_int_option("user_level_m_c", "<br />Comment Multiplier: ");
		$sb->add_int_option("user_level_m_t", "<br />Tag Multiplier: ");
		$sb->add_int_option("user_level_m_s", "<br />Slap Multiplier: ");
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
	 * TODO: Get custom ranks and titles.
	 */
	
	public function onUserPageBuilding(Event $event) {
		global $database, $user, $page;
		$ul = new DatabasePrefs($database, $event->display_user->id);
		$user_level_total = $ul->get_int("user_level_c_total",0);
		$page->add_block(new Block('User Level','Total user level: '.$user_level_total.'.'));
	}
}

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
		if($user->is_admin()) {
			if($event->page_matches("user_levels/slap")) {
				if(isset($_POST['user_id']) && isset($_POST['user_name']) && isset($_POST['points'])) {
					if($_POST['points'] > 0) {
						$this->slap_user($_POST['user_id'], $_POST['points']);
						$GLOBALS['punish_user_id'] = $_POST['user_id'];
						send_event(new UserLevelsPunishmentEvent());
						$page->set_mode("redirect");
						$page->set_redirect(make_link("user/{$_POST['user_name']}"));
					} else { die("Error: did you enter a number greater than 1?"); }
				} else { die("Error: did you submit the form?"); }
			}
		} else { die("Error: must be admin"); }
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
	public function onPageRequest(Event $event) {
		/**
		 * Stub. Need to get the custom ranks first.
		 */
		if($event->page_matches("user_levels/slap")) {
			echo '<script type="text/javascript"> 
			$(function() {
				$("#progressbar").progressbar({
					value: 79.1236749117	});
			});
			</script>';
		}
	}
}

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
				$levels = $database->get_all("SELECT * FROM user_level_editor ORDER BY id ASC");
				$this->theme->display_editor($levels);
			}
		}
		if($event->page_matches("user_levels/add")) {
			/**
			 * Adds a user level
			 */			
			$level_name = $_POST['level_name'];
			$level_c_total = $_POST['level_c_total'];
			$level_c_p = $_POST['level_c_p'];
			$level_c_c = $_POST['level_c_c'];
			$level_c_t = $_POST['level_c_t'];
			
			if(!isset($level_name)) { die("No name!"); }
			if(!isset($level_c_total)) { $level_c_total = 0; }
			if(!isset($level_c_p)) { $level_c_p = 0; }
			if(!isset($level_c_c)) { $level_c_c = 0; }
			if(!isset($level_c_t)) { $level_c_t = 0; }
			
			$database->Execute("INSERT INTO aliases(id, level_name, level_c_total, level_c_p, level_c_c, level_c_t) VALUES(?, ?, ?, ?, ?, ?)", array('', $level_name, $level_c_total, $level_c_p, $level_c_c, $level_c_t));
			log_info("user_level_editor", "Added User Level: {$event->oldtag} -> {$event->newtag}");
		}
		if($event->page_matches("user_levels/remove")) {
		
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
		 * REMINDER: If I change the database tables, I need to change the version.
		 *
		 * FUTURE: Add this to the create table once I'm ready for influence points.
		 		 , level_i_p DECIMAL
				 , level_i_c DECIMAL
				 , level_i_t DECIMAL
				 , level_i_total DECIMAL
		 */
		 if($version < 1) {
		 	/**
		 	* Installer
		 	*/
			global $database;
			$database->create_table("user_level_editor",
                "id SCORE_AIPK
				 , level_name VARCHAR(128)
                 , level_c_p INTEGER
                 , level_c_c INTEGER
                 , level_c_t INTEGER
                 , level_c_total INTEGER
                 , INDEX(id)
                ");
			$config->set_int("user_level_editor", 1);
		}
	}
}
?>