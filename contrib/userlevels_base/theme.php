<?
class UserLevelExperienceTheme extends Themelet {
	public function display_exp_bar($userid) {
		/**
		 * This page will get the current user's exp and display it to him/her.
		 * Include the jQueryUI and related theme files.
		 */
		global $database;
		// Get variables
		$ulprefs = new DatabasePrefs($database, $userid);
		$up = $ulprefs->get_int("user_level_c_total",0);
		$ul = $ulprefs->get_int("user_level_l",0);
		$pl = $ulprefs->get_int("user_level_c_remain",0);
		$pp = $ulprefs->get_int("user_level_p_percent",0);
	
		$html = '<script type="text/javascript" src="js/jquery.progressbar.min.js"></script>';
		$html .='<script type="text/javascript">
					$(document).ready(function() {
						$("#pbl").progressBar({
								steps: 20,
								showText: false
						});
					});
				</script>
				<span class="progressBar" id="pbl" title="Current EXP: '.$up.'. EXP required for level '.{$ul+1}.': '.$pl.'">'.$pp.'</span>';

		$html .= "<b>User's level: $ul</b><br />
			  
			  <br />Experience: $up<br />

			  Points required until next level-up: $pl<br />";
		
		return $html;
		
	}
}
?>