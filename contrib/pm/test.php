<?php
class PrivMsgTest extends SCoreWebTestCase {
	function testPM() {
		$this->log_in_as_admin();
		$this->get_page("user/test");
		$this->set_field('subject', "message demo to test");
		$this->set_field('message', "message contents");
		$this->click("Send");
		$this->log_out();

		$this->log_in_as_user();
		$this->get_page("user");
		$this->assert_text("message demo to test");
		$this->click("message demo to test");
		$this->assert_text("message contents");
		$this->back();
		$this->click("Delete");
		$this->assert_no_text("message demo to test");
		$this->log_out();
	}

	function testAdminAccess() {
		$this->log_in_as_admin();
		$this->get_page("user/test");
		$this->set_field('subject', "message demo to test");
		$this->set_field('message', "message contents");
		$this->click("Send");

		$this->get_page("user/test");
		$this->assert_text("message demo to test");
		$this->click("message demo to test");
		$this->assert_text("message contents");
		$this->back();
		$this->click("Delete");

		# simpletest bug? - redirect(referrer) works in opera, not in
		# webtestcase, so we end up at the wrong page...
		$this->get_page("user/test");
		$this->assert_title("test's Page");
		$this->assert_no_text("message demo to test");
		$this->log_out();
	}
}
?>
