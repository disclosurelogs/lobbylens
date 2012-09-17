<?php

$lobbyistABN = $graphTarget;
$lobbyistN = $dbConn->prepare('SELECT "lobbyistID", trading_name as preferred_name
FROM lobbyists
WHERE abn = ?
LIMIT 1');
$lobbyistN->execute(array(
    $lobbyistABN
));
$name = $lobbyistN->fetch(PDO::FETCH_ASSOC);
//$name['preferred_name'] = ucsmart($name['preferred_name']);
$lobbyistNode = $nodes->addChild('node');
$lobbyistNode->addAttribute("id", "lobbyist-" . $lobbyistABN);
$lobbyistNode->addAttribute("label", $name['preferred_name']);
formatLobbyistNode($lobbyistNode);
$lobbyistID = $name['lobbyistID'];
$lobbyistName = $name['preferred_name'];
$xml->addChild('name', htmlentities($lobbyistName));
$lobbyistN->closeCursor();
$lobbyistclients = $dbConn->prepare('
SELECT *
FROM lobbyist_clients
INNER JOIN lobbyist_relationships ON 
lobbyist_clients."lobbyistClientID" = lobbyist_relationships."lobbyistClientID"
WHERE "lobbyistID" = ?;
');
$lobbyistclients->execute(array(
    $lobbyistID
));
foreach ($lobbyistclients->fetchAll() as $row) {
    $clientABN = null;
    $searchName = searchName($row['business_name']);
    //! todo: use ABNs properly rather than supplierName exclusively to check gov supplier
    //! get ABNs from lobbyist client tbale not supplier table
    $result = $dbConn->prepare('SELECT "supplierABN"
	FROM contractnotice
	WHERE "supplierName" LIKE ?
	LIMIT 1 ');
    $result->execute(Array($searchName));
    if (isset($result) && $result->rowCount() > 0) {
        $abn = $result->fetch(PDO::FETCH_ASSOC);
        $clientABN = $abn['supplierABN'];
    }
    $exists = false;
    foreach ($nodes->node as $node) {
        $attributes = $node->attributes();
        if ($attributes['id'] == "supplier-" . $clientABN || $attributes['id'] == "lobbyistclient-" . $row['business_name']) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $node = $nodes->addChild('node');
        if (!$clientABN) {
            $head_node_id = "lobbyistclient-" . $row['business_name'];
            $govsupplier = "";
        } else {
            $head_node_id = "supplier-" . $clientABN;
            $govsupplier = " and Government supplier";
        }
        $node->addAttribute("id", $head_node_id);
        $node->addAttribute("label", "Client" . $govsupplier . ": " . $row['business_name']);
        //$node->addAttribute("tooltip", "$".number_format($row['value'], 2, '.', ','));
        formatLobbyistNode($node);
    }
    $link = $edges->addChild('edge');
    $tail_node_id = "lobbyist-" . $lobbyistABN;
    $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
    $link->addAttribute("tooltip", $lobbyistName . " lobbies for " . $row['business_name']);
    $link->addAttribute("tail_node_id", $tail_node_id);
    $link->addAttribute("head_node_id", $head_node_id);
}
// donations
$searchName = searchName($lobbyistName);
$result = $dbConn->prepare('
      select max("DonorClientNm"),"RecipientClientNm", sum("AmountPaid") as "AmountPaid" from political_donations where "DonorClientNm"
			       LIKE ? group by "RecipientClientNm" order by "RecipientClientNm" desc');
$result->execute(array(
    $searchName
));

foreach ($result->fetchAll() as $row) {
    $exists = false;
    foreach ($nodes->node as $node) {
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
    $tail_node_id = "lobbyist-" . $lobbyistABN;
    $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
    $link->addAttribute("tooltip", $lobbyistName . " donated $" . money_format('%i', $row['AmountPaid']) . " to " . $row['RecipientClientNm']);
    $link->addAttribute("tail_node_id", $tail_node_id);
    $link->addAttribute("head_node_id", $head_node_id);


}
?>
