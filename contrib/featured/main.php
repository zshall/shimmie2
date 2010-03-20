<?php
/*
 * Name: Featured Image
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Bring a specific image to the users' attentions
 * Documentation:
 *  Once enabled, a new "feature this" button will appear next
 *  to the other image control buttons (delete, rotate, etc).
 *  Clicking it will set the image as the site's current feature,
 *  which will be shown in the side bar of the post list.
 *  <p><b>Viewing a featured image</b>
 *  <br>Visit <code>/featured_image/view</code>
 *  <p><b>Downloading a featured image</b>
 *  <br>Link to <code>/featured_image/download</code>. This will give
 *  the raw data for an image (no HTML). This is useful so that you
 *  can set your desktop wallpaper to be the download URL, refreshed
 *  every couple of hours.
 */
/**
 * Zach: going to a table of featured images, so we can recall them later.
 */
class Featured extends SimpleExtension {
	public function onInitExt($event) {
		global $config;
		$config->set_default_int('featured_id', 0);

		/**
		 * Zach: Installing data tables.
		 */
		global $config;
		$version = $config->get_int("featured_version", 0);
		/**
		 * If this version is less than "1", it's time to install.
		 *
		 * REMINDER: If I change the database tables, I must change up version by 1.
		 */
		 if($version < 1) {
		 	/**
		 	* Installer
		 	*/
			global $database, $config;
			$database->create_table("featured",
				 "id SCORE_AIPK
				 , feature_date SCORE_DATETIME DEFAULT SCORE_NOW
				 , feature_image_id INTEGER NOT NULL
				 , feature_best SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N");
			log_info("featured", "Upgraded Featured Image Extension");
			$config->set_int("featured_version", 1);
		}
		// Set default config:
		// [none for now]
	}

	public function onPageRequest($event) {
		global $config, $page, $user, $database;
		if($event->page_matches("featured_image")) {
			if($event->get_arg(0) == "set") {
				if($user->is_admin() && isset($_POST['image_id'])) {
					/* Zach: add this image to a table! */
					$yn = "N";
					if(isset($_POST['best'])) { $yn = "Y"; }
					$id = int_escape($_POST['image_id']);
					if($id > 0) {
						$exists = $database->get_row("SELECT * FROM featured WHERE feature_image_id = $id");
						if(!isset($exists)) {
							$database->execute("INSERT into featured (id, feature_date, feature_image_id, feature_best) VALUES (?,now(),?,?)",
									   array(NULL,$id, $yn));
						}
						$config->set_int("featured_id", $id);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/$id"));
					}
				}
			}
			if($event->get_arg(0) == "download") {
				$image = Image::by_id($config->get_int("featured_id"));
				if(!is_null($image)) {
					$page->set_mode("data");
					$page->set_type("image/jpeg");
					$page->set_data(file_get_contents($image->get_image_filename()));
				}
			}
			if($event->get_arg(0) == "view") {
				$image = Image::by_id($config->get_int("featured_id"));
				if(!is_null($image)) {
					send_event(new DisplayingImageEvent($image, $page));
				}
			}
		}
		if($event->page_matches("featured")) {
			if($event->get_arg(0) == "list") {
				if(!class_exists("SimpleBlog")) {
					global $page;
					$page->set_mode("data");
					$html = "Sorry... this page requires the simple_blog extension.";
					$page->set_data($html);
				} else {
					global $database;
					if(is_null($event->get_arg(1))||$event->get_arg(1)<=0) {
						$current_page = 1;
					} else {
						$current_page = $event->get_arg(1);
					}
					
					$posts_per_page = $config->get_int("blog_posts_per_page");
					$start = $posts_per_page * ($current_page - 1);
					
					$featured_index = $database->get_all("SELECT *
												FROM featured
												ORDER BY id DESC
												LIMIT ? OFFSET ?",
												array($posts_per_page, $start));
					
					$total_pages = ceil(($database->db->GetOne("SELECT COUNT(*) FROM featured") / $posts_per_page));

					// id, feature_date, feature_image_id, feature_best
					
					/*				 $post['id'],
									 $post['owner_id'],
									 $post['post_date'],
									 $post['post_title'],
									 $post['post_text']*/
					
					for($i = 0; $i < count($featured_index); $i++) {
						/**
						 * Going to try and shoehorn this into a blog.
						 **/
						$image = Image::by_id($featured_index[$i]["feature_image_id"]);
						$posts[$i]["id"] = $i;
						$posts[$i]["owner_id"] = $image->owner_id;
						$posts[$i]["post_date"] = $image->posted;
						$posts[$i]["post_title"] = "#".$image->id;
						$posts[$i]["post_text"] = "[image:".$image->id."]";
					}
					$blogtheme = new SimpleBlogTheme();
					$extension["name"] = "featured";
					$extension["title"] = "Featured Images";
					$extension["permalinks"] = false;
					$blogtheme->display_blog_index($posts, $current_page, $total_pages, $extension);
				}
			}
		}
		if($event->page_matches("helloworld")) {
			global $page;
			$page->set_mode("data");
			$image = Image::by_random();
			$abc = file_get_contents($image->get_image_filename());
			$html = "<img src='$abc' />";
			$page->set_data($html);
		}
	}
	
	public function onSearchTermParse($event) {
		$matches = array();
		if(preg_match("/featured_list/i", $event->term, $matches)) {
			global $database;
			$event->add_querylet(new Querylet("images.id IN (SELECT feature_image_id FROM featured ORDER BY feature_date DESC)"));
		}
	}

	public function onPostListBuilding($event) {
		global $config, $page, $user;
		$fid = $config->get_int("featured_id");
		if($fid > 0) {
			$image = Image::by_id($fid);
			if(!is_null($image)) {
				if(class_exists("Ratings")) {
					if(strpos(Ratings::get_user_privs($user), $image->rating) === FALSE) {
						return;
					}
				}
				$this->theme->display_featured($page, $image);
			}
		}
	}

	public function onImageAdminBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_part($this->theme->get_buttons_html($event->image->id));
		}
	}
}
?>
