<?php
$postcode = $graphTarget;
$postcodeNode = $nodes->addChild('node');
$postcodeNode->addAttribute("id","postcode-".$postcode);
$postcodeNode->addAttribute("label",$postcode);
formatPostcodeNode($postcodeNode);
$xml->addChild('name',$postcode);

$suppliers = $dbConn->prepare('
 SELECT "supplierName", "supplierABN", value
FROM contractnotice
WHERE "supplierPostcode" = ?
AND "childCN" is null
GROUP BY "supplierABN"
ORDER BY value DESC
LIMIT 0 , 30 
');
$suppliers->execute(array($postcode));

foreach ($suppliers->fetchAll() as $row) {
 $existing = $nodes->xpath('//node[@id="'. "supplier-" . $row['supplierABN'].'"]');
  $exists = !empty($existing);
 
	if (!$exists) {
	$row['supplierName'] = ucsmart($row['supplierName']);
	$node = $nodes->addChild('node');
	$node->addAttribute("id","supplier-".$row['supplierABN']);
	$node->addAttribute("label",$row['supplierName']);
	$node->addAttribute("tooltip", "$".number_format($row['value'], 2, '.', ','));
	formatPostcodeNode($node);
	$link = $edges->addChild('edge');
	$tail_node_id = "postcode-".$postcode;
	$head_node_id = "supplier-".$row['supplierABN'];
	$link->addAttribute("id", $head_node_id. "|" . $tail_node_id);
	$link->addAttribute("tooltip", $row['supplierName']. " operates a business in postcode " . $postcode);
	$link->addAttribute("tail_node_id",$tail_node_id);
	$link->addAttribute("head_node_id",$head_node_id);
	}
}

$agencies = $dbConn->prepare('
 SELECT "agencyName", "contactPostcode", value
FROM contractnotice
WHERE "contactPostcode" = ?
AND "childCN" is null
GROUP BY "agencyName"
');
$agencies->execute(array($postcode));

foreach ($agencies->fetchAll() as $row) {
	$exists = false; 
  $existing = $nodes->xpath('//node[@id="'."agency-" . $row['agencyName'].'"]');
  $exists = !empty($existing);
	if (!$exists) {
	$node = $nodes->addChild('node');
	$node->addAttribute("id","agency-".$row['agencyName']);
	$node->addAttribute("label",$row['agencyName']);
	$node->addAttribute("tooltip", "$".number_format($row['value'], 2, '.', ','));
	formatAgencyNode($node);
	$link = $edges->addChild('edge');
	$tail_node_id = "postcode-".$postcode;
	$head_node_id = "agency-".$row['agencyName'];
	$link->addAttribute("id", $head_node_id. "|" . $tail_node_id);
	$link->addAttribute("tooltip", $row['agencyName']. " operates a office in " . $tail_node_id);
	$link->addAttribute("tail_node_id",$tail_node_id);
	$link->addAttribute("head_node_id",$head_node_id);
	}
}

?>
