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
    public function display_blog_index($posts) {
        global $page, $config;
        $sitename = $config->get_string('title');
        
        $page->set_title("$sitename Blog");
        $page->set_heading("$sitename Blog");
        $this->generate_blog_index($posts);
        $page->add_block(new Block("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0));
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
            <form action='".make_link("blog/add")."' method='POST'>
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

                <td><form name='remove$id' method='post' action='".make_link("blog/remove")."'>
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
    private function generate_blog_index($posts) {
        global $page;
        for ($i = 0 ; $i < count($posts) ; $i++)
        {
            /**
             * Add table rows
             */
            $id = $posts[$i]['id'];
            $post_author = User::by_id($posts[$i]['owner_id']);
            $post_date = $posts[$i]['post_date'];
            $clean_date = date("m/d/y", strtotime($post_date));
            $post_title = $posts[$i]['post_title'];
            
            $post_text = $posts[$i]['post_text'];

            $tfe = new TextFormattingEvent($post_text);
            send_event($tfe);
            $post_text = $tfe->formatted;

	    $post_text = str_replace('\n\r', '<br>', $post_text);
            $post_text = str_replace('\r\n', '<br>', $post_text);
            $post_text = str_replace('\n', '<br>', $post_text);
            $post_text = str_replace('\r', '<br>', $post_text);
	    $post_text = stripslashes($post_text);

            $body = "
                <h4>Written by {$post_author->name} on $clean_date</h4>
                $post_text
            ";
            $page->add_block(new Block($post_title, $body, "main", $i));
        }
    }
}
?>