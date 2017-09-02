<?php

		session_start();
include_once "../config.php";
include_once "../header.php";


if (isset($_POST['username']))
{
	if($_POST['username'] == 'Chester' && $_POST['password'] == 'Herttna')
	{
		$_SESSION['username'] = 'Chester';
		$_SESSION['adminLevel'] = 0;//Lower is better
	}
	else if($_POST['username'] == 'Ellis' && $_POST['password'] == 'Wheeler')
	{
		$_SESSION['username'] = 'Ellis';
		$_SESSION['adminLevel'] = 1;
	}
	else
	{
	 	session_destroy();
		echo "Invalid username/password<br/>";
		
	}
}

if(isset($_SESSION['username'])){
		echo "<br/><strong>Welcome " . $_SESSION['username'] . "!</strong><br/><br/>
		
			<a href='../admin'>Admin Home</a><br/><br/>
		";
}
else{
	
?>


	<br/>
	<form action="index.php" method="post">
		<div class='center' style="text-align:right; width:300px;">
			Username: <input type="text" name="username"><br/>
			Password: <input type="password" name="password"><br/>
		</div>
		<br/>
		<button type="submit" value="submit" name="submit">Log In</button>

	</form>
	<br/>

<?php

	}

	include_once "../footer.php";
?>
