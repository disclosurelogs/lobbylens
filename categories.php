<?php
include	("libs/config.php");
include_header("Industries");
?>
  <h2>Industries</h2>
  <?php


	$unspsc = array();
	$unspscQuery = $dbConn->query('SELECT "UNSPSC", "Title" FROM "UNSPSCcategories" where "UNSPSC"::text like \'%000000\'');
	foreach ($unspscQuery->fetchAll() as $r) {
		$unspsc[$r['UNSPSC']] = $r['Title'];
	}

	$catresult = $dbConn->query('SELECT distinct substr( "categoryUNSPSC"::text, 0, 3 )  as cat , SUM( value ) as value
				FROM contractnotice WHERE "childCN" is null
				GROUP BY cat
			ORDER BY sum(value) DESC;');

	echo "<table id=\"categories\">";
	foreach ($catresult->fetchAll() as $row) {
        
        if ($row['cat'] == "") {
			$catName = "null";
			continue;
		} else {
                    $catName = $unspsc[$row['cat']."000000"];
        	echo "<tr><td><a href=\"networkgraph.php?node_id=category-".$row['cat']."000000"."\">$catName</a></td><td>"."$".number_format($row['value'],
		2, '.',',')."</td></tr>";
		}
	}	
echo "</table>";
include_footer();
?>

