<?
class GroupEditorTheme extends Themelet {
	public function display_editor($permissions, $groups) {
		global $page;
		$html = $this->get_html_for_groups_editor($permissions, $groups);
		$page->set_title("Groups Editor");
		$page->set_heading("Groups Editor");
                $page->add_block(new Block("Welcome to the Groups Editor!", $html, "main", 10));
	}
	
	private function is_odd($number) {
	   return $number & 1; // 0 = even, 1 = odd
	}
	
	private function get_html_for_groups_editor($permissions, $groups) {
		/**
		 * Long function name, but at least I won't confuse it with something else ^_^
		 *
		 * Editing this a bit to use a new table for each group. I think I see what Shish means
		 * when he says that I need a "permission_exists" thing.
		 */

		$html = "";
			// Add_new stuff goes here.
			$add_new_header = "<tr>
			<td colspan='4'>Group Name: <input style='text-align:center;' type='text' name='group_name' /></td>
			</tr>";
			$add_new = "";
			$oetd = "";
			// Put all custom permissions here
			for ($i = 0 ; $i < count($permissions) ; $i++)
			{				
				// Show two permissions per TR:
				$tr_top = "";
				$tr_bot = "";
				$td_class = "";
				if(!$this->is_odd($i)) {$tr_top = "<tr>";}
				if($this->is_odd($i)) {$tr_bot = "</tr>"; $oetd = $oetd + 1;}
				if($this->is_odd($oetd)) { $td_class = "odd"; } else { $td_class = "even"; }
				// Add the new table row(s)
				$add_new .= "
						{$tr_top}
						<td class='{$td_class}'>{$permissions[$i]['perm_desc']}</td>
						<td class='{$td_class}'><input type='checkbox' name='{$permissions[$i]['perm_name']}' /></td>
						{$tr_bot}";
			}
			// Finish the table generation.

			$add_new_footer = "<input type='submit' class='group_edit_submit' value='Add'>";


		// Now, time for group tables.
		$group_list = "";
        for ($i = 0 ; $i < count($groups) ; $i++)
        {
		/**
		 * Add table rows
		 */
			$id = $groups[$i]['id'];
			$group_name = $groups[$i]['group_name'];
			
			// Start the table
			$group_list .= "<table id='$group_name' class='zebra'><thead>";
			// Now add the group name
			$group_list	.=  "<tr><td colspan='4'><span style='font-size:110%; font-weight:bold;'>$group_name</span></td></tr></thead><tbody>
			<form action='".make_link("groups/edit")."' method='POST'>";
							
			/**
			 * How do we figure out if all these permissions are set?
			 * Explode... something.
			 */
			$gp = explode(",", $groups[$i]['group_permissions']);
			
			$oetd = "";
			
			for ($j = 0 ; $j < count($permissions) ; $j++)
			{
				/**
				 * Remember: $permissions[$i]['perm_name']
				 * Also remember: isset()? "Y" : "N" ;
				 */
				
				// Reset vars:
				$yn = "";
				$tr_top = "";
				$tr_bot = "";
				$td_class = "";
				
				for ($k = 0 ; $k < count($permissions) ; $k++) {
					if(isset($gp[$k])) {
						if($gp[$k]==$permissions[$j]['perm_name']) { 
							$yn = "checked";
						}
					}
				}
				
				if(!$this->is_odd($j)) {$tr_top = "<tr>";}
				if($this->is_odd($j)) {$tr_bot = "</tr>"; $oetd = $oetd + 1;}
				if($this->is_odd($oetd)) { $td_class = "odd"; } else { $td_class = "even"; }
				// Add the new table row(s)
				$group_list .= 
					"{$tr_top}
						<td class='{$td_class}' width='45%'>{$permissions[$j]['perm_desc']}</td>
						<td class='{$td_class}' width='5%'><input type='checkbox' name='{$permissions[$j]['perm_name']}' $yn /></td>
					{$tr_bot}";
			}
			$group_list .= "</tbody><tfoot><tr>";
			$group_list .= "<td colspan='2'><input type='submit' value='Edit'></td></form>";
			
			
			if($group_name != "anonymous" && $group_name != "user" && $group_name != "admin") {
				$group_list  .=		"<td colspan='2'>
									<form action='".make_link("groups/remove")."' method='POST'>
										<input type='hidden' name='id' value='$id'>
										<input type='hidden' name='group_name' value='$group_name'>
										<input type='submit' style='width:100%;' value='Remove'>
									</form></td>";
			} else { $group_list .= "<td colspan='2'><i>System Required Group</i></td>"; }
			$group_list .= "</tr></tfoot></table><br />";
			
		}

		$html = "<b>Current Groups:</b>
			$group_list
		
			<br /><b>Add New Group:</b><br />
			<form action='".make_link("groups/add")."' method='POST'>
			<table id='add_new' class='zebra'>
				<thead>$add_new_header</thead>
				<tbody>$add_new</tbody>
				<tfoot><tr><td colspan='4'>$add_new_footer</td></tr></tfoot>
			</table>
			
			</form>
			
			
			
			<br />
			<b>Help:</b><br />
			<blockquote>Set <i>group permissions</i> for each group. You can assign members to these groups... later.</blockquote>";
		
		return $html;
	}
}
?>