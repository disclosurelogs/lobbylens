<?php
error_reporting(E_ALL);

$link = mysql_connect('localhost', 'team7', '');
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
@mysql_select_db("team7") or die("Unable to select database");

$unspscresult = mysql_query ("select * from UNSPSCcategories;");
while ($row = mysql_fetch_assoc($unspscresult)) {
	$unspsc[$row['UNSPSC']] = $row['Title'];
}
$catsresult = mysql_query ("SELECT LEFT( categoryUNSPSC, 2 ) as cat , SUM( value ) as value
FROM `contractnotice`
GROUP BY cat ;");
echo "<table>";
while ($row = mysql_fetch_assoc($catsresult)) {
	$catName = $unspsc[$row['cat']."000000"].$row['cat'];
	if ($row['cat'] = "") $catName = "null";
	
	echo "<tr><td>$catName</td><td>".$row['value']."</td></tr>";
}
?>
