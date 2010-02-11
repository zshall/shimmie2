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
	
		$html = '<link type="text/css" href="/contrib/userlevels_base/css/custom-theme/jquery-ui-1.7.2.custom.css" rel="stylesheet" /> 
		<script type="text/javascript" src="/contrib/userlevels_base/js/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="/contrib/userlevels_base/js/jquery-ui-1.7.2.custom.min.js"></script>';
		$html .=   '<script type="text/javascript"> 
				$(function() {
					$("#progressbar'.$up.'").progressbar({
						value: '.$pp.'	});
				});
				</script>
				<div align="center"><div id="progressbar'.$up.'" title="'.$pp.'%" style="width:40%; height: 20px;"></div></div>';

		$html .= "<b>User's level: $ul</b><br />
			  
			  <br />Experience: $up<br />

			  Points required until next level-up: $pl<br />";
		
		return $html;
		
	}
}
?>