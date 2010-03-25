<?php
/*
 * Name: Image Ratings
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Allow users to rate images "safe", "questionable" or "explicit"
 *				Zach: working with this to create a queue / schedule system. Right now, I'll just add a new rating (i for invisible) that's only visible to admins.
 *					  Next, I'll work on a script that'll "release" queued images. Perhaps a cron job will suffice. /queued/release?
 *					  Also... can_rate($userid)... if a user is admin, this will return "extended". They'll get the invisible option.
 */

class RatingSetEvent extends Event {
	var $image, $user, $rating;

	public function RatingSetEvent(Image $image, User $user, $rating) {
		assert(in_array($rating, array("s", "q", "e", "u", "i")));
		$this->image = $image;
		$this->user = $user;
		$this->rating = $rating;
	}
}

class Ratings implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof AdminBuildingEvent) {
			$this->theme->display_bulk_rater();
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("admin/bulk_rate")) {
			global $database, $user, $page;
			if(!$user->is_admin()) {
				throw PermissionDeniedException();
			}
			else {
				$n = 0;
				while(true) {
					$images = Image::find_images($n, 100, Tag::explode($_POST["query"]));
					if(count($images) == 0) break;
					foreach($images as $image) {
						send_event(new RatingSetEvent($image, $user, $_POST['rating']));
					}
					$n += 100;
				}
				#$database->execute("
				#	update images set rating=? where images.id in (
				#		select image_id from image_tags join tags
				#		on image_tags.tag_id = tags.id where tags.tag = ?);
				#	", array($_POST["rating"], $_POST["tag"]));
				$page->set_mode("redirect");
				$page->set_redirect(make_link("admin"));
			}
		}

		if($event instanceof InitExtEvent) {
			if($config->get_int("ext_ratings2_version") < 2) {
				$this->install();
			}

			$config->set_default_string("ext_rating_anon_privs", 'squ');
			$config->set_default_string("ext_rating_user_privs", 'sqeu');
			$config->set_default_string("ext_rating_admin_privs", 'sqeui');
		}

		if($event instanceof RatingSetEvent) {
			$this->set_rating($event->image->id, $event->rating);
		}

		if($event instanceof ImageInfoBoxBuildingEvent) {
			if($this->can_rate()) {
				$extended = $this->can_rate() == "extended" ? true : false;
				$event->add_part($this->theme->get_rater_html($event->image->id, $event->image->rating, $extended), 80);
			}
		}

		if($event instanceof ImageInfoSetEvent) {
			if($this->can_rate() && isset($_POST["rating"])) {
				send_event(new RatingSetEvent($event->image, $user, $_POST['rating']));
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$privs = array();
			$privs['Safe Only'] = 's';
			$privs['Safe and Unknown'] = 'su';
			$privs['Safe and Questionable'] = 'sq';
			$privs['Safe, Questionable, Unknown'] = 'squ';
			$privs['All'] = 'sqeui';

			$sb = new SetupBlock("Image Ratings");
			$sb->add_choice_option("ext_rating_anon_privs", $privs, "Anonymous: ");
			$sb->add_choice_option("ext_rating_user_privs", $privs, "<br>Users: ");
			$sb->add_choice_option("ext_rating_admin_privs", $privs, "<br>Admins: ");
			$event->panel->add_block($sb);
		}

		if($event instanceof ParseLinkTemplateEvent) {
			$event->replace('$rating', $this->theme->rating_to_name($event->image->rating));
		}

		if($event instanceof SearchTermParseEvent) {
			$matches = array();
			if(is_null($event->term) && $this->no_rating_query($event->context)) {
				$set = Ratings::privs_to_sql(Ratings::get_user_privs($user));
				$event->add_querylet(new Querylet("rating IN ($set)"));
			}
			if(preg_match("/^rating=([sqeui]+)$/", $event->term, $matches)) {
				$sqes = $matches[1];
				$arr = array();
				for($i=0; $i<strlen($sqes); $i++) {
					$arr[] = "'" . $sqes[$i] . "'";
				}
				$set = join(', ', $arr);
				$event->add_querylet(new Querylet("rating IN ($set)"));
			}
			if(preg_match("/^rating=(safe|questionable|explicit|unknown|invisible)$/", strtolower($event->term), $matches)) {
				$text = $matches[1];
				$char = $text[0];
				$event->add_querylet(new Querylet("rating = ?", array($char)));
			}
		}
		
		if($event instanceof DisplayingImageEvent) {
			/**
			 * Deny images upon insufficient permissions.
			 **/
			global $user, $database, $page;
			$user_view_level = Ratings::get_user_privs($user);
			$user_view_level = preg_split('//', $user_view_level, -1);
			$image_level = $database->get_row("SELECT  `rating` FROM  `images` WHERE id =?",$event->image->id);
			$image_level = $image_level["rating"];
			if(!in_array($image_level, $user_view_level)) {
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/list"));
			}
		}
	}

	public static function get_user_privs($user) {
		global $config;
		if($user->is_anonymous()) {
			$sqes = $config->get_string("ext_rating_anon_privs");
		}
		else if($user->is_admin()) {
			$sqes = $config->get_string("ext_rating_admin_privs");
		}
		else {
			$sqes = $config->get_string("ext_rating_user_privs");
		}
		return $sqes;
	}

	public static function privs_to_sql($sqes) {
		$arr = array();
		for($i=0; $i<strlen($sqes); $i++) {
			$arr[] = "'" . $sqes[$i] . "'";
		}
		$set = join(', ', $arr);
		return $set;
	}

	public static function rating_to_human($rating) {
		switch($rating) {
			case "s": return "Safe";
			case "q": return "Questionable";
			case "e": return "Explicit";
			case "i": return "Invisible";
			default:  return "Unknown";
		}
	}

	// FIXME: this is a bit ugly and guessey, should have proper options
	private function can_rate() {
		global $config, $user;
		if($user->is_anonymous() && $config->get_string("ext_rating_anon_privs") == "sqeu") return false;
		if($user->is_admin()) return "extended";
		if(!$user->is_anonymous() && $config->get_string("ext_rating_user_privs") == "sqeu") return true;
		return false;
	}

	private function no_rating_query($context) {
		foreach($context as $term) {
			if(preg_match("/^rating=/", $term)) {
				return false;
			}
		}
		return true;
	}

	private function install() {
		global $database;
		global $config;

		if($config->get_int("ext_ratings2_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN rating CHAR(1) NOT NULL DEFAULT 'u'");
			$database->Execute("CREATE INDEX images__rating ON images(rating)");
			$config->set_int("ext_ratings2_version", 3);
		}

		if($config->get_int("ext_ratings2_version") < 2) {
			$database->Execute("CREATE INDEX images__rating ON images(rating)");
			$config->set_int("ext_ratings2_version", 2);
		}

		if($config->get_int("ext_ratings2_version") < 3) {
			$database->Execute("ALTER TABLE images CHANGE rating rating CHAR(1) NOT NULL DEFAULT 'u'");
			$config->set_int("ext_ratings2_version", 3);
		}
	}

	private function set_rating($image_id, $rating) {
		global $database;
		$database->Execute("UPDATE images SET rating=? WHERE id=?", array($rating, $image_id));
	}
}
add_event_listener(new Ratings());

/*
 * Name: Advance Queue (dependent version)
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
 * License: GPLv2
 * Description: Selects a number of images rated as "i", and changes them to "u"
 * 				Uses ratings extension for convienence.
 */
class AdvanceQueue extends SimpleExtension {
	public function onPageRequest($event) {
		global $page, $database, $config;
		$html = "<pre>";
		if($event->page_matches("advance_queue")) {
			$PASSPHRASE = $config->get_string("ext_rating_queue_passphrase", "changeme");
			if($PASSPHRASE != "changeme") {
				if($PASSPHRASE != "" && !is_null($PASSPHRASE)) {
					if($event->get_arg(0) == $PASSPHRASE) {
						$html .= "Passphrase OK: ".$PASSPHRASE."<br />";
						if(is_numeric($event->get_arg(1)) && $event->get_arg(1) > 0 && $event->get_arg(1) < 100) {
							$limit = int_escape($event->get_arg(1));
							$html .= "Number OK: $limit<br /><br />";
							$image_list = $database->get_all("SELECT `id`
															FROM `images`
															WHERE `rating` = 'i'
															ORDER BY `id` ASC
															LIMIT ?", $limit);
							for($i=0;$i<count($image_list);$i++) {
								$image_id = $image_list[$i]['id'];
								$database->Execute("UPDATE images SET rating=? WHERE id=?", array("u", $image_id));
								$html .= "Changed image #".$image_id."'s status from i to u.<br />";
							}
							$html .= "<br /> All completed successfully.";
						} else {
							$html .= "Number error.";
						}
					} else {
						$html .= "Passphrase error: incorrect passphrase.";
					}
				} else {
					$html .= "Passphrase error: blank passphrase!";
				}
			} else {
				$html .= "Passphrase error: default passphrase. Change your passphrase in /setup/";
			}
			$page->set_mode("data");
			$html .= "</pre>";
			$page->set_data($html);
		}
	}
	
	public function onSetupBuilding($event) {
		global $config;
		$sb = new SetupBlock("Queue Options");
		$sb->add_label("To use the queue, rate some images as 'invisible', and then visit /advance_queue/PASSPHRASE/x where x is how many images you want to release from the queue. I recommend setting up a cronjob to do this so that 'new' content is added every day!");
		$sb->add_text_option("ext_rating_queue_passphrase", "<br />Passphrase: ");
		$event->panel->add_block($sb);
	}
	
	public function onInitExt($event) {
		global $config;
		$config->set_default_string("ext_rating_queue_passphrase", "changeme");
	}
}
?>
