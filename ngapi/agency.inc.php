<?php

$agency = $graphTarget;
$agency = stripslashes($agency);
$xml->addChild('name', htmlspecialchars($agency));
$agencyNode = $nodes->addChild('node');
$agencyNode->addAttribute("id", "agency-" . $agency);
$agencyNode->addAttribute("label", $agency);

formatAgencyNode($agencyNode);
$suppliersAggregates = $dbConn->prepare('
 SELECT sum(value) as totalvalue, count(*) as "numContracts"
FROM contractnotice
WHERE "agencyName" = ?
AND "childCN" is null
');
$suppliersAggregates->execute(array(
    $agency
));
$result = $suppliersAggregates->fetch(PDO::FETCH_ASSOC);
$agencyTotalValue = $result['totalvalue'];
$agencyTotalContracts = $result['numContracts'];
$suppliersAggregates->closeCursor();
$suppliers = $dbConn->prepare('
 SELECT min("supplierName") as "supplierName", "supplierABN", sum(value), count(1) as count
FROM contractnotice
WHERE "agencyName" = ?
AND "childCN" is null
GROUP BY "supplierABN"
ORDER BY sum(value) DESC
LIMIT 30 
');
$suppliers->execute(array(
    $agency
));
foreach ($suppliers->fetchAll() as $row) {
    $existing = $nodes->xpath('//node[@id="' . "supplier-" . $row['supplierABN'] . '"]');
    $exists = !empty($existing);
    if (!$exists) {
        $row['supplierName'] = ucsmart($row['supplierName']);
        $node = $nodes->addChild('node');
        $node->addAttribute("id", "supplier-" . $row['supplierABN']);
        $node->addAttribute("label", $row['supplierName']);
        formatSupplierNode($node);
        $node->addAttribute("tooltip", "$" . number_format($row['sum'], 2, '.', ',') . " (" . number_format((($row['sum'] / $agencyTotalValue) * 100), 2) . "% of all contract expenditure) \n" . $row['count'] . " contracts (" . number_format((($row['count'] / $agencyTotalContracts) * 100), 2) . "% of all 
contracts)");
        $link = $edges->addChild('edge');
        $tail_node_id = "agency-" . $agency;
        $head_node_id = "supplier-" . $row['supplierABN'];
        $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
        $link->addAttribute("tooltip", $row['supplierName'] . " is a supplier of " . $agency);
        $link->addAttribute("tail_node_id", $tail_node_id);
        $link->addAttribute("head_node_id", $head_node_id);
        $link->addAttribute("edge_length_weight", $row['sum']);
        if ($details) {
            $detailsNode = $link->addChild('details');

            $txnNode = $detailsNode->addChild('transaction');
            $fromNode = $txnNode->addChild('from');
            $fromNode->addChild("name", $agency);
            $fromNode->addChild("id", $tail_node_id);
            $toNode = $txnNode->addChild('to');
            $toNode->addChild("name", $row['supplierName']);
            $toNode->addChild("id", $head_node_id);
            $valueNode = $txnNode->addChild('value', $row['sum']);
            $dateNode = $txnNode->addChild('date');
            $descriptionNode = $txnNode->addChild('description');
        }
    }
}

?>
