#!/usr/bin/php

<?php
//================================OPTION VALIDATION===================================
	//Construct the input string for the short options
	$shortOptions = "";
	$shortOptions .= "u:";
	$shortOptions .= "p::";
	$shortOptions .= "h:";
	$shortOptions .= "d::";
	
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
		echo "=========HELP TEXT==============\n";
		echo "Options for the Script:
--file [csv file name] – Required. this is the name of the CSV to be parsed
--create_table – this will cause the MySQL users table to be built (and no further action will be taken)
--dry_run – this will be used with the --file directive in the instance that we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered.
-u – Required. MySQL username
-p – MySQL password, defaults to blank if not supplied
-h – Required. MySQL host
-d – MySQL Database name to create table in, defaults to 'TestUsers' if not supplied\n";
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
	
	if (isset($options["d"])){
	    $DBName = $options["d"];
	}	else {
	    echo $DBName;
	    echo "No Database name given, using default DB 'TestUsers'\n";
	    $DBName = "TestUsers";
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
//=====================END OPTION VALIDATION=======================================================

	
//=====================DATABASE CONNECTION AND DATA IMPORT=========================================
	//Create connection to DB using supplied credentials, output success or error
	$conn = new mysqli($host, $user, $pw);
	if ($conn->connect_error) {
	    echo ("Connection failed: " . $conn->connect_error);
	    exit(3);
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
//===================END DATABASE CONNECTION AND DATA IMPORT=======================================

//===================DATABASE INTERACTION LOOP=====================================================
   
	//setup vars for use in loop
	$lineCount = 0;
	$MasterQuery = "";
	$Col1Name = "";
	$Col2Name = "";
	$Col3Name = "";
	
	
    while (! feof($file)){
        //get current line
        try{
            $line = (fgetcsv($file));
        } catch (Exception $e){
            echo "Error parsing found file as CSV. Exiting \n";
            exit(2);
        }
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
            
            $Col1Name = $conn->real_escape_string($line[0]);
            $Col2Name = $conn->real_escape_string($line[1]);
            $Col3Name = $conn->real_escape_string($line[2]);
            
            //SQL statement takes var names from column names, for easier portability
            $sql = "CREATE TABLE users (
                $Col1Name VARCHAR(100) NOT NULL,
                $Col2Name VARCHAR(100) NOT NULL,
                $Col3Name VARCHAR(100) NOT NULL PRIMARY KEY
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
            
        } else if(!$createTable){
            //Every other line except 0, if we aren't only creating table
            $validLine = true;
            $email = "";
            //Before checking if valid line, check for primary key presence, indicating EOF
            if ($line[2]== ""){
                break;
            }
            
            //Normalise Names
            $firstName = $conn->real_escape_string(normaliseName($line[0]));
            $lastName = $conn->real_escape_string(normaliseName($line[1]));
            
            //validate email, and confirm valid line
            if (validateEmail($line[2])){
                $email = $conn->real_escape_string(strtolower($line[2]));
            } else {
                echo "Email Validation Failed at Line $lineCount\n";
                $validLine = false;
            }
            //Check all conditions are met for line insert into DB, then execute query
            if (!$dryRun && !$createTable && $validLine){
                $sql = "INSERT INTO users VALUES ('$firstName', '$lastName', '$email')";
                
                if (!mysqli_query($conn, $sql)) {
                    echo "Error inserting line $lineCount " . mysqli_error($conn);
                    echo "\n";
                }
            }
            $lineCount++;
        }
    }
//===========================END DATABASE INTERACTION LOOP=========================================
    
//===========================EXITING===============================================================
    if ($dryRun){
        echo "All lines validated and formatted. No Data added to database. Exiting\n";
        exit(0);
    } else if  ($createTable){
        echo "Users table has been created, no data inserted to table. Exiting\n";
        exit(0);
    } else {
        echo "All validated/non-duplicate lines added. Exiting\n";
        exit(0);
    }
 
//==================================NORMALISATION AND VALIDATING FUNCTIONS=========================
    
    //https://stackoverflow.com/questions/10143007/php-normalize-a-string
    //Exact solution found on StackOverflow, no need to reinvent the wheel
    function normaliseName($name) {
        $name = strtolower($name);
        $normalized = array();
        
        foreach (preg_split('/([^a-z])/', $name, NULL, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $word) {
            if (preg_match('/^(mc)(.*)$/', $word, $matches)) {
                $word = $matches[1] . ucfirst($matches[2]);
            }
            
            $normalized[] = ucfirst($word);
        }
        
        $norm = implode('', $normalized);
        //Strip Special Chars from name
        return preg_replace("/^[a-z -']+$/", "", $norm);
    }
    
    function validateEmail($email){
        //Pattern found on http://www.regexlib.com
        $pattern = "/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/";
        if (preg_match($pattern, $email)){
            return true;
        } else {
            return false;
        }
    }
	
	//TODO:SQL injection attack vulnerable
?>
