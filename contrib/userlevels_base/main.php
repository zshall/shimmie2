<?php
/*
 * Name: User Levels Base
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com/]
 * License: GPLv2
 * Description: User Incentive System Base
 *				Counts up all the posts, comments, and tags a user has made and stores
 *				in the database as a level. Expands on the tiny library of userprefs extensions.
 *
 *				Yes, I know this is being stripped down into a lot of tiny extensions, but it's because I work on these things at
 *				different parts of the day, and thus it helps me to keep organized. TODO: consolidate this into one app.
 *
 *				Prereqs: userprefs class
 *
 *				Development TODO:
 *					+ Calculate values on ImageUploadEvent, CommentPostingEvent, Tag... something
 *					+ Punishment system
 *						- Add punishment points on ImageDeletionEvent, CommentDeletionEvent
 *						- "Slap" button on user profile (UserPageBuildingEvent)
 *						- Edit slap points on preferences page if admin
 *					+ Figure out a way to do influence points properly
 *						- Either have a percent of (user's total / (total - admins))
 *						- Or have a percentile system (how do I do this?)
 *					+ Custom ranks system
 *						- Editor
 *						- Table in database
 *						- STORE ALL VALUES, not just user_level. Name them user_level_c and user_level_i and stuff.
 */
 
class User_Levels_Base extends SimpleExtension {
	
	private function get_user_level($userid, $mode, $custom) {
		/**
		This simple function generates the specified user level and returns it.
		I'll need to decide sometime on what it actually does. :\
		
		Parameters:
			$userid		[]				: The ID of the user we want to work with
			$mode		default=c		: "Contribution" or "Influence" mode... which should be generated?
			$custom		default=false	: If this is true, we'll return everything separately (4-5 different variables)
													 false, we'll combine the results and return 1 variable
		**/
		global $database;
		
		// Get count of posts by user
		$level_mode_c_p = ceil($database->db->GetOne('SELECT COUNT(*) FROM `images` WHERE (`owner_id` = "'.$userid.'")'));
		$p = "$level_mode_c_p";
		
		// Get count of comments by user
		$level_mode_c_c = ceil($database->db->GetOne('SELECT COUNT(*) FROM `comments` WHERE (`owner_id` = "'.$userid.'")'));
		$c = "$level_mode_c_c";
		
		// Get count of tags (if tag history is enabled)
		$t = 0;
		if(class_exists("Tag_History")) {
			$level_mode_c_t = ceil($database->db->GetOne('SELECT COUNT(*) FROM `tag_histories` WHERE (`user_id` = "'.$userid.'")'));
			$t = "$level_mode_c_t";
		}
		
/*		// Get count of referrals (completely leaving this out for now, as it doesn't even exist, and might not ever exist.)
		$r = 0;
		if(class_exists("Referrals")) {
			$level_mode_c_r = ceil($database->db->GetOne('SELECT COUNT(*) FROM `user_prefs` WHERE (`name` = "testprefs_icecream" AND `value` = "none")'));
			$r = "$level_mode_c_r";
		}*/
		
		$level = 0;
		switch($mode) {
			case 'c':
				if($custom==false) {
					$level_mode_c = $p + $c + $t;
					return $level_mode_c;
				} else { // TODO: This next part I'm not so confident about. Untested, and won't be for a while. Oh dear.
					$level_mode_c['p'] = $p;
					$level_mode_c['c'] = $c;
					$level_mode_c['t'] = $t;
					return $level_mode_c;
				}
				break;
			case 'i':
				// TODO: figure out influence mode.
				break;
		}
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
				level_mode_c_p			: Posts^ required for level
				level_mode_c_c			: Comments^ required for level
				level_mode_c_t			: Tags^ required for level
				level_mode_c_r			: Referrals^* required for level
			
			level_mode_i				: Total "Influence" mode level equivelent
				level_mode_i_p			: % of Posts^ required for level
				level_mode_i_c			: % of Comments^ required for level
				level_mode_i_t			: % of Tags^ required for level
				level_mode_i_r			: % of Referrals^* required for level
		
		^ optional... not sure if I'll be implementing this, as it could be unnecessary, but it would add a lot more customization.
		* supposing a referral system exists.
		
		For now, I'm assuming things will be kept simple, as all posts, tags, and comments will be added together and stored as total user contribution.
		Our short-term goal is to have $user->level = [integer: sum(total post count + total comment count + total tag history changes)]
		
		For now, however, let's set some default settings for when "Influence" mode does come into play.
		
		TODO: Include a config panel with these options.
		**/
		global $config;
		$config->set_default_string("user_level_mode", "c");	// Setting the rank mode to "Contributions"
		$config->set_default_bool("user_level_custom", false);	// Turning off custom ranks for now.
		
		/**
		TODO: move this to private function, call on ImageUploadEvent, CommentPostingEvent, etc.
		The extension will now get the options that have been set, and implement them.
		**/
		global $user, $database;
		$userid = $user->id;
		$mode = $config->get_string("user_level_mode", "c");
		$custom = $config->get_bool("user_level_custom", false);
		// Now that we have all the variables, get the user level.
		$user_level = $this->get_user_level($userid, $mode, $custom);
		// And put it into the userprefs. This won't be changable by the user, but it's an easy way to store the data since we don't need yet another db table.
		$prefs_user_level = new DatabasePrefs($database, $userid);
		$prefs_user_level->set_int("user_level",$user_level, $userid);
	}
}
?>