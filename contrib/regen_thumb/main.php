<?php
/*
 * Name: Regen Thumb
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Regenerate a thumbnail image
 * Documentation:
 *  This adds a button in the image control section on an
 *  image's view page, which allows an admin to regenerate
 *  an image's thumbnail; useful for instance if the first
 *  attempt failed due to lack of memory, and memory has
 *  since been increased.
 */

class RegenThumb extends SimpleExtension {
	public function onInitExt($event) {
		global $config;
		$version = $config->get_int("pdef_regen_thumb", 0);
		if($version < 1) {
			PermissionManager::set_perm("admin","regen_thumbs",true);
			$config->set_int("pdef_regen_thumb", 1);
		}
	}

	public function onPermissionScan(Event $event) {
		$event->add_perm("regen_thumbs","Regenerate Thumbnails");
	}

	public function onPageRequest($event) {
		global $config, $database, $page, $user;

		if($event->page_matches("regen_thumb") && $user->can("regen_thumbs") && isset($_POST['image_id'])) {
			$image = Image::by_id(int_escape($_POST['image_id']));
			send_event(new ThumbnailGenerationEvent($image->hash, $image->ext));
			$this->theme->display_results($page, $image);
		}
	}

	public function onImageAdminBlockBuilding($event) {
		global $user;
		if($user->can("regen_thumbs")) {
			$event->add_part($this->theme->get_buttons_html($event->image->id));
		}
	}
}
?>
