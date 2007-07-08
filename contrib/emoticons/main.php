<?php

class Emoticons extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'TextFormattingEvent')) {
			$event->formatted = $this->bbcode_to_html($event->formatted);
			$event->stripped  = $this->bbcode_to_text($event->stripped);
		}
	}
	
	private function bbcode_to_html($text) {
		global $config;
		$data_href = $config->get_string("data_href");
		$text = preg_replace("/:([a-z]*?):/s", "<img src='$data_href/ext/emoticons/default/\\1.gif'>", $text);
		return $text;
	}

	private function bbcode_to_text($text) {
		$text = preg_replace("/:([a-z]*?):/s", "\\1", $text);
		return $text;
	}
}
add_event_listener(new Emoticons());
?>
