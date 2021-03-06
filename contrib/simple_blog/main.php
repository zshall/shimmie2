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
//class SimpleBlogPermissions extends SimpleExtension {
///**
// * Zach: permissions system development.
// */
//	public function onPermissionScan(Event $event) {
//                $event->add_perm("view_blog","View blog");
//		$event->add_perm("manage_blog","Manage Blog");
//	}
//	public function onInitExt(Event $event) {
//		global $permissions, $config;
//		$version = $config->get_int("pdef_blog", 0);
//		 if($version < 2) {
//				PermissionManager::set_perm("admin","manage_blog",true);
//				PermissionManager::set_perm("admin","view_blog",true);
//                                PermissionManager::set_perm("user","view_blog",true);
//                                PermissionManager::set_perm("anonymous","view_blog",true);
//				$config->set_int("pdef_blog", 2);
//		}
//	}
//}

class SimpleBlogConfig extends SimpleExtension {
    /**
     * Don't know why, but I love keeping config seperate from the rest.
     */
    public function onSetupBuilding($event) {
        $sb = new SetupBlock("Simple Blog");
        $sb->add_text_option("blog_title", "Blog Title: <br>");
        // These settings inspired by Bzchan's fabulous /home/ extension. Thanks!
        $sb->add_longtext_option("blog_sidebar", '<br>Sidebar Links - Example: [/post/list|Posts]<br>');
        $sb->add_longtext_option("blog_header", "<br>Custom Header:<br>");
        $sb->add_int_option("blog_posts_per_page", "<br>Blog posts per page: <br>");
        $event->panel->add_block($sb);
    }
    public function onInitExt(Event $event) {
        global $config;
        $sitename = $config->get_string("title");
        $config->set_default_string("blog_title", "$sitename Blog");
        $config->set_default_string("blog_sidebar", "[/post/list|Posts]<br>[/comment/list|Comments]");
        $config->set_default_string("blog_header", "Featuring the latest updates and news from $sitename!");
        $config->set_default_int("blog_posts_per_page", 5);
    }
}

// Null Events:
class BlogBuildingEvent extends Event {}

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
                $event->add_link("Blog Manager", make_link("blog_manager"));
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
        if($event->page_matches("blog_manager")) {
            switch($event->get_arg(0)) {
                case "":		
                    /**
                     * Displays the blog manager.
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
                            $page->set_redirect(make_link("blog_manager"));
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
                            $database->Execute("DELETE FROM simple_blog WHERE id=?", array($id));
                            log_info("simple_blog", "Removed Post #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("blog_manager"));
                    }
                    break;
            }
        }
        if($event->page_matches("blog")) {
            global $database, $user, $config, $page;
            /**
             * Displays the blog
             */
            if(!$user->can("view_blog")) {
                $this->theme->display_permission_denied($page);
            } else {
                switch ($event->get_arg(0)) {
                    case "":
                    case "list":
                        /**
                         * Pagination always helps.
                         */
                        if(is_null($event->get_arg(1))||$event->get_arg(1)<=0) {
                            $current_page = 1;
                        } else {
                            $current_page = $event->get_arg(1);
                        }
                        
                        $posts_per_page = $config->get_int("blog_posts_per_page");
                        $start = $posts_per_page * ($current_page - 1);
                        
                        $posts = $database->get_all("SELECT *
                                                    FROM simple_blog
                                                    ORDER BY id DESC
                                                    LIMIT ? OFFSET ?",
                                                    array($posts_per_page, $start));
                        
                        $total_pages = ceil(($database->db->GetOne("SELECT COUNT(*) FROM simple_blog") / $posts_per_page));
            			$extension["name"] = "blog";
						$extension["title"] = $title = $config->get_string('blog_title');
						$extension["permalinks"] = true;
                        $this->theme->display_blog_index($posts, $current_page, $total_pages, $extension);
                        break;
                    case "view":
                        global $database, $config;
                        if(is_null($event->get_arg(1))||$event->get_arg(1)<=0) {
                            $current_post = 1;
                        } else {
                            $current_post = $event->get_arg(1);
                        }
                        
                        $post = $database->get_row("SELECT *
                                                   FROM simple_blog
                                                   ORDER BY id DESC");
                        $extension["name"] = "blog";
						$extension["title"] = $title = $config->get_string('blog_title');
						$extension["permalinks"] = false;
                        $this->theme->display_blog_post($post, $extension);
                        break;
                }
            }
        }
    }
}

class SimpleBlogRSS extends SimpleExtension {
    /**
     * Adapted from Shish's Image RSS extension.
     */
	public function onBlogBuilding($event) {
		global $config, $page;
		$title = $config->get_string('blog_title');
		$page->add_header("<link id=\"posts\" rel=\"alternate\" type=\"application/rss+xml\" ".
                    "title=\"$title\" href=\"".make_link("rss/blog/1")."\" />");
	}

	public function onPageRequest($event) {
            global $config, $database, $user;
            if($event->page_matches("rss/blog")) {
                if($user->can("view_blog")) {
                    if(is_null($event->get_arg(0))||$event->get_arg(0)<=0) {
                        $current_page = 1;
                    } else {
                        $current_page = $event->get_arg(0);
                    }
                    
                    $posts_per_page = $config->get_int("blog_posts_per_page");
                    $start = $posts_per_page * ($current_page - 1);
                    
                    $posts = $database->get_all("SELECT *
                                                FROM simple_blog
                                                ORDER BY id DESC
                                                LIMIT ? OFFSET ?",
                                                array($posts_per_page, $start));
                    
                    $this->do_rss($posts, $current_page);
                }
            }
	}


	private function do_rss($posts, $page_number) {
	    global $page;
            global $config;
            $page->set_mode("data");
            $page->set_type("application/rss+xml");

            $data = "";
            for ($i = 0 ; $i < count($posts) ; $i++)
            {

                    $link = make_http(make_link("blog/view/{$posts[$i]['id']}"));
                    $owner = User::by_id($posts[$i]['owner_id']);
                    $posted = date(DATE_RSS, strtotime($posts[$i]['post_date']));
                    $content = html_escape(
                            "<p>" . $posts[$i]['post_text'] . "</p>" .
                            "<p>Posted by " . $owner->name . "</p>"
                    );

                    $data .= "
            <item>
                    <title>{$posts[$i]['post_title']}</title>
                    <link>$link</link>
                    <guid isPermaLink=\"true\">$link</guid>
                    <pubDate>$posted</pubDate>
                    <description>$content</description>
            </item>
                    ";
            }

            $title = $config->get_string('title');
            $base_href = make_http($config->get_string('base_href'));

            if($page_number > 1) {
                    $prev_url = make_link("rss/blog/".($page_number-1));
                    $prev_link = "<atom:link rel=\"previous\" href=\"$prev_url\" />";
            }
            else {
                    $prev_link = "";
            }
            $next_url = make_link("rss/blog/".($page_number+1));
            $next_link = "<atom:link rel=\"next\" href=\"$next_url\" />"; // no end...

            $version = VERSION;
            $xml = "<"."?xml version=\"1.0\" encoding=\"utf-8\" ?".">
<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
    <channel>
        <title>$title</title>
        <description>Latest Blog Posts</description>
		<link>$base_href</link>
		<generator>Shimmie-$version</generator>
		$prev_link
		$next_link
		$data
	</channel>
</rss>";
		$page->set_data($xml);
	}
}
?>