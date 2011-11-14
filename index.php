<?php
	session_start();
	$dbuser = '';
	$db = '';
	$dbpass = '';
	$key = "";
	if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != '76')
	{
		header('Location: login.php');
	}
	require_once('mobile_device_detect.php');
	mobile_device_detect(true,false,true,true,true,true,true,false,'http://purduelug.org/library/desktop.php');
?> 
<html>
<head>
	<meta name="viewport" content="initial-scale=1.0; maximum-scale=1.0;" />
	<title>PLUG Library Database</title>
	<link rel="stylesheet" type="text/css" href="./mobile.css" />
</head>
<body>
<?php
	//Setup Zend and Google Books
	$path = '/home/httpd/entities/plug/library/library';
	set_include_path(get_include_path().PATH_SEPARATOR.$path);		
	require_once("Zend/Loader.php");
	require_once 'XML/RPC.php';
	Zend_Loader::loadClass('Zend_Uri_Http');
	Zend_Loader::loadClass('Zend_Gdata_Books');

	//Setup MySQL Connection
	$conn = mysql_connect('localhost', $dbuser, $dbpass) or die(mysql_error());
	mysql_select_db($db) or die(mysql_error());
	
	//Check for given ISBN
	if (isset($_GET['q']))
	{
		$isbn = $_GET['q'];
	}

	//Check to see if "Add additional copy" button was pressed
	if (isset($_POST['quant']))
	{
		$isbn = mysql_real_escape_string($_POST['isbn']);
		if ($_POST['codeType'] == "isbn") $query = "UPDATE books SET quantity = quantity + 1 WHERE isbn='" . $isbn . "'";
		else if ($_POST['codeType'] == "ean") $query = "UPDATE ean SET quantity = quantity + 1 WHERE code='" . $isbn. "'";
		if (mysql_query($query))
		{
			$response = "<span class='red'>Successfully added additional copy to database.</span>";
		}
		else
		{
			$response = "<span class='red'>Error adding additional copy to database.</span>";
		}
	}

	//Check to see if "Checkout to user" was pressed
	if (isset($_POST['out']))
	{
		$isbn = mysql_real_escape_string($_POST['isbn']);
		$user = mysql_real_escape_string($_POST['user_answer']);
		if ($_POST['codeType'] == "isbn") $query = "SELECT checkedOutUser FROM books WHERE isbn='" . $isbn . "'";
		else if ($_POST['codeType'] == "ean") $query = "SELECT checkedOutUser FROM ean WHERE code='" . $isbn . "'";
		$result = mysql_query($query);
		while ($row = mysql_fetch_array($result))
		{
			$inUser = $row['checkedOutUser'];
		}
		if ($inUser != "")
		{
			$user="," . $user;
		}
		if ($_POST['codeType'] == "isbn") $query = "UPDATE books SET checkedOut = checkedOut + 1, checkedOutUser = '" . $user . "' WHERE isbn='" . $isbn . "'";
		else if ($_POST['codeType'] == "ean") $query = "UPDATE ean SET checkedOut = checkedOut + 1, checkedOutUser = '" . $user . "' WHERE code='" . $isbn . "'";
		if (mysql_query($query))
		{
			$response = "<span class='red'>Checked out to Purdue Account " . $user . "!</span>";
		}
		else
		{
			$response = "<span class='red'>Error checking out!</span>";
		}
	}

	//Check to see if "Check in to user" was selected
	if (isset($_POST['in']))
	{
		$isbn = mysql_real_escape_string($_POST['isbn']);
		$user = mysql_real_escape_string($_POST['user_answer']);
		if ($_POST['codeType'] == "isbn") $query = "SELECT checkedOutUser FROM books WHERE isbn='" . $isbn . "'";
		else if ($_POST['codeType'] == "ean") $query = "SELECT checkedOutUser FROM ean WHERE code='" . $isbn . "'";
		$result = mysql_query($query);
		while ($row = mysql_fetch_array($result))
		{
			$outUsers = $row['checkedOutUser'];
		}
		$outUsers = explode(",", $outUsers);
		$allGood = 0;
	  foreach ($outUsers as $outUser)
		{
			if($user == $outUser)	 $allGood = 1;
		}	
		if($allGood == 1) {
			if ($_POST['codeType'] == "isbn") $query = "UPDATE books SET checkedOut = checkedOut - 1, checkedOutUser='' WHERE isbn='".$isbn."'";
			else if ($_POST['codeType'] == "ean") $query = "UPDATE ean SET checkedOut = checkedOut - 1, checkedOutUser = '' WHERE code='". $isbn. "'";
			if (mysql_query($query))
			{
				$response = "<span class='red'>Checked back in!</span>";
			}
			else
			{
				$response = "<span class='red'>Error checking in!</span>";
			}
		}
		else {
			$response = "<span class='red'>User did not have item checked out!</span>";
		}
	}

	//Setup google books query
	$books = new Zend_Gdata_Books();
	$query = $books->newVolumeQuery();
	$query->setQuery('isbn:'.$isbn);
	$query->setStartIndex(1);
	$query->setMaxResults(1);
	
	//get the query
	$feed = $books->getVolumeFeed($query);

	//Set to see if we get any results.
	$totResults = 0;
	//RUn the loop obtaining results
	foreach($feed as $entry) {
		//Set the results variable to show we have results
		$totResults = 1;
		//Get the thumbnail
		$thumb = $entry->getThumbnailLink() ? $entry->getThumbnailLink()->getHref() : '';

		//get the title
		if(is_array($entry->getTitles())){
			foreach ($entry->getTitles() as $title) {
				$titles .= $title->getText() . " ";
			}
		}

		//get the authors
		if(is_array($entry->getCreators())) {
			foreach ($entry->getCreators() as $creator) {
				$authors .= $creator->getText() . " ";
			}
		}

		//get the publishers
		if(is_array($entry->getPublishers())){
			foreach ($entry->getPublishers() as $publisher) {
				$publishers .= $publisher->getText() . " ";
			}
		}

		//get the publishing dates
		if(is_array($entry->getDates())) {
			$arr = $entry->getDates();
			$pubdate = (is_object($arr[0])) ? $arr[0]->getText() : 'Unspecified';
		}

		//get the description (if available)
		if(is_array($entry->getDescriptions())){
			$arr = $entry->getDescriptions();
			$description = is_object($arr[0]) ? $arr[0]->getText() : 'No description available.';
		}

		//output all of this to the screen
		echo "<h2>" . $titles . "</h2>" . "<h3>By " . $authors . "</h3>" . "<img src=\"" . $thumb . "\" class=\"img\" /> Published by: " . $publishers . "<br/><br/> Published on: " . $pubdate . "<br/><br/>" . $description;
		
		//See how many of this book we have (if any)
		$number = mysql_num_rows(mysql_query("SELECT * FROM books WHERE isbn='" . mysql_real_escape_string($isbn) . "'"));

		//If we have none then insert this book into our database (because it /should/ have been scanned in with a barcode scanner, that means we have it.)	
		if ($number === 0) {
			if (mysql_query("INSERT INTO books (isbn, title, author, publisher, publishDate, thumbnail, description) VALUES('" . mysql_real_escape_string($isbn) . "', '" . mysql_real_escape_string($titles) . "', '" . mysql_real_escape_string($authors) . "', '" . mysql_real_escape_string($publishers) . "', '" . mysql_real_escape_string($pubdate) . "', '" . mysql_real_escape_string($thumb) . "', '" . mysql_real_escape_string($description) . "')")) {
				$response .= "<span class=\"red\">Successfully inserted into database.</span>";
			}
			else {
				$response .= "<span class=\"red\">Error inserting into database!" . mysql_error() . "</span>";
			}
		}
		//formatting
		echo "<br/><br/><br/>";

		//Echo any response from previously run commands (checkin, out, etc.)
		if ($response)
		{
			echo $response . "<br/><br/><br/>";
		}

		//Check how many we have available
		$query = "SELECT quantity, checkedOut FROM books WHERE isbn='" . mysql_real_escape_string($isbn) . "'";
		$result = mysql_query($query);		
		while ($row = mysql_fetch_array($result))
		{
			$quant = $row['quantity'];
			$out = $row['checkedOut'];
		}
		
		$available = $quant - $out;

		//Let us know how many are available, and set up the form.
		echo "There are " . $available . " of these available.<br/><form action='" . $_SERVER['PHP_SELF'] . "' method='POST' onSubmit=\"return doStuff()\"><input type=\"hidden\" name=\"isbn\" value=\"" . $isbn . "\" /><input type=\"hidden\" name=\"codeType\" value=\"isbn\" />";
		//If we have one or more available
		if ($available >= 1)
		{
			//then be sure to show the "Add additional copy" and "checkout" buttons
			echo "<input type='submit' name='quant' value='Add Additional Copy'><input type='submit' name='out' value='Checkout to User'>";
			//and if the amount that we have available is not equal to our total amount, be sure to show the "checkin" button.
			if ($available != $quant)
			{
				echo "<input type='submit' name='in' value='Check in from User'>";
			}
		}
		//if we have none available, be sure to show the "Add additional copy" and "checkin" buttons.
		else
		{
			echo "<input type='submit' name='quant' value='Add Additional Copy'><input type='submit' name='in' value='Check in from User'>";
		}
		//this is for the username for checking a book out or in.
		echo "<input type='text' name='user_answer' /></form>";
	}

	//if we have no results, then check the UPC database to see if it's a magazine/journal.
	if ($totResults == 0)
	{
		//look up the UPC code
		$rpc_key = $key;
		$params = array(new XML_RPC_Value(array( 'rpc_key' => new XML_RPC_Value($rpc_key, 'string'), 'upc' => new XML_RPC_Value($isbn, string)), 'struct'));
		$msg = new XML_RPC_Message('lookup', $params);
		$cli = new XML_RPC_Client('/xmlrpc', 'http://www.upcdatabase.com');
		$resp = $cli->send($msg);
		//echo error messages
		if (!$resp)  echo "<span class='red'>Communication error! " . $cli ->errstr . "</span>";
		if (!$resp->faultCode())
		{
			$val = $resp->value();
			$data = XML_RPC_decode($val);
			if ($data['message'] != "No database entry found.") {
				echo "<h2>UCC-13/EAN Located!</h2><h3>" . $data['description'] . "</h3>";
				//See how many of this item we have (if any)
				$number = mysql_num_rows(mysql_query("SELECT * FROM ean WHERE code='" . mysql_real_escape_string($isbn) . "'"));

				//If we have none then insert this book into our database (because it /should/ have been scanned in with a barcode scanner, that means we have it.)	
				if ($number === 0) {
					if (mysql_query("INSERT INTO ean(code, item) VALUES('" . mysql_real_escape_string($isbn) . "', '" . mysql_real_escape_string($data['description']) . "')")) {
						$response .= "<span class=\"red\">Successfully inserted into database.</span>";
					}
					else {
						$response .= "<span class=\"red\">Error inserting into database!" . mysql_error() . "</span>";
					}
				}
				//formatting
				echo "<br/><br/><br/>";

				//Echo any response from previously run commands (checkin, out, etc.)
				if ($response)
				{
					echo $response . "<br/><br/><br/>";
				}

				//Check how many we have available
				$query = "SELECT quantity, checkedOut FROM ean WHERE code='" . mysql_real_escape_string($isbn) . "'";
				$result = mysql_query($query);		
				while ($row = mysql_fetch_array($result))
				{
					$quant = $row['quantity'];
					$out = $row['checkedOut'];
				}
		
				$available = $quant - $out;

				//Let us know how many are available, and set up the form.
				echo "There are " . $available . " of these available.<br/><form action='" . $_SERVER['PHP_SELF'] . "' method='POST' onSubmit=\"return doStuff()\"><input type=\"hidden\" name=\"isbn\" value=\"" . $isbn . "\" /><input type=\"hidden\" name=\"codeType\" value=\"ean\" />";
				//If we have one or more available
				if ($available >= 1)
				{
					//then be sure to show the "Add additional copy" and "checkout" buttons
					echo "<input type='submit' name='quant' value='Add Additional Copy'><input type='submit' name='out' value='Checkout to User'>";
					//and if the amount that we have available is not equal to our total amount, be sure to show the "checkin" button.
					if ($available != $quant)
					{
						echo "<input type='submit' name='in' value='Check in from User'>";
					}
				}
				//if we have none available, be sure to show the "Add additional copy" and "checkin" buttons.
				else
				{
					echo "<input type='submit' name='quant' value='Add Additional Copy'><input type='submit' name='in' value='Check in from User'>";
				}
				//this is for the username for checking a book out or in.
				echo "<input type='text' name='user_answer' /></form>";
			}
			else echo "<span class='red'>No ISBN, UCC-13/EAN, or PLUG-IC was found. Please check the code and try again.</span>";
		}
		else echo "<span class='red'>FAULT CODE: " . $resp->faultCode() . "\n REASON: " . $resp->faultString();
	}

?>
</body>
</html>
