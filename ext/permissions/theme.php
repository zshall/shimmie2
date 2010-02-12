<?
class GroupEditorTheme extends Themelet {
	public function display_editor($permissions, $groups) {
		global $page;
		$html = $this->get_html_for_groups_editor($permissions, $groups);
		$page->set_title("Groups Editor");
		$page->set_heading("Groups Editor");
                $page->add_block(new Block("Welcome to the Groups Editor!", $html, "main", 10));
	}
	
	private function get_html_for_groups_editor($permissions, $groups) {
		/**
		 * Long function name, but at least I won't confuse it with something else ^_^
		 *
		 * I'm not great with interfaces unless I'm working in Dreamweaver (drat) so I'll have to copy from the alias editor.
		 * I'll also have to copy from myself, as this is a direct copy of the user level editor almost.
		 */

		$html = "";
			$table_header =  "<tr>
						<th>Group Name</th>";
			$add_new = "
				<tr>
					<form action='".make_link("groups/add")."' method='POST'>
						<td><input style='text-align:center;' type='text' name='group_name' /></td>
						";
			// Put all custom permissions here
			for ($i = 0 ; $i < count($permissions) ; $i++)
			{
				/**
				 * Remember: $permissions[$i]['name']
				 * Also remember: isset()? "Y" : "N" ;
				 */
				$table_header .= "<th>{$permissions[$i]['perm_desc']}</th>";
				$add_new .= "<td><input type='checkbox' name='{$permissions[$i]['perm_name']}' /></td>";
			}
			// Finish the table generation.
			$table_header .= "<th>Action</th>
							 </tr>";
			$add_new .= "<td><input type='submit' value='Add'></td>
						</form>
						</tr>";
		// Now, time for table rows.
		$table_rows = "";
        for ($i = 0 ; $i < count($groups) ; $i++)
        {
		/**
		 * Add table rows
		 */
			$id = $groups[$i]['id'];
			$group_name = $groups[$i]['group_name'];
			
			$table_rows	 .=  "<tr>
							<td>$group_name</td>";
							
			/**
			 * How do we figure out if all these permissions are set?
			 * Explode... something.
			 */
			$gp = explode(",", $groups[$i]['group_permissions']);
			//echo count($gp);
			for ($j = 0 ; $j < count($permissions) ; $j++)
			{
				/**
				 * Remember: $permissions[$i]['perm_name']
				 * Also remember: isset()? "Y" : "N" ;
				 */
			
			$yn = "N";
			
			for ($k = 0 ; $k < count($permissions) ; $k++) 
			{
				if(isset($gp[$k])) {
				
				if($gp[$k]==$permissions[$j]['perm_name']) { 
				
				$yn = "Y";}
				}
			}
			if($yn == "Y") { 
			$table_rows .= "<td>Y</td>";
			} else { $yn = "N"; $table_rows .= "<td>N</td>"; }
			
			}

			
							
			$table_rows  .=		"<td>
								<form action='".make_link("groups/remove")."' method='POST'>
									<input type='hidden' name='id' value='$id'>
									<input type='hidden' name='group_name' value='$group_name'>
									<input type='submit' value='Remove'>
								</form>
							</td>
							</tr>";
		}

		$html = "
			<table id='user_levels' class='zebra'>
				<thead>$table_header</thead>
				<tbody>$table_rows</tbody>
				<tfoot>$add_new</tfoot>
			</table>
			
			<br />
			<b>Help:</b><br />
			<blockquote>Set <i>group permissions</i> for each group. You can assign members to these groups... later.</blockquote>";
		
		return $html;
	}
}
?>