#!/usr/bin/env php
<?php

	/**
	* add the representities to the table
	*/

	$reps = simplexml_load_file('representatives.xml');
	//$reps = simplexml_load_file('senators.xml');

	$db = new mysqli("localhost", "team7", "", "team7");
	/* check connection */
	if (mysqli_connect_errno()) {
	    	printf("Connect failed: %s\n", mysqli_connect_error());
    		exit();
	}


	// id, firstname, surname, party, house, seat
	foreach($reps as $member) {

		// find the id for the division
		$d_query = sprintf('SELECT id FROM electoral_divisions WHERE division="%s"', $member["division"]);
		if(!$d_result = $db->query($d_query)) {
			printf("Error: %s\n", $db->error);
		}
		$d_row = mysqli_fetch_assoc($d_result);

		$query = sprintf('INSERT into representatives VALUES("", "%s", "%s", "%s", "%s", "%s")', 
				$member["firstname"], 
				$member["lastname"], 
				$member["party"], 
				$member["house"], 
				$d_row["id"]);

		

		//$query = sprintf('INSERT INTO electoral_divisions (id, division) VALUE("", "%s")',  $member['division']);

		if(!$db->query($query)) {
			printf("Error: %s\n", $db->error);
		}


		/*
		$select = sprintf("SELECT id FROM representatives WHERE firstname='%s' AND surname='%s'", $member["firstname"], $member["lastname"]);
		$result = $db->query($select);
		$row = mysqli_fetch_assoc($result);

		$query = sprintf('UPDATE electoral_divisions SET representative_id="%d" WHERE division="%s"', $row["id"], $member["division"]);

		if(!$db->query($query)) {
			printf("Error: %s\n", $db->error);
		}
		*/

	}
	
	$db->close();	
