<?php
class HomeTest extends ShimmieWebTestCase {
    function testHomePage() {
        $this->get_page('home');
        $this->assert_title('SCore');
        $this->assert_text('SCore');

		# FIXME: test search box
    }
}
?>
