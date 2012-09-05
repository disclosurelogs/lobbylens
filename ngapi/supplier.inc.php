<?php
$supplierABN = $graphTarget;
$supplierN = $dbConn->prepare(' SELECT "supplierName"
FROM contractnotice
WHERE "supplierABN" = ?
LIMIT 1 ');
$supplierN->execute(array(
  $supplierABN
));
$name = $supplierN->fetch(PDO::FETCH_ASSOC);
$name['supplierName'] = ucsmart($name['supplierName']);
$supplierNode = $nodes->addChild('node');
$supplierNode->addAttribute("id", "supplier-" . $supplierABN);
$supplierNode->addAttribute("label", $name['supplierName']);
$supplierName = $name['supplierName'];
$xml->addChild('name',htmlentities($supplierName));

formatSupplierNode($supplierNode);
$agencies = $dbConn->prepare('
SELECT "agencyName", sum(value) as value
FROM contractnotice
WHERE "supplierABN" = ?
AND "childCN" is null
GROUP BY "agencyName"

');
$agencies->execute(array(
  $supplierABN
));
foreach($agencies->fetchAll() as $row) {
  $existing = $nodes->xpath('//node[@id="'."agency-" . $row['agencyName'].'"]');
  $exists = !empty($existing);
  if (!$exists) {
    $node = $nodes->addChild('node');
    $node->addAttribute("id", "agency-" . $row['agencyName']);
    $node->addAttribute("label", $row['agencyName']);
    formatSupplierNode($node);
    $node->addAttribute("tooltip", "$" . number_format($row['value'], 2, '.', ','));
  }
  $link = $edges->addChild('edge');
  $tail_node_id = "agency-" . $row['agencyName'];
  $head_node_id = "supplier-" . $supplierABN;
  //! todo avoid duplication of links
  $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
  $link->addAttribute("tooltip", $row['agencyName'] . " receives goods/services from " . $name['supplierName']);
  $link->addAttribute("tail_node_id", $tail_node_id);
  $link->addAttribute("head_node_id", $head_node_id);
  $link->addAttribute("edge_length_weight", $row['value']);
}

  $categories = $dbConn->prepare('
SELECT max(category) as category,substr( "categoryUNSPSC"::text, 0, 3 ) as "categoryPrefix", sum(value) as value
FROM contractnotice
WHERE "supplierABN" = ?
AND "childCN" is null
GROUP BY substr( "categoryUNSPSC"::text, 0, 3 )
');
  $categories->execute(array(
    $supplierABN
  ));
  foreach($categories->fetchAll() as $row) {
	$existing = $nodes->xpath('//node[@id="'."category-" . $row['categoryPrefix'] . "000000".'"]');
	$exists = !empty($existing);
    if (!$exists) {
      $node = $nodes->addChild('node');
      $node->addAttribute("id", "category-" . $row['categoryPrefix'] . "000000");
      $node->addAttribute("label", $row['category']);
      $node->addAttribute("tooltip", "$" . number_format($row['value'], 2, '.', ','));
      formatCategoryNode($node);
    }
    $tail_node_id = "category-" . $row['categoryPrefix'] . "000000";
    $head_node_id = "supplier-" . $supplierABN;
$existing = $edges->xpath('//edge[@id="'.$head_node_id . "|" . $tail_node_id.'"]');
  $exists = !empty($existing);
    if (!$exists) {
      $link = $edges->addChild('edge');
      //! todo avoid duplication of links
      $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
      $link->addAttribute("tooltip", $name['supplierName'] . " provides goods/services in category " . $row['category']);
      $link->addAttribute("tail_node_id", $tail_node_id);
      $link->addAttribute("head_node_id", $head_node_id);
    }
  
}

  $result = $dbConn->prepare(' SELECT "lobbyistClientID"
FROM lobbyist_clients
WHERE "ABN" = ? LIMIT 1 ');
  $result->execute(Array($supplierABN));
  $lobid = $result->fetch();
  $lobbyistClientID = $lobid['lobbyistClientID'];
  $result->closeCursor();
  $lobbyists = $dbConn->prepare('
SELECT *, abn as lobbyist_abn
FROM lobbyists
INNER JOIN lobbyist_relationships ON lobbyists."lobbyistID" = lobbyist_relationships."lobbyistID"
WHERE "lobbyistClientID" = ? ;
');
  $lobbyists->execute(array(
    $lobbyistClientID
  ));
  foreach($lobbyists->fetchAll() as $row) {
	$existing = $nodes->xpath('//node[@id="'."lobbyist-" . $row['lobbyist_abn'].'"]');
	$exists = !empty($existing);
       if (!$exists) {
      $node = $nodes->addChild('node');
      $node->addAttribute("id", "lobbyist-" . $row['abn']);
      $node->addAttribute("label", "Lobbyist: " . $row['trading_name']);
      //$node->addAttribute("tooltip", "$".number_format($row['value'], 2, '.', ','));
      formatLobbyistNode($node);
      $link = $edges->addChild('edge');
      $tail_node_id = "lobbyist-" . $row['abn'];
      $head_node_id = "supplier-" . $supplierABN;
      $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
      $link->addAttribute("tooltip", $row['trading_name'] . " lobbies for " . $supplierName);
      $link->addAttribute("tail_node_id", $tail_node_id);
      $link->addAttribute("head_node_id", $head_node_id);
    }
  
}

 
  $searchName = searchName($supplierName);
  $result = $dbConn->prepare(
          'select max("DonorClientNm"),"RecipientClientNm",sum("AmountPaid") as AmountPaid
              from political_donations where "DonorClientNm"
		LIKE ? group by "RecipientClientNm" order by "RecipientClientNm" desc');
  $result->execute(array(
        $searchName
    ));

    foreach ($result->fetchAll() as $row) {
      $existing = $nodes->xpath('//node[@id="'."donationrecipient-" . $row['RecipientClientNm'].'"]');
	$exists = !empty($existing);
      
      $head_node_id = "donationrecipient-" . $row['RecipientClientNm'];
      if (!$exists) {
        $node = $nodes->addChild('node');
        $node->addAttribute("id", $head_node_id);
        $node->addAttribute("label", "Donation Recipient: " . $row['RecipientClientNm']);
        //$node->addAttribute("tooltip", "$".number_format($row['value'], 2, '.', ','));
        formatLobbyistNode($node);
      }
      $link = $edges->addChild('edge');
      $tail_node_id = "supplier-" . $supplierABN;
      $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
      $link->addAttribute("tooltip", $supplierName . " donated $" . money_format('%i',$row['AmountPaid']) . " to " . $row['RecipientClientNm']);
      $link->addAttribute("tail_node_id", $tail_node_id);
      $link->addAttribute("head_node_id", $head_node_id);
    
}
?>
