<?php

class ThemeChooserTheme extends Themelet {	
	public function display_page($page, $body) {
		$page->set_mode("data");
		$page->set_data(<<<EOD
<html>
	<head>
		<title>Theme Chooser Stats</title>
	</head>
	<body>
		$body
	</body>
</html>
EOD
);
	}
}
?>