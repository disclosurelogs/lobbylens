#!/usr/bin/env php
<?php

	/**
	* add the divisions to the division table
	*/

	$divisions = simplexml_load_file('divisions.xml');

	$db = new mysqli("localhost", "team7", "", "team7");
	/* check connection */
	if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());
    		exit();
	}


	// id, firstname, surname, party, house, seat
	foreach($divisions as $d) {
		$sql = sprintf('INSERT INTO electoral_divisions VALUES ("", "%s")', $d->name["text"]);
		$db->query($sql);
	}
	
	$db->close();	
