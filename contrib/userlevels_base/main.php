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
	**/
	
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
	
	private function set_user_level() {
		/**
		TODO: call on ImageUploadEvent, CommentPostingEvent, etc.
		The extension will now get the options that have been set, and implement them.
		**/
		global $user, $database;
		$userid = $user->id;
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
	
	public function onImageUpload(Event $event) {
		$this->set_user_level();
	}
	
	public function onCommentPosting(Event $event) {
		$this->set_user_level();
	}
	
	public function onTagSet(Event $event) {
		$this->set_user_level();
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
		
		$this->set_user_level(); // Debugging.
	}
}


class User_Levels_Config extends SimpleExtension {
	/**
	 * This class handles configuration of the User_Levels system.
	**/
	
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
			$pb->add_int_option("user_level_c_p", "<br />Post points");
			$pb->add_int_option("user_level_c_c", "<br />Comment points");
			$pb->add_int_option("user_level_c_t", "<br />Tag points");
			
			$pb->add_int_option("user_level_c_total", "<br />Total points");
			$event->panel->add_block($pb);
		}
	}		
}


class User_Levels_Profile extends SimpleExtension {
	/**
	 * This section puts the user's level on their profile.
	 *
	 * TODO: Get custom ranks and titles.
	**/
	
	public function onUserPageBuilding(Event $event) {
		global $database, $user, $page;
		$ul = new DatabasePrefs($database, $event->display_user->id);
		$user_level_total = $ul->get_int("user_level_c_total",0);
		$page->add_block(new Block('User Level','Total user level: '.$user_level_total.'.'));
	}
}


class User_Levels_Punishment extends SimpleExtension {	
	/**
	 * This section deals with the punishment system.
	**/
	
	public function onUserPageBuilding(Event $event) {
		/**
		 * When we view the user's profile page, if we are admin, display a "slap" button.
		 */
		global $user, $page;
		if($user->is_admin()) {
			$page->add_block(new Block("Punish User", "If a user is misbehaving, click this button to slap sense into him/her."));
		}
	}
}
?>