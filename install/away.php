<?php
	//Logout from MODX Manager for fix issue #1
	if (file_exists('../manager/includes/config.inc.php')) {
		include '../manager/includes/config.inc.php';
		session_name($site_sessionname);
		session_start();
		// destroy session cookie
		if (isset($_COOKIE[session_name()]))
			setcookie(session_name(), '', 0, MODX_BASE_URL);
		//// now destroy the session
		@session_destroy(); // this sometimes generate an error in iis
	}
		
	//Redirect
	if (isset($_GET['rminstall'])) {
		// remove install folder and files
		header("Location: ../manager/processors/remove_installer.processor.php?rminstall=1",TRUE,301);
	}	
	else {
		header("Location: ../manager/",TRUE,301);
	}	
?>
