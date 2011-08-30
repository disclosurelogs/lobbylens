<?php

	include "/home/team7/libs/config.php";
	
	echo "<br><br><h1>categories</h1>";
$catsresult = mysql_query ("SELECT LEFT( categoryUNSPSC, 2 ) as cat , SUM( value ) as value
FROM `contractnotice`
GROUP BY cat ;");
echo "<table>";
while ($row = mysql_fetch_assoc($catsresult)) {
        $catName = $unspsc[$row['cat']."000000"];
        if ($row['cat'] == "") $catName = "null";
        echo "<tr><td><a href=\"networkgraph.php?node_id=category-".$row['cat']."000000"."\">$catName</a></td><td>"."$".number_format($row['value'],
2, '.',
',')."</td></tr>";
}

?>
