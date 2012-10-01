<?php

$recipientName = $graphTarget;
$recipientNode = $nodes->addChild('node');
$recipientNode->addAttribute("id", "donationrecipient-" . $recipientName);
$recipientNode->addAttribute("label", $recipientName);
formatPostcodeNode($recipientNode);
$xml->addChild('name', $recipientName);

$result = $dbConn->prepare('select DonorClientNm,RecipientClientNm,DonationDt,sum(AmountPaid) as AmountPaid from political_donations where RecipientClientNm
			       LIKE ? group by DonorClientNm order by DonorClientNm desc');

$head_node_id = "donationrecipient-" . $recipientName;
$recipientName = "%" . $recipientName . "%";
$result->execute(array(
    $recipientName
));

foreach ($result->fetchAll() as $row) {
    $tail_node_id = "donor-" . $row['DonorClientNm'];
    $extralabel = "";
    $searchName = searchName($row['DonorClientNm']);
    $supplier = $dbConn->prepare('SELECT "supplierABN"
	FROM contractnotice
	WHERE supplierName LIKE ?
	LIMIT 1');
    $lobbyclient = $dbConn->prepare('SELECT "ABN" FROM lobbyist_clients WHERE business_name LIKE ?');
    $lobbyclient->execute(Array($searchName));
    if ($lobbyclient && $lobbyclient->rowCount() > 0) {
        $abn = $lobbyclient->fetch(PDO::FETCH_ASSOC);
        $tail_node_id = "lobbyistclient-" . $abn['ABN'];
        $extralabel .= " and lobbying client";
    }
    if ($supplier && $supplier->rowCount() > 0) {
        $abn = $supplier->fetch(PDO::FETCH_ASSOC);
        $tail_node_id = "supplier-" . $abn['supplierABN'];
        $extralabel .= " and government supplier";
    }
    $lobbyist = $dbConn->prepare('SELECT "ABN" FROM lobbyists WHERE (business_name LIKE ? OR trading_name = LIKE ?');
    if ($lobbyist && $lobbyist->rowCount() > 0) {
        $abn = $lobbyist->fetch(PDO::FETCH_ASSOC);
        $tail_node_id = "lobbyist-" . $abn['ABN'];
        $extralabel .= " and lobbyist";
    }
    $exists = false;
    foreach ($nodes->node as $node) {
        $attributes = $node->attributes();
        if ($attributes['id'] == $tail_node_id) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $node = $nodes->addChild('node');
        $node->addAttribute("id", $tail_node_id);
        $node->addAttribute("label", "Donor" . $extralabel . ": " . $row['DonorClientNm']);
        if (strstr($extralabel, "lobbyist"))
            formatLobbyistNode($node);
        else if (strstr($extralabel, "client"))
            formatLobbyingClientNode($node);
        else if (strstr($extralabel, "supplier"))
            formatSupplierNode($node);
        else
            formatDonorNode($node);
    }
    $link = $edges->addChild('edge');
    $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
    $link->addAttribute("tooltip", $row['DonorClientNm'] . " donated $" . money_format('%i', $row['AmountPaid']) . " to " . $row['RecipientClientNm']);
    $link->addAttribute("tail_node_id", $tail_node_id);
    $link->addAttribute("head_node_id", $head_node_id);
}

