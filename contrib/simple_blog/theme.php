<?php
class SimpleBlogTheme extends Themelet {
    public function display_editor($posts) {
        global $page;
        $html = $this->get_html_for_blog_editor($posts);
        $page->set_title("Blog Manager");
        $page->set_heading("Blog Manager");
        $page->add_block(new Block("Welcome to the Blog Manager!", $html, "main", 10));
        $page->add_block(new Block("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0));
    }
    public function display_blog_index($posts, $page_number, $total_pages, $extension) {
        global $page, $config;
		$ename = $extension["name"];
        /**
         * Pagination:
         */
        $prev = $page_number - 1;
		$next = $page_number + 1;
        
        $h_prev = ($page_number <= 1) ? "Prev" :
                "<a href='".make_link("$ename/$prev")."'>Prev</a>";
        $h_index = "<a href='".make_link("$ename/list")."'>Index</a>";
        $h_home = "<a href='".make_link()."'>&#171; Home</a>";
        $h_next = ($page_number >= $total_pages) ? "Next" :
                "<a href='".make_link("$ename/$next")."'>Next</a>";

		$nav = "$h_prev | $h_index | $h_next<br />$h_home";
        /**
         * Displaying the blog:
         */
        
        $title = $extension["title"];
        
        $page->set_title($title);
        $page->set_heading($title);
        $this->generate_blog_header($nav);
        $this->generate_blog_index($posts, $extension);
        $this->display_paginator($page, "$ename/list", null, $page_number, $total_pages);
        send_event(new BlogBuildingEvent()); // make it extendable.
    }
    public function display_blog_post($post, $extension) {
        global $page, $config;
        /**
         * Displaying the blog:
         */
        $ename = $extension["name"];
		$title = $extension["title"];
        
        $page->set_title($title);
        $page->set_heading($title);
        $this->generate_blog_header("<a href='".make_link("$ename/list")."'>Index</a><br /><a href='".make_link()."'>&#171; Home</a>");
        
        $this->generate_blog_post($post['id'],
                                 $post['owner_id'],
                                 $post['post_date'],
                                 $post['post_title'],
                                 $post['post_text'],
                                 0, $extension);
        
        send_event(new BlogBuildingEvent()); // make it extendable.
    }
    private function is_odd($number) {
            return $number & 1; // 0 = even, 1 = odd
    }
    private function get_html_for_blog_editor($posts) {
        /**
         * Long function name, but at least I won't confuse it with something else ^_^
         */

        $html = "";
        $table_header =  "
            <tr>
            <th>Author</th>
            <th>Date</th>
            <th>Title</th>
            <th>Action</th>
            </tr>";
        $add_new = "
            <form action='".make_link("blog_manager/add")."' method='POST'>
            <table class='zebra'>
            <tr class='odd'><td style='width: 30px;'>Title</td><td><input type='text' name='post_title' maxlength='120' /></td></tr>
            <tr class='even'>
            <td colspan='2'><textarea style='text-align:left;' name='post_text' rows='5' /></textarea></td>
            </tr><tr class='odd'>
            <td><input type='submit' value='Add'></td>
            </tr>
            </table>
            </form>";

        // Posts list
        $table_rows = "";
        for ($i = 0 ; $i < count($posts) ; $i++)
        {
            /**
             * Add table rows
             */
            $id = $posts[$i]['id'];
            $post_author = User::by_id($posts[$i]['owner_id']);
            $post_date = $posts[$i]['post_date'];
            $post_title = $posts[$i]['post_title'];

            if(!$this->is_odd($i)) {$tr_class = "odd";}
            if($this->is_odd($i)) {$tr_class = "even";}
            // Add the new table row(s)
            $table_rows .=
                "<tr class='{$tr_class}'>
                <td>{$post_author->name}</td>
                <td>$post_date</td>
                <td>$post_title</td>

                <td><form name='remove$id' method='post' action='".make_link("blog_manager/remove")."'>
                <input type='hidden' name='id' value='$id' />
                <input type='submit' style='width: 100%;' value='Remove' />
                </form>
                </td>
                </tr>";
        }
        $html = "
                <table id='blog_entries' class='zebra'>
                <thead>$table_header</thead>
                <tbody>$table_rows</tbody>
                </table>
                <br />

                $add_new

                <br />
                <b>Help:</b><br />
                <blockquote>Add and remove blog entries on this page!</blockquote>";

        return $html;
    }
    private function generate_blog_post($pi, $pa, $pd, $ph, $pb, $i, $e) {
            global $page, $config;
            $id = $pi;
            $post_author = User::by_id($pa);
            $post_date = $pd;
            $clean_date = date("m/d/y", strtotime($post_date));
            $post_title = $ph;
            
            $post_text = $pb;
            
            $tfe = new TextFormattingEvent($post_text);
            send_event($tfe);
            $post_text = $tfe->formatted;
            
	    	$post_text = str_replace('\n\r', '<br>', $post_text);
            $post_text = str_replace('\r\n', '<br>', $post_text);
            $post_text = str_replace('\n', '<br>', $post_text);
            $post_text = str_replace('\r', '<br>', $post_text);
	    	$post_text = stripslashes($post_text);
            
            $pattern = '/\[([^:]+):([^\]]+)\]/';
            preg_match_all($pattern, $post_text, $matches);
            
            /**
             * We now have a $matches array:
             * [0][x] - what to find / replace ([image:25])
             * [1][x] - the command portion (image)
             * [2][x] - the parameter portion (25)
             */
            
            for ($j = 0; $j < count($matches); $j++) {
                if(isset($matches[0][$j])) {
                    switch ($matches[1][$j]) {
                        case "image":
                            if(isset($matches[2][$j])) {
                                $origtxt = html_escape($matches[0][$j]);
                                $imageid = int_escape($matches[2][$j]);
                                $image = Image::by_id($imageid);
                                $src = $image->get_image_link();
                                switch($image->ext) {
                                    case "jpg":
                                    case "gif":
                                    case "png":
                                        $to_replace = "<a href='".make_link("post/view/{$image->id}")."'><img id='main-image' src='{$src}' style='max-width:100%;' /></a>";
                                        $post_text = str_replace($origtxt, $to_replace, $post_text);
                                        break;
                                }
                            }
                            break;
                        case "comment":
                            break;
                    }
                }
            }
           //-----------------------------------------------------------------------------------------------------------------------------------------------------------
           if($e["permalinks"] == true) {$permalink = "<a href='".make_link("{$e['name']}/view/$id")."'>#</a> ";} else {$permalink = "";}
		   $body = "<div class='blog-body'>
                <span class='blog-header'>".$permalink."By {$post_author->name} on $clean_date<br /><br /></span>
                $post_text
                </div>
            ";
            
            $page->add_block(new Block($post_title, $body, "main", ($i+5)));
    }
    private function generate_blog_index($posts, $extension) {
        global $page, $config;
        
        for ($i = 0 ; $i < count($posts) ; $i++)
        {
            /**
             * Show posts
             */
            $this->generate_blog_post($posts[$i]['id'],
                                     $posts[$i]['owner_id'],
                                     $posts[$i]['post_date'],
                                     $posts[$i]['post_title'],
                                     $posts[$i]['post_text'],
                                     $i, $extension);
        }
        $page->add_block(new Block(NULL,$config->get_string("blog_header"), "main", 0));
    }
    private function generate_blog_header($nav=null) {
        global $page, $config;
        $page->add_header("<style type'text/css'>
                        .blog-header {
                            font-size:80%;
                        }
                        .blog-body {
                            text-align:left;
                        }</style>");
        
        $base_href = $config->get_string('base_href');
        $sidebar_links = $config->get_string('blog_sidebar');
        $sidebar_links = str_replace('$base',	$base_href, 	$sidebar_links);
        $sidebar_links = preg_replace('#\[(.*?)\|(.*?)\]#', "<a href='\\1'>\\2</a>", $sidebar_links);
        $sidebar_links = str_replace('//',	"/", $sidebar_links);
        $page->add_block(new Block("Navigation", $nav . "<br />". $sidebar_links, "left", 10));
    }
}
?>