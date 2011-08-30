<?php
	include "/home/team7/libs/config.php";

	echo '<h2>suppliers</h2>';
echo '<form action="browse.php" method="post"><frameset>    <LABEL for="suppliersearch">Supplier search: </LABEL>
              <input type="text" name="suppliersearch"><input type="submit" value="Send"></frameset> </form>';

$suppliercloud = new wordcloud();
$suppliers = $dbConn->prepare("
SELECT supplierName, supplierABN, value
FROM `contractnotice`
WHERE value > 10000000
GROUP BY supplierABN
ORDER BY value DESC;
");
$suppliers->execute();
$supplierABNs = Array();

foreach ($suppliers->fetchAll() as $row) {
	$suppliercloud->addWord($row['supplierName'],$row['value']);
	$supplierABNs[$row['supplierName']] = $row['supplierABN'];
}

$myCloud = $suppliercloud->showCloud('array');
if (is_array($myCloud))
{
	foreach ($myCloud as $key => $value)
	{
		echo ' <a href="networkgraph.php?node_id=supplier-'.$supplierABNs[$value['word']].'" style="font-size: 
		1.'.$value['sizeRange'].'em">'.$value['word'].'</a> &nbsp;';
	}
}

$link = mysql_connect('localhost', 'team7', '');
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
@mysql_select_db("team7") or die("Unable to select database");

$unspscresult = mysql_query ("select * from UNSPSCcategories;");
while ($row = mysql_fetch_assoc($unspscresult)) {
        $unspsc[$row['UNSPSC']] = $row['Title'];
}
