<?php
include "../../libs/config.php";
/* todo
  take out id-
  remove gov node
  cleanse names of Australian, "of", pty ltd etc.
  add more parties. greens, democrats
  fix colors throughout workflow
*/
//error_reporting(0);
header("Content-Type: text/xml");
$xml = simplexml_load_string('<graph_data></graph_data>');
$nodes = $xml->addChild('nodes');
$edges = $xml->addChild('edges');

function formatLaborNode($node) {
  $node->addAttribute("shape", "circle");
  $node->addAttribute("label_bg_line_color", "#0000FF");
  $node->addAttribute("graphic_fill_color", "#0000FF");
  $node->addAttribute("graphic_line_color", "#0000FF");
}

function formatLiberalNode($node) {
  $node->addAttribute("shape", "circle");
  $node->addAttribute("label_bg_line_color", "#EE0000");
  $node->addAttribute("graphic_fill_color", "#EE0000");
  $node->addAttribute("graphic_line_color", "#EE0000");
}

function formatNationalNode($node) {
  $node->addAttribute("shape", "circle");
  $node->addAttribute("label_bg_line_color", "#FFEE00");
  $node->addAttribute("graphic_fill_color", "#FFEE00");
  $node->addAttribute("graphic_line_color", "#FFEE00");
}

function formatOtherPartyNode($node) {
  $node->addAttribute("shape", "circle");
  $node->addAttribute("label_bg_line_color", "#FAE100");
  $node->addAttribute("graphic_fill_color", "#FAE100");
  $node->addAttribute("graphic_line_color", "#FAE100");
}

function formatDonorNode($node) {
  $node->addAttribute("shape", "circle");
  $node->addAttribute("label_bg_line_color", "#FAE100");
  $node->addAttribute("graphic_fill_color", "#FAE100");
  $node->addAttribute("graphic_line_color", "#FAE100");
}
$node = $nodes->addChild('node');
$node->addAttribute("id", "gov");
$node->addAttribute("label", "Government in Australia");
formatLaborNode($node);
$node = $nodes->addChild('node');
$node->addAttribute("id", "party-labor");
$node->addAttribute("label", "Labor Party");
formatLaborNode($node);
$tail_node_id = "party-labor";
$link = $edges->addChild('edge');
$link->addAttribute("id", "gov|" . $tail_node_id);
$link->addAttribute("tail_node_id", $tail_node_id);
$link->addAttribute("head_node_id", "gov");
$node = $nodes->addChild('node');
$node->addAttribute("id", "party-liberal");
$node->addAttribute("label", "Liberal Party");
formatLiberalNode($node);
$tail_node_id = "party-liberal";
$link = $edges->addChild('edge');
$link->addAttribute("id", "gov|" . $tail_node_id);
$link->addAttribute("tail_node_id", $tail_node_id);
$link->addAttribute("head_node_id", "gov");
$node = $nodes->addChild('node');
$node->addAttribute("id", "party-national");
$node->addAttribute("label", "National Party");
formatNationalNode($node);
$tail_node_id = "party-national";
$link = $edges->addChild('edge');
$link->addAttribute("id", "gov|" . $tail_node_id);
$link->addAttribute("tail_node_id", $tail_node_id);
$link->addAttribute("head_node_id", "gov");

function addDonationRecipientNode($name, $party, $value) {
  global $nodes;
  global $edges;
  global $largestRecipient;
  global $totalDonations;

if ($party != "other") {
  $head_node_id = "donationrecipient-" . $name;
  $tail_node_id = "";
  $node = $nodes->addChild('node');
  $node->addAttribute("id", $head_node_id);
  $node->addAttribute("label", "Donation Recipient: " . $name);
  $node->addAttribute("weight", (float)$value / $largestRecipient);
  $tail_node_id = "";
  if ($party == "labor") {
    formatLaborNode($node);
    $tail_node_id = "party-labor";
  }
  if ($party == "liberal") {
    formatLiberalNode($node);
    $tail_node_id = "party-liberal";
  }
  if ($party == "national") {
    formatNationalNode($node);
    $tail_node_id = "party-national";
  }
  if ($party == "other") {
    $tail_node_id = "gov";
  }
  $link = $edges->addChild('edge');
  $link->addAttribute("id", $head_node_id . "|" . $tail_node_id);
  //  $link->addAttribute("tooltip", );
  $link->addAttribute("tail_node_id", $tail_node_id);
  $link->addAttribute("head_node_id", $head_node_id);
  $link->addAttribute("weight", (float)$value / $totalDonations);
}
}

function addDonorNode($name, $value) {
  global $nodes;
  global $largestDonor;
  $node = $nodes->addChild('node');
  $node->addAttribute("id", "donor-" . $name);
  $node->addAttribute("label", "Donor: " . $name);
  $node->addAttribute("weight", (float)$value / $largestDonor);
}

function addDonationLink($donor, $recipient, $value) {
  global $edges;
  global $largestDonation;
  $link = $edges->addChild('edge');
  $link->addAttribute("id", "donor-$donor | donationrecipient-$recipient");
  $link->addAttribute("tooltip", $value);
  $link->addAttribute("tail_node_id", "donor-$donor");
  $link->addAttribute("head_node_id", "donationrecipient-$recipient");
  $link->addAttribute("weight", (float)$value / $largestDonation);
}
$totalDonations = 125035725; // select sum(AmountPaid) from political_donations
$largestDonation = 1000000; //  select AmountPaid from political_donations order by AmountPaid desc limit 1
$largestDonor = 3900000; // select sum(AmountPaid) as total from political_donations group by DonorClientNm order by total desc limit 1
$largestRecipient = 20010662; // select sum(AmountPaid) as total from political_donations group by RecipientClientNm order by total desc limit 1
createMySQLlink();
$result = mysql_query("select RecipientClientNm, sum(AmountPaid) as AmountPaid from political_donations group by RecipientClientNm");
if ($result) {
  while ($row = mysql_fetch_array($result)) {
    if (strpos($row['RecipientClientNm'], "Labor") !== false && strpos($row['RecipientClientNm'], "Democratic") === false) $party = "labor";
    else if (strpos($row['RecipientClientNm'], "Liberal") !== false) $party = "liberal";
    else if (strpos($row['RecipientClientNm'], "National") !== false) $party = "national";
    else $party = "other";
    addDonationRecipientNode($row['RecipientClientNm'], $party, $row['AmountPaid']);
  }
}
$result = mysql_query("select DonorClientNm,RecipientClientNm,DonationDt,sum(AmountPaid) as AmountPaid
                        from political_donations group by DonorClientNm");
if ($result) {
  while ($row = mysql_fetch_array($result)) {
    addDonorNode($row['DonorClientNm'], $row['AmountPaid']);
  }
}
$result = mysql_query("select DonorClientNm,RecipientClientNm,DonationDt,sum(AmountPaid) as AmountPaid
                        from political_donations group by DonorClientNm, RecipientClientNm");
if ($result) {
  while ($row = mysql_fetch_array($result)) {
    addDonationLink($row['DonorClientNm'], $row['RecipientClientNm'], $row['AmountPaid']);
  }
}
$dom = dom_import_simplexml($xml)->ownerDocument;
$dom->formatOutput = true;
echo $dom->saveXML();
?>
