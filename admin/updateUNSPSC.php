<?php
error_reporting(E_ALL);

$link = mysql_connect('localhost', 'team7', '');
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
@mysql_select_db("team7") or die("Unable to select database");

$unspscresult = mysql_query ("select * from UNSPSCcategories;");
while ($row = mysql_fetch_assoc($unspscresult)) {
	$unspsc[$row['Title']] = $row['UNSPSC'];
}

$query = "SELECT CNID,category,value
FROM `contractnotice`
WHERE `categoryUNSPSC` IS NULL OR `categoryUNSPSC` = 0";
$emptycatresult = mysql_query ($query);
$missing = Array();
if ($emptycatresult){
	while ($record = mysql_fetch_assoc($emptycatresult)) {
	if ($unspsc[$record['category']] == "") {
		$missing[$record['category']]= $missing[$record['category']]+ $record['value'];
//		echo "<br>\n Category not found for: \n";
//		print_r($record);
	} else {
	$result = mysql_query("UPDATE contractnotice SET categoryUNSPSC = 
'".mysql_real_escape_string($unspsc[$record['category']])."' where CNID = 
'".mysql_real_escape_string($record['CNID'])."';");
	if ($result) echo $record['CNID']. " set to ". ($unspsc[$record['category']]) . " <br>\n";
	else echo "error".mysql_error();
	}
	} 
} else echo "error".mysql_error();
asort($missing);
print_r($missing);
?>
