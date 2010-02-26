<?php

class HomeTheme extends Themelet {
	public function display_page(Page $page, $sitename, $data_href, $theme_name, $body) {
		$page->set_mode("data");
		$page->set_data(<<<EOD
<html>
	<head>
		<title>$sitename</title>
		<link rel='stylesheet' href='$data_href/themes/$theme_name/style.css' type='text/css'>
	</head>
	<style>
		div#front-page h1 {font-size: 4em; margin-top: 2em; margin-bottom: 0px; text-align: center; border: none; background: none;}
		div#front-page {text-align:center;}
		.space {margin-bottom: 1em;}
		div#front-page div#links a {margin: 0 0.5em;}
		div#front-page li {list-style-type: none; margin: 0;}
	</style>
	<body>
		$body
	</body>
</html>
EOD
);
	}

	public function build_body($sitename, $main_links, $main_text, $contact_link) {
		$main_links_html = empty($main_links) ? "" : "<div class='space' id='links'>$main_links</div>";
		$message_html = empty($main_text)     ? "" : "<div class='space' id='message'>$main_text</div>";
		return "
		<div id='front-page'>
			<h1><a style='text-decoration: none;' href='".make_link()."'><span>$sitename</span></a></h1>
			$main_links_html
			$search_html
			$message_html
			<div class='space' id='foot'>
				<small><small>
				<a href='$contact_link'>Contact</a> &ndash;
				Running <a href='http://code.shishnet.org/shimmie2/'>SCore</a>
				</small></small>
			</div>
		</div>";
	}
}
?>
