<?
class UserLevelEditorTheme extends Themelet {
	public function display_editor($levels) {
		global $page;
		$html = $this->get_html_for_level_editor($levels);
		$page->set_title("User Level Editor");
		$page->set_heading("User Level Editor");
                $page->add_block(new Block("Level Editor", $html, "main", 10));
	}
	
	private function get_html_for_level_editor($levels) {
		/**
		 * Long function name, but at least I won't confuse it with something else ^_^
		 *
		 * I'm not great with interfaces unless I'm working in Dreamweaver (drat) so I'll have to copy from the alias editor.
		 */

		$html = "";
			$table_header =  "<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Total Level Req'd</th>
						<th>Post Points Req'd</th>
						<th>Comment Points Req'd</th>
						<th>Tag Points Req'd</th>
						<th>Action</th>
						</tr>";
			$add_level = "
				<tr>
					<form action='".make_link("user_levels/add")."' method='POST'>
						<td>&nbsp;</td>
						<td><input style='text-align:center;' type='text' name='level_name'></td>
						<td><input style='text-align:center;' type='text' name='level_c_total'></td>
						<td><input style='text-align:center;' type='text' name='level_c_p'></td>
						<td><input style='text-align:center;' type='text' name='level_c_c'></td>
						<td><input style='text-align:center;' type='text' name='level_c_t'></td>
						<td><input type='submit' value='Add'></td>
					</form>
				</tr>
			";
			
		$table_rows = "";
        for ($i = 0 ; $i < count($levels) ; $i++)
        {
            //$result[$i]["name"] = stripslashes($result[$i]["name"]);


		/**
		 * Will this work?
		 */
			$id = $levels[$i]['id'];
			$level_name = $levels[$i]['level_name'];
			$level_c_total = $levels[$i]['level_c_total'];
			$level_c_p = $levels[$i]['level_c_p'];
			$level_c_c = $levels[$i]['level_c_c'];
			$level_c_t = $levels[$i]['level_c_t'];
			
			$table_rows	 .=  "<tr>
							<td>$id</td>
							<td>$level_name</td>
							<td>$level_c_total</td>
							<td>$level_c_p</td>
							<td>$level_c_c</td>
							<td>$level_c_t</td>
							
							<td>
								<form action='".make_link("user_levels/remove")."' method='POST'>
									<input type='hidden' name='id' value='$id'>
									<input type='submit' value='Remove'>
								</form>
							</td>
							</tr>";
		}

		$html = "
			<table id='user_levels' class='zebra'>
				<thead>$table_header</thead>
				<tbody>$table_rows</tbody>
				<tfoot>$add_level</tfoot>
			</table>";
		
		return $html;
	}
}
?>