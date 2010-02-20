<?php
/*
 * Name: Simple Blog
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com/]
 * License: GPLv2
 * Description: Simple, extendable blogging system
 *		Under development.
 *
 *		Prereqs: permissions system
 *
 *		Development: http://github.com/zshall/shimmie2/
 */
class SimpleBlogPermissions extends SimpleExtension {
/**
 * Zach: permissions system development.
 */
	public function onPermissionScan(Event $event) {
		global $permissions;
                $permissions->add_perm("view_blog","View blog");
		$permissions->add_perm("manage_blog","Manage Blog");
	}
	public function onInitExt(Event $event) {
		global $permissions, $config;
		$version = $config->get_int("pdef_blog", 0);
		 if($version < 1) {
				$permissions->set_perm("admin","manage_blog",true);
				$permissions->set_perm("admin","view_blog",true);
                                $permissions->set_perm("user","view_blog",true);
                                $permissions->set_perm("anonymous","view_blog",true);
				$config->set_int("pdef_blog", 1);
		}
	}
}

class SimpleBlog extends SimpleExtension {
    /**
     * My simple blog extends a simple extension... how fitting.
     */
    public function onInitExt() {
        /**
         * First, the tables. (Look familiar?)
         */
        global $config;
        $version = $config->get_int("blog_version", 0);
        /**
         * If this version is less than "1", it's time to install.
         *
         * REMINDER: If I change the database tables, I must change up version by 1.
         *
         * Goals:
         * Version 1 - Author, date, title, text.
         * Version 2 - Author, date, title, text, category. (Will have a category editor by then.)
         */
         if($version < 1) {
            /**
            * Installer
            */
            global $database, $user;
            $database->create_table("simple_blog",
                     "id SCORE_AIPK
                     , owner_id INTEGER NOT NULL
                     , post_date SCORE_DATETIME DEFAULT SCORE_NOW
                     , post_title VARCHAR(128) UNIQUE NOT NULL
                     , post_text TEXT
            ");
            // Insert sample data:
            $database->Execute("INSERT INTO simple_blog(id, owner_id, post_date, post_title, post_text) VALUES(?, ?, now(), ?, ?)",
                               array(NULL, 2, "First Post!", "You have successfully installed the Simple Blog extension!"));
            
            $config->set_int("blog_version", 1);
            log_info("simple_blog","Installed Simple Blog Tables (version 1)");
        }
    }
    public function onUserBlockBuilding(Event $event) {
        global $user;
        if($user->is_admin()) {
                $event->add_link("Blog Manager", make_link("blog/editor"));
        }
    }
    public function addPost($userid, $title, $text) {
        /**
         * Add Post function... can be used by other extensions.
         */
        global $database;
        $database->execute("INSERT INTO simple_blog (id, owner_id, post_date, post_title, post_text) VALUES (?, ?, now(), ?, ?)", 
                        array(NULL, $userid, $title, $text));
        log_info("simple_blog", "Added Message: $title");
    }
    public function onPageRequest(Event $event) {
        if($event->page_matches("blog")) {
            switch($event->get_arg(0)) {
                case "editor":		
                    /**
                     * Displays the blog editor.
                     */
                    global $page, $database, $user;
                    if(!$user->can("manage_blog")) {
                            $this->theme->display_permission_denied($page);
                    } else {
                            $entries = $database->get_all("SELECT * FROM simple_blog ORDER BY id DESC");
                            $this->theme->display_editor($entries);
                    }
                    break;
                case "add":
                    /**
                     * Adds an entry
                     */
                    global $page, $database, $user;
                    if(!$user->can("manage_blog")) {
                            $this->theme->display_permission_denied($page);
                    } else {
                            $post_title = $_POST['post_title'];
                            $post_text = $_POST['post_text'];
                            if($post_title == "") { die("No post title!"); }
                            if($post_text == "") { die("No post message!"); }
                            // Now insert into db:
                            $this->addPost($user->id, $post_title, $post_text);
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("blog/editor"));
                    }
                    break;
                case "remove":
                    /**
                     * Removes an entry
                     */
                    global $page, $database, $user;
                    if(!$user->can("manage_blog")) {
                            $this->theme->display_permission_denied($page);
                    } else {
                            $id = int_escape($_POST['id']);
                            if(!isset($id)) { die("No ID!"); }
                            $database->Execute("DELETE FROM blotter WHERE id=?", array($id));
                            log_info("simple_blog", "Removed Entry #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("blog/editor"));
                    }
                    break;
                case "":
                    /**
                     * Displays the blog
                     */
                    global $database, $user;
                    if(!$user->can("view_blog")) {
                        $this->theme->display_permisssion_denied($page);
                    } else {
                        $posts = $database->get_all("SELECT * FROM simple_blog ORDER BY id DESC");
                        $this->theme->display_blog_index($posts);
                    }
                    break;
            }
        }
    }
}
?>