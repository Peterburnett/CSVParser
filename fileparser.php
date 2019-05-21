#!/usr/bin/php

<?php
	//Construct the input string for the short options
	$shortOptions = "";
	$shortOptions .= "u:";
	$shortOptions .= "p::";
	$shortOptions .= "h:";
	
	//Construct the input string for the long options
	$longOptions = array(
		"file:",
		"create_table",
		"dry_run",
		"help",
	);
	
	//Read the options from the CLI, and set vars from supplied options
	$options = getopt($shortOptions, $longOptions);
	
	//Check for help command before parsing command
	if (isset($options["help"])){
		echo "HELP TEXT \n";
		exit(0);		
	}
	
	//Short option Validation
	if (isset($options["u"])){
		$user = $options["u"];
	}	else {
		echo "No Username supplied for DB, Exiting\n";
		exit(1);
	}
	
	if (isset($options["p"])){
		$pw = $options["p"];
	}	else {
		echo "No Password supplied for DB, blank password used";
		$pw = "";
	}
	
	if (isset($options["h"])){
		$host = $options["h"];
	}	else {
		echo "No Host supplied for DB, Exiting\n";
		exit(1);
	}
	
	//Long Option Validation
	if (isset($options["file"])){
		$filepath = $options["file"];
	}	else {
		echo "No FilePath supplied for CSV, Exiting\n";
		exit(1);
	}
	
	if (isset($options["create_table"])){
		$createTable = true;		
	} else {
		$createTable = false;
	}
	
	if (isset($options["dry_run"])){
		$dryRun = true;		
	} else {
		$dryRun = false;
	}
	
	//Create connection to DB using supplied credentials, output success or error
	$conn = new mysqli($host, $user, $pw);
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}
	echo "Connected successfully\n";
	
	//Read in the CSV
	try{
	    $file = fopen($filepath, "r");
	} catch (Exception $e){
	    echo 'Caught exception: ',  $e->getMessage(), "\n";
	    exit(2);
	}
	
	//Create table in the DB called users
	
	
	
	
?>
