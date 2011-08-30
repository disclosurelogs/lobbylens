<?php
include	("libs/config.php");
include_header();
?>
  <h2>Industries</h2>
  <?php


	$unspsc = array();
	$unspscQuery = $dbConn->query('SELECT UNSPSC, Title FROM UNSPSCcategories');
	foreach ($unspscQuery->fetchAll() as $r) {
		$unspsc[$r['UNSPSC']] = $r['Title'];
	}

	$catresult = $dbConn->query('SELECT LEFT( categoryUNSPSC, 2 ) as cat , SUM( value ) as value
				FROM `contractnotice` WHERE childCN = 0
				GROUP BY cat
			ORDER BY value DESC;');

	echo "<table id=\"categories\">";
	foreach ($catresult->fetchAll() as $row) {
        $catName = $unspsc[$row['cat']."000000"];
        if ($row['cat'] == "") {
			$catName = "null";
			continue;
		} else {
        	echo "<tr><td><a href=\"networkgraph.php?node_id=category-".$row['cat']."000000"."\">$catName</a></td><td>"."$".number_format($row['value'],
		2, '.',',')."</td></tr>";
		}
	}	
echo "</table>";
?>
</div>
</body>
</html>
