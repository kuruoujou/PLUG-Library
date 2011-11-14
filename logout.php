<?php
	if (isset($_SESSION['login'])){
		unset($_SESSION['login']);
		echo "session login value removed.";
	}
	session_destroy();
?>
