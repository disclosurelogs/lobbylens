<?php
$categoryID = $graphTarget;

$result = $dbConn->prepare ('SELECT "Title"
FROM "UNSPSCcategories"
WHERE "UNSPSC" = ?
LIMIT 1 ');
$result->execute(array(
    $categoryID
));
$name = $result->fetch();

$categoryNode = $nodes->addChild('node');
$categoryNode->addAttribute("id","category-".$categoryID);
$categoryNode->addAttribute("label",$name['Title']);
$xml->addChild('name', htmlspecialchars($name['Title']));
formatCategoryNode($categoryNode);
$suppliers = $dbConn->prepare('
 SELECT max("supplierName") as "supplierName",max("supplierABN") as "supplierABN", max(category) as category, sum(value) as value
FROM contractnotice
WHERE substr( "categoryUNSPSC"::text, 0, 3 ) = substr( ?, 0, 3 )
AND "childCN" is null
GROUP BY "supplierABN"
ORDER BY sum(value) DESC
LIMIT 30 
');
$suppliers->execute(array($categoryID));

foreach ($suppliers->fetchAll() as $row) {
	 $existing = $nodes->xpath('//node[@id="'. "supplier-" . $row['supplierABN'].'"]');
  $exists = !empty($existing);
 	if (!$exists) {
    $row['supplierName'] = ucsmart($row['supplierName']);
	$node = $nodes->addChild('node');
	$node->addAttribute("id","supplier-".$row['supplierABN']);
	$node->addAttribute("label",$row['supplierName']);
	$node->addAttribute("tooltip", "$".number_format($row['value'], 2, '.', ','));
	formatSupplierNode($node);
	$link = $edges->addChild('edge');
	$tail_node_id = "category-".$categoryID;
	$head_node_id = "supplier-".$row['supplierABN'];
	$link->addAttribute("tooltip", $row['supplierName'] . " provides goods/services in category " . $row['category']);
	$link->addAttribute("id", $head_node_id. "|" . $tail_node_id);
	$link->addAttribute("tail_node_id",$tail_node_id);
	$link->addAttribute("head_node_id",$head_node_id);
	}
}

?>
