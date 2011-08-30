#!/usr/bin/env php
<?php

	/**
	* Load up the division geo data from the xml generate from the json
	*
	*/

	$divisions = simplexml_load_file('divisions_geo.xml');

	$db = new mysqli("localhost", "team7", "", "team7");
	/* check connection */
	if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());
    		exit();
	}


	// id, firstname, surname, party, house, seat
	foreach($divisions as $d) {

		// find the division id
		$select = sprintf("SELECT id FROM electoral_divisions WHERE division='%s'", mysql_escape_string($d->nm));
		$result = $db->query($select);
                $row = mysqli_fetch_assoc($result);

		// insert the data
		if(!empty($row["id"])) {
			$sql = sprintf('INSERT INTO division_geo VALUES("%d", "%s", "%s")', $row['id'], $d->lat, $d->long);
			if(!$db->query($sql)) {
				printf("Error: %s\n", $db->error);
			}	
		}
		else {
			echo "Failed to load " . $d->nm . "\n\n";
		}

	}
	
	$db->close();	
