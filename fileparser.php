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
	
	$DBName = "TestDB";
	
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
	
	//Create DB for data if it doesn't already exist on the server
	$DBCreateSQL = "CREATE DATABASE IF NOT EXISTS $DBName;";
	$DBSelSQL = "USE $DBName;";
	
	if (!mysqli_query($conn, $DBCreateSQL)) {
	    echo "Error creating DB: " . mysqli_error($conn);
	    exit(3);
	}
	
	if (mysqli_query($conn, $DBSelSQL)) {
	    echo "DB successfully selected\n";
	} else {
	    echo "Error Selecting DB: " . mysqli_error($conn);
	    exit(3);
	}


    //Main loop for interacting with DB
	$lineCount = 0;
	
	$MasterQuery = "";
	
    while (! feof($file)){
        //get current line
        $line = (fgetcsv($file));
        //if firstline, construct table from headers, only if not a dry-run
        if (($lineCount == 0) && !$dryRun){
            //Drop any existing users table
            $usersDropSQL = "DROP TABLE IF EXISTS users;";
            
            if (mysqli_query($conn, $usersDropSQL)) {
                echo "Old users dropped successfully\n";
            } else {
                echo "Error dropping old Users table: " . mysqli_error($conn);
                exit(3);
            }
            
            //SQL statement takes var names from column names, for easier portability
            $sql = "CREATE TABLE users (
                $line[0] VARCHAR(100) NOT NULL,
                $line[1] VARCHAR(100) NOT NULL,
                $line[2] VARCHAR(100) NOT NULL PRIMARY KEY
                )";
                
            //Execute SQL query
            if (mysqli_query($conn, $sql)) {
                echo "Table users created successfully\n";
            } else {
                echo "Error creating table: " . mysqli_error($conn);
                exit(3);
            }
            //Finally increment linecount
            $lineCount++;
        } else {
            //Every other line except 0
            $firstName = normaliseName($line[0]);
            $lastName = normaliseName($line[1]);
        }
    }
    
    //https://stackoverflow.com/questions/10143007/php-normalize-a-string
    function normaliseName($name) {
        $name = strtolower($name);
        $normalized = array();
        
        foreach (preg_split('/([^a-z])/', $name, NULL, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $word) {
            if (preg_match('/^(mc)(.*)$/', $word, $matches)) {
                $word = $matches[1] . ucfirst($matches[2]);
            }
            
            $normalized[] = ucfirst($word);
        }
        
        return implode('', $normalized);
    }
    
    function validateEmail($email){
        
    }
	
	
?>
