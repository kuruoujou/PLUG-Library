<?php
	session_start();
	if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != 76)
	{
		require_once("wp_auth.php");
		$auth = new TMRSAuthentication();
		if( $auth->do_basic_authentication() ) {
		    $_SESSION['loggedin'] = 76;
		    header("Location: index.php");
		} else {
		    $auth->show_failed_login();
		}
	} else header("Location: index.php");
?>	
