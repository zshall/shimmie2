<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>Jquery WITS Form</title>
		<script type="text/javascript" src="js/jquery.progressbar.min.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				$("#pb1").progressBar();
			});
		</script>
	</head>
	<body>
				<h2>Progress Bars &amp; Controls</h2>
				<span class="progressBar" id="pb1">75%</span>
				<strong>Some controls: </strong>
				<a href="#" onclick="$('#pb1').progressBar(20);">20</a> |
				<a href="#" onclick="$('#pb1').progressBar(40);">40</a> |
				<a href="#" onclick="$('#pb1').progressBar(60);">60</a> |
				<a href="#" onclick="$('#pb1').progressBar(80);">80</a> |
				<a href="#" onclick="$('#pb1').progressBar(100);">100</a>
	</body>
</html>
