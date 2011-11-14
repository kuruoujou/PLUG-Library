<?php
	/*
	The following require is part of the PEAR package XML_RPC.
	There is another package called XML_RPC2, this is not it.
	This example takes a simple GET (page.php?upc=123456789012)
	and sends it to the site.  There should probably be some error checking.
	
	To install PEAR for your server you should do one of the following:
	a) follow any instructions for your distro
	b) check your distro's package manager
	c) see http://pear.php.net/manual/en/installation.getting.php
	
	Once you have PEAR, you can install this by either:
	a) using your distro's package manager
	b) issue 'pear install XML_RPC' from the command line as root/admin 
	*/
	ini_set('display_errors', '1');
	require_once 'XML/RPC.php';
	
	//Take a URL value and turn it into a XML_RPC Value of a string type
	$params = array(new XML_RPC_Value($_REQUEST['upc'], string));
	
	//Set the 'method'
	
	//Use this to get the list of functions availiable.  No real parameter needed.
	//$msg = new XMP_RPC_Message('help', $params);
	
	//Use this if you have a CueCat
	//$msg = new XMP_RPC_Message('decodeCueCat', $params);
	
	//Use this to send a plain UPC
	$msg = new XML_RPC_Message('lookupUPC', $params);
	
	//Set the server for the client and page to send to
	$cli = new XML_RPC_Client('/rpc', 'http://www.upcdatabase.com');
	
	//Set debug info to true.  Useful for testing, shows the response from the server
	//$cli->setDebug(1);
	
	//More debug info, create the payload before sending.
	//Not necessary to function, but useful to test
	//$msg->createPayload();
	
	//TEST Print the response to the screen for testing
	//echo "<pre>" . print_r($msg->payload, true) . "</pre><hr />";
	
	//Actually have the client send the message to the server.  Save response.
	$resp = $cli->send($msg);
	
	//If there was a problem sending the message, the resp will be false
	if (!$resp)
	{
		//print the error code from the client and exit
		echo 'Communication error: ' . $cli->errstr;
		exit;
	}
	
	
	//If the response doesn't have a fault code, show the response as the array it is
	if(!$resp->faultCode())
	{
		//Store the value of the response in a variable
		$val = $resp->value();
		//Decode the value, into an array.
		$data = XML_RPC_decode($val);
		//Optionally print the array to the screen to inspect the values
		echo "<pre>" . print_r($data, true) . "</pre>";
	}else{
		//If something went wrong, show the error
		echo 'Fault Code: ' . $resp->faultCode() . "\n";
		echo 'Fault Reason: ' . $resp->faultString() . "\n";
	}
?>
