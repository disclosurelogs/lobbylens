<?php
$recipientName = $graphTarget;
$recipientNode = $nodes->addChild('node');
$recipientNode->addAttribute("id", "donationrecipient-" . $recipientName);
$recipientNode->addAttribute("label", $recipientName);
formatPostcodeNode($recipientNode);
$xml->addChild('name', $recipientName);
if ($politicialDonationsEnabled) {
  $result = mysql_query("select DonorClientNm,RecipientClientNm,DonationDt,sum(AmountPaid) as AmountPaid from political_donations where RecipientClientNm
			       LIKE \"%" . $recipientName . "%\" group by DonorClientNm order by DonorClientNm desc");
  if ($result) {
    $head_node_id = "donationrecipient-" . $recipientName;
    while ($row = mysql_fetch_array($result)) {
      $tail_node_id = "donor-" . $row['DonorClientNm'];
      $extralabel = "";
      $searchName = searchName($row['DonorClientNm']);
      $supplier = mysql_query("SELECT supplierABN
	FROM contractnotice
	WHERE supplierName LIKE \"%" . $searchName . "%\"
	LIMIT 1 ");
      $lobbyclient = mysql_query("SELECT * FROM lobbyist_clients WHERE business_name LIKE \"%" . $searchName . "%\"");
      if ($lobbyclient && mysql_num_rows($lobbyclient) > 0) {
        $abn = mysql_fetch_assoc($lobbyclient);
        $tail_node_id = "lobbyistclient-" . $abn['ABN'];
        $extralabel = " and lobbying client";
      }
      if ($supplier && mysql_num_rows($supplier) > 0) {
        $abn = mysql_fetch_assoc($supplier);
        $tail_node_id = "supplier-" . $abn['supplierABN'];
        $extralabel = " and government supplier";
      }
      $lobbyist = mysql_query("SELECT * FROM lobbyists WHERE (business_name LIKE \"%" . $searchName . "%\" OR trading_name = LIKE \"%" . $searchName . "%\"");
      if ($lobbyist && mysql_num_rows($lobbyist) > 0) {
        $abn = mysql_fetch_assoc($lobbyist);
        $tail_node_id = "lobbyist-" . $abn['ABN'];
        $extralabel = " and lobbyist";
      }
      $exists = false;
      foreach($nodes->node as $node) {
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
        if (strstr($extralabel, "lobbyist")) formatLobbyistNode($node);
        else if (strstr($extralabel, "client")) formatLobbyingClientNode($node);
        else if (strstr($extralabel, "supplier")) formatSupplierNode($node);
        else formatDonorNode($node);
      }
      $link = $edges->addChild('edge');
      $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
      $link->addAttribute("tooltip", $row['DonorClientNm'] . " donated $" . money_format('%i', $row['AmountPaid']) . " to " . $row['RecipientClientNm']);
      $link->addAttribute("tail_node_id", $tail_node_id);
      $link->addAttribute("head_node_id", $head_node_id);
    }
  }
}
