<?php

$agency = $graphTarget;
$agency = stripslashes($agency);
$xml->addChild('name', htmlentities($agency));
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
if ($categoriesEnabled) {


    $categories = $dbConn->prepare('
SELECT "agencyName", category,LEFT("categoryUNSPSC",2) as categoryPrefix, value
FROM contractnotice
WHERE "agencyName = ?
AND "childCN" is null
GROUP BY LEFT(categoryUNSPSC,2)
');
    $categories->execute(array(
        $agency
    ));
    foreach ($categories->fetchAll() as $row) {
        $exists = false;
        foreach ($nodes->node as $node) {
            $attributes = $node->attributes();
            if ($attributes['id'] == "category-" . $row['categoryPrefix'] . "000000") {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $node = $nodes->addChild('node');
            $node->addAttribute("id", "category-" . $row['categoryPrefix'] . "000000");
            $node->addAttribute("label", $row['category']);
            formatCategoryNode($node);
            $node->addAttribute("tooltip", "$" . number_format($row['sum'], 2, '.', ','));
        }
        $link = $edges->addChild('edge');
        $tail_node_id = "category-" . $row['categoryPrefix'] . "000000";
        $head_node_id = "agency-" . $agency;
        //! todo avoid duplication of links
        $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
        $link->addAttribute("tooltip", $agency . " recieves goods/services in the industry of " . $row['category']);
        $link->addAttribute("tail_node_id", $tail_node_id);
        $link->addAttribute("head_node_id", $head_node_id);
    }
}
if ($postcodesEnabled) {


    $postcodes = $dbConn->prepare('
 SELECT "agencyName", "contactPostcode", value
FROM contractnotice
WHERE "agencyName" = ?
AND "childCN" is null
GROUP BY "contactPostcode"
');
    $postcodes->execute(array(
        $agency
    ));
    foreach ($postcodes->fetchAll() as $row) {
        $exists = false;
        foreach ($nodes->node as $node) {
            $attributes = $node->attributes();
            if ($attributes['id'] == "postcode-" . $row['contactPostcode']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $node = $nodes->addChild('node');
            $node->addAttribute("id", "postcode-" . $row['contactPostcode']);
            $node->addAttribute("label", "Postcode: " . $row['contactPostcode']);
            $node->addAttribute("tooltip", "$" . number_format($row['sum'], 2, '.', ','));
            formatPostcodeNode($node);
            $link = $edges->addChild('edge');
            $tail_node_id = "postcode-" . $row['contactPostcode'];
            $head_node_id = "agency-" . $row['agencyName'];
            $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
            $link->addAttribute("tooltip", $row['agencyName'] . " operates a office in postcode " . $row['contactPostcode']);
            $link->addAttribute("tail_node_id", $tail_node_id);
            $link->addAttribute("head_node_id", $head_node_id);
        }
    }
}
if ($politiciansEnabled) {
    $politicians = $dbConn->prepare("SELECT representative_id, firstname, surname, party, house, division_id, portfolio
FROM portfolio2representative
INNER JOIN representatives ON portfolio2representative.representative_id = representatives.id
INNER JOIN portfolios ON portfolio2representative.portfolio_id = portfolios.id");
    $politicians->execute();
    foreach ($politicians->fetchAll() as $row) {
        if (strpos($agency, $row['portfolio'])) {
            $exists = false;
            foreach ($nodes->node as $node) {
                $attributes = $node->attributes();
                if ($attributes['id'] == "politician-" . $row['firstname'] . $row['surname']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $node = $nodes->addChild('node');
                $node->addAttribute("id", "politician-" . $row['firstname'] . '.' . $row['surname']);
                $node->addAttribute("label", "Politician: " . $row['firstname'] . ' ' . $row['surname']);
                formatPoliticianNode($node);
                $link = $edges->addChild('edge');
                $tail_node_id = "politician-" . $row['firstname'] . '.' . $row['surname'];
                $head_node_id = "agency-" . $agency;
                $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
                $link->addAttribute("tooltip", $row['firstname'] . ' ' . $row['surname'] . " has a portfolio responsiblity for " . $agency);
                $link->addAttribute("tail_node_id", $tail_node_id);
                $link->addAttribute("head_node_id", $head_node_id);
            }
        }
    }
}
?>
