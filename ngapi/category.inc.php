<?php
$categoryID = $graphTarget;
createMySQLlink();

$result = mysql_query (" SELECT Title
FROM `UNSPSCcategories`
WHERE UNSPSC = $categoryID
LIMIT 1 ");
$name = mysql_fetch_assoc($result);

$categoryNode = $nodes->addChild('node');
$categoryNode->addAttribute("id","category-".$categoryID);
$categoryNode->addAttribute("label",$name['Title']);
$xml->addChild('name', htmlentities($name['Title']));
formatCategoryNode($categoryNode);
$suppliers = $dbConn->prepare("
 SELECT supplierName, supplierABN, category, value
FROM `contractnotice`
WHERE LEFT(categoryUNSPSC,2) = LEFT(?,2)
AND childCN = 0
GROUP BY supplierABN
ORDER BY value DESC
LIMIT 0 , 30 
");
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
