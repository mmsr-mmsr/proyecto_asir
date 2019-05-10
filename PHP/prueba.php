<?php
	session_start();
	include "funciones.php";
	echo date("d/m/Y H:i:s", 1555632000);

?>
<html>
<head>
</head>
<body>
	<form method="post" action="">
		<section class="input">
			<input type="date" id="date" name="maintenanace_date"/>
		</section>
		<section class="input">
			<input type="time" id="time" name="maintenance_time" placeholder="EST"/>
		</section>
	</form>
	<button onclick="ver()">Ver fecha</button>
	<script>
	function ver() {
		var date = new Date(document.getElementById("date").value);
		var timestamp2 = date.getTime();
		alert(timestamp2);
	}
	</script>
</body>
</html>
