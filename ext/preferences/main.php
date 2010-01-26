<?php
/*
 * Name: User Preferences
 * Author: Zach Hall <zach@sosguy.net>
 * Visibility: admin
 * Description: Allows the user to configure user-configurable settings to his or her taste.
 *				Cheap renaming hack of Shish's setup extension!!
 */

/* PrefSaveEvent {{{
 *
 * Sent when the setup screen's 'set' button has been
 * activated; new config options are in $_POST
 */
class PrefSaveEvent extends Event {
	var $userprefs;

	public function PrefSaveEvent($userprefs) {
		$this->userprefs = $userprefs;
	}
}
// }}}
/* SetupBuildingEvent {{{
 *
 * Sent when the setup page is ready to be added to
 */
class PrefBuildingEvent extends Event {
	var $panel;

	public function PrefBuildingEvent($panel) {
		$this->panel = $panel;
	}

	public function get_panel() {
		return $this->panel;
	}
}
// }}}
/* SetupPanel {{{
 *
 */
class PrefPanel {
	var $blocks = array();

	public function add_block($block) {
		$this->blocks[] = $block;
	}
}
// }}}
/* SetupBlock {{{
 *
 */
class PrefBlock extends Block {
	var $header;
	var $body;

	public function PrefBlock($title) {
		$this->header = $title;
		$this->section = "main";
		$this->position = 50;
	}

	public function add_label($text) {
		$this->body .= $text;
	}

	public function add_text_option($name, $label=null) {
		global $userprefs;
		$val = html_escape($userprefs->get_string($name));
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='text' id='$name' name='_userprefs_$name' value='$val'>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";
	}

	public function add_longtext_option($name, $label=null) {
		global $userprefs;
		$val = html_escape($userprefs->get_string($name));
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<textarea rows='5' id='$name' name='_userprefs_$name'>$val</textarea>\n";
		$this->body .= "<!--<br><br><br><br>-->\n"; // setup page auto-layout counts <br> tags
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";
	}

	public function add_bool_option($name, $label=null) {
		global $userprefs;
		$checked = $userprefs->get_bool($name) ? " checked" : "";
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='checkbox' id='$name' name='_userprefs_$name'$checked>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='bool'>\n";
	}

//	public function add_hidden_option($name, $label=null) {
//		global $userprefs;
//		$val = $userprefs->get_string($name);
//		$this->body .= "<input type='hidden' id='$name' name='$name' value='$val'>";
//	}

	public function add_int_option($name, $label=null) {
		global $userprefs;
		$val = html_escape($userprefs->get_string($name));
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='text' id='$name' name='_userprefs_$name' value='$val' size='4' style='text-align: center;'>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='int'>\n";
	}

	public function add_shorthand_int_option($name, $label=null) {
		global $userprefs;
		$val = to_shorthand_int($userprefs->get_string($name));
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='text' id='$name' name='_userprefs_$name' value='$val' size='6' style='text-align: center;'>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='int'>\n";
	}

	public function add_choice_option($name, $options, $label=null) {
		global $userprefs;
		$current = $userprefs->get_string($name);

		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$html = "<select id='$name' name='_userprefs_$name'>";
		foreach($options as $optname => $optval) {
			if($optval == $current) $selected=" selected";
			else $selected="";
			$html .= "<option value='$optval'$selected>$optname</option>\n";
		}
		$html .= "</select>";
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";

		$this->body .= $html;
	}

	public function add_multichoice_option($name, $options, $label=null) {
		global $userprefs;
		$current = $userprefs->get_array($name);

		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$html = "<select id='$name' name='_userprefs_{$name}[]' multiple size='5'>";
		foreach($options as $optname => $optval) {
			if(in_array($optval, $current)) $selected=" selected";
			else $selected="";
			$html .= "<option value='$optval'$selected>$optname</option>\n";
		}
		$html .= "</select>";
		$this->body .= "<input type='hidden' name='_type_$name' value='array'>\n";
		$this->body .= "<!--<br><br><br><br>-->\n"; // setup page auto-layout counts <br> tags

		$this->body .= $html;
	}
}
// }}}

class UserPrefsSetup extends SimpleExtension {
	public function onInitExt($event) {
		global $userprefs;
		$userprefs->set_default_string("test_data", "Input something here");
		$userprefs->set_default_string("test_data2", "And here");
		$userprefs->set_default_bool("test_data3", true);
	}
	

	public function onPageRequest($event) {
		global $userprefs, $page, $user;
		
		if($event->page_matches("preferences")) { //TODO: let admins set anonymous preferences.
			if($user->is_anonymous()) {
				$this->theme->display_permission_denied($page);
			} else {
				if($event->get_arg(0) == "save") {
					send_event(new PrefSaveEvent($userprefs));
					$userprefs->save_prefs();

					$page->set_mode("redirect");
					$page->set_redirect(make_link("preferences"));
				}
				else if($event->get_arg(0) == "advanced") {
					$this->theme->display_advanced($page, $userprefs->values);
				}
				else {
					$panel = new PrefPanel();
					send_event(new PrefBuildingEvent($panel));
					$this->theme->display_page($page, $panel);
				}
			}
		}
	}

	public function onPrefBuilding($event) {
		global $config, $user, $userprefs;
		$theme_name = $config->get_string('theme');
		$styles = array();
		foreach(glob("themes/$theme_name/*.css") as $style_name) {
			$name = str_replace("themes/$theme_name/", "", $style_name);
			$human = str_replace("_", " ", $name);
			$human = ucwords($human);
			$themes[$human] = $name;
		}

		$sb = new PrefBlock("Extension testing block");
		$sb->position = 0;
		$sb->add_text_option("test_data", "Data1: ");
		$sb->add_text_option("test_data2", "Data2: ");
		$sb->add_bool_option("test_data3", "Data3: ");
		$event->panel->add_block($sb);
	}

	public function onPrefSave($event) {
		global $userprefs;
		global $user;
		$userid = $user->id;
		foreach($_POST as $_name => $junk) {
			if(substr($_name, 0, 6) == "_type_") {
				$name = substr($_name, 6);
				$type = $_POST["_type_$name"];
				$value = isset($_POST["_userprefs_$name"]) ? $_POST["_userprefs_$name"] : null;
				switch($type) {
					case "string": 
						$tfe = new TextFormattingEvent($value);
						send_event($tfe);
						$value = $tfe->formatted;
						
						$value = str_replace('\n\r', '<br>', $value);
						$value = str_replace('\r\n', '<br>', $value);
						$value = str_replace('\n', '<br>', $value);
						$value = str_replace('\r', '<br>', $value);
						
						$value = stripslashes($value);
						$userprefs->set_string($name, $value); 
						break;
					case "int":    $userprefs->set_int($name, $value);    break;
					case "bool":   $userprefs->set_bool($name, $value);   break;
					case "array":  $userprefs->set_array($name, $value);  break;
				}
			}
		}
		log_warning("userprefs", "Preferences saved for user #$userid");
	}

	public function onUserBlockBuilding($event) {
		global $user;
			$event->add_link("Preferences", make_link("preferences"));
	}
}
?>
