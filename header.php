<?php

// include configuration file
include('config.php');

?>



<?php
if (isset($_SESSION['username']))
	echo "welcome " . $_SESSION['username'];

	$beginExecution = microtime(true);
?>
			