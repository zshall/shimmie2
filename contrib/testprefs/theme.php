<?php

class TestPrefsTheme extends Themelet {
	// Showing the greeting on the page.
	public function greeting($page, $text) {
		$page->add_block(new Block("Greeting", $text, "left", 5));
	}
}
?>