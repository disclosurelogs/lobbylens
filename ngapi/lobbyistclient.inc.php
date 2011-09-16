<?php
$lobbyistClientName = $graphTarget;
$lobbyistClientNode = $nodes->addChild('node');
$lobbyistClientNode->addAttribute("id", "lobbyistclient-" . $lobbyistClientName);
$lobbyistClientNode->addAttribute("label", $lobbyistClientName);
formatLobbyingClientNode($lobbyistClientNode);
$xml->addChild('name', htmlentities($lobbyistClientName));
$supplierN = $dbConn->prepare(" SELECT lobbyistClientID
FROM lobbyist_clients
WHERE business_name = ?
LIMIT 1 ");
$supplierN->execute(array(
  $lobbyistClientName
));
$lobbyistClientID = $supplierN->fetch(PDO::FETCH_OBJ)->lobbyistClientID;

$lobbyists = $dbConn->prepare("
SELECT *
FROM lobbyists
INNER JOIN lobbyist_relationships ON lobbyists.lobbyistID = lobbyist_relationships.lobbyistID
WHERE lobbyistClientID = ? ;
");
$lobbyists->execute(array(
  $lobbyistClientID
));
createMySQLlink();
foreach($lobbyists->fetchAll() as $row) {
  $exists = false;
  foreach($nodes->node as $node) {
    $attributes = $node->attributes();
    if ($attributes['id'] == "lobbyist-" . $row['abn']) {
      $exists = true;
      break;
    }
  }
  if (!$exists) {
    $node = $nodes->addChild('node');
      $head_node_id = "lobbyist-" . $row['abn'];
    $node->addAttribute("id", $head_node_id);
    $node->addAttribute("label", "Lobbyist: " . $row['business_name']);
    formatLobbyistNode($node);
  }
  $link = $edges->addChild('edge');
  $tail_node_id = "lobbyistclient-" . $lobbyistClientName;
  $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
  $link->addAttribute("tooltip", $row['business_name'] . " lobbies for " . $lobbyistClientName);
  $link->addAttribute("tail_node_id", $tail_node_id);
  $link->addAttribute("head_node_id", $head_node_id);
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
  $searchName = str_ireplace($cleanseNames, "", $lobbyistClientName);
    $searchName = trim($searchName);
  $result = mysql_query("select DonorClientNm,RecipientClientNm,DonationDt,sum(AmountPaid) as AmountPaid from political_donations where DonorClientNm
			       LIKE \"%" . $searchName . "%\" group by RecipientClientNm order by RecipientClientNm desc");
  if ($result) {
    while ($row = mysql_fetch_array($result)) {
      $exists = false;
      foreach($nodes->node as $node) {
        $attributes = $node->attributes();
        if ($attributes['id'] == "donationrecipient-" . $row['RecipientClientNm']) {
          $exists = true;
          break;
        }
      }
      $head_node_id = "donationrecipient-" . $row['RecipientClientNm'];
      if (!$exists) {
        $node = $nodes->addChild('node');
        $node->addAttribute("id", $head_node_id);
        $node->addAttribute("label", "Donation Recipient: " . $row['RecipientClientNm']);
        formatLobbyistNode($node);
      }
      $link = $edges->addChild('edge');
      $tail_node_id = "lobbyistclient-" . $lobbyistClientName;
      $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
      $link->addAttribute("tooltip", $lobbyistClientName . " donated $" . money_format('%i',$row['AmountPaid']) . " to " . $row['RecipientClientNm']);
      $link->addAttribute("tail_node_id", $tail_node_id);
      $link->addAttribute("head_node_id", $head_node_id);
    }
  }
}
?>
