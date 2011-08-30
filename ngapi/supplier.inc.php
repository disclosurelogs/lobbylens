<?php
$supplierABN = $graphTarget;
$supplierN = $dbConn->prepare(" SELECT supplierName
FROM `contractnotice`
WHERE supplierABN = ?
LIMIT 1 ");
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
$dbConn = null;
include "libs/dbconn.php";
$agencies = $dbConn->prepare("
SELECT agencyName, value
FROM `contractnotice`
WHERE supplierABN = ?
AND childCN = 0
GROUP BY agencyName
");
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
if ($categoriesEnabled) {
  $dbConn = null;
  include "libs/dbconn.php";
  $categories = $dbConn->prepare("
SELECT category,LEFT(categoryUNSPSC,2) as categoryPrefix, value
FROM `contractnotice`
WHERE supplierABN = ?
AND childCN = 0
GROUP BY LEFT(categoryUNSPSC,2)
");
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
}
if ($postcodesEnabled) {
  $dbConn = null;
  include "../libs/dbconn.php";
  $postcodes = $dbConn->prepare("
 SELECT supplierName, supplierPostcode, value
FROM `contractnotice`
WHERE supplierABN = ?
AND childCN = 0
GROUP BY supplierPostcode
");
  $postcodes->execute(array(
    $supplierABN
  ));
  foreach($postcodes->fetchAll() as $row) {
	$existing = $nodes->xpath('//node[@id="'."postcode-" . $row['supplierPostcode'].'"]');
	$exists = !empty($existing);

    if (!$exists) {
      $node = $nodes->addChild('node');
      $node->addAttribute("id", "postcode-" . $row['supplierPostcode']);
      $node->addAttribute("label", $row['supplierPostcode']);
      $node->addAttribute("tooltip", "$" . number_format($row['value'], 2, '.', ','));
      formatPostcodeNode($node);
      $link = $edges->addChild('edge');
      $tail_node_id = "postcode-" . $row['supplierPostcode'];
      $head_node_id = "supplier-" . $supplierABN;
      $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
      $link->addAttribute("tooltip", $name['supplierName'] . " operates a business in postcode" . $row['supplierPostcode']);
      $link->addAttribute("tail_node_id", $tail_node_id);
      $link->addAttribute("head_node_id", $head_node_id);
    }
  }
}
if ($lobbyistsEnabled) {
  createMySQLlink();
  $result = mysql_query(" SELECT lobbyistClientID
FROM `lobbyist_clients`
WHERE abn = $supplierABN
LIMIT 1 ");
  $lobid = mysql_fetch_assoc($result);
  $supplierID = $lobid['lobbyistClientID'];
  $dbConn = null;
  include "libs/dbconn.php";
  $lobbyists = $dbConn->prepare("
SELECT *
FROM lobbyists
INNER JOIN lobbyist_relationships ON lobbyists.lobbyistID = lobbyist_relationships.lobbyistID
WHERE lobbyistClientID = ? ;
");
  $lobbyists->execute(array(
    $supplierID
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
}

if ($politicialDonationsEnabled) {
  $cleanseNames = Array(
    "Ltd",
    "Limited",
    "Australiasia",
    "The ",
    "(NSW)",
    "(QLD)",
    "Pty",
    "Ltd."
  );
  $searchName = str_ireplace($cleanseNames, "", $supplierName);
  $searchName = trim($searchName);
  $result = mysql_query("select DonorClientNm,RecipientClientNm,DonationDt,sum(AmountPaid) as AmountPaid from political_donations where DonorClientNm
			       LIKE \"%" . $searchName . "%\" group by RecipientClientNm order by RecipientClientNm desc");
  if ($result) {
    while ($row = mysql_fetch_array($result)) {
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
  }
}
?>
