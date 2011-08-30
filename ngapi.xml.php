<?php
include "libs/config.php";
error_reporting(1);
setlocale(LC_MONETARY, 'en_AU');
$nodeID = $_REQUEST['node_id'];
$details = $_REQUEST['details'] != "" ;
if ($nodeID == "" || $nodeID == "[node_id]") {
?>
Network Graph API supports the following central node types:
"agency"
"supplier"
"category"
"politician"
"lobbyist"
"lobbyistclient"
"donationrecipient"
<?php
	die();
} 
header("Content-Type: text/xml; charset=utf-8");
$xml = simplexml_load_string('<?xml-stylesheet type="text/xsl" href="networkgraph.xsl"?><graph_data></graph_data>');
$nodes = $xml->addChild('nodes');
$edges = $xml->addChild('edges');
$dev = false;

function formatSupplierNode($node) {
  $node->addAttribute("shape", "circle");
  $node->addAttribute("label_bg_line_color", "#FAE100");
  $node->addAttribute("graphic_fill_color", "#FAE100");
  $node->addAttribute("graphic_line_color", "#FAE100");
}

function formatAgencyNode($node) {
  $node->addAttribute("shape", "square");
  $node->addAttribute("label_bg_line_color", "#53A639");
  $node->addAttribute("graphic_fill_color", "#53A639");
  $node->addAttribute("graphic_line_color", "#53A639");
}

function formatCategoryNode($node) {
  $node->addAttribute("shape", "triangle");
  $node->addAttribute("label_bg_line_color", "#3E8FAA");
  $node->addAttribute("graphic_fill_color", "#3E8FAA");
  $node->addAttribute("graphic_line_color", "#3E8FAA");
}

function formatPostcodeNode($node) {
  $node->addAttribute("shape", "rectangle");
  $node->addAttribute("label_bg_line_color", "#ED0404");
  $node->addAttribute("graphic_fill_color", "#ED0404");
  $node->addAttribute("graphic_line_color", "#ED0404");
}

function formatPoliticianNode($node) {
  $node->addAttribute("shape", "rectangle");
  $node->addAttribute("label_bg_line_color", "#68447F");
  $node->addAttribute("graphic_fill_color", "#68447F");
  $node->addAttribute("graphic_line_color", "#68447F");
}

function formatLobbyistNode($node) {
  $node->addAttribute("shape", "rectangle");
  $node->addAttribute("label_bg_line_color", "#EB6119");
  $node->addAttribute("graphic_fill_color", "#EB6119");
  $node->addAttribute("graphic_line_color", "#EB6119");
}


function formatLobbyingClientNode($node) {
  $node->addAttribute("shape", "rectangle");
  $node->addAttribute("label_bg_line_color", "#EB6119");
  $node->addAttribute("graphic_fill_color", "#EB6119");
  $node->addAttribute("graphic_line_color", "#EB6119");
}

function formatDonorNode($node) {
  $node->addAttribute("shape", "rectangle");
  $node->addAttribute("label_bg_line_color", "#EB6119");
  $node->addAttribute("graphic_fill_color", "#EB6119");
  $node->addAttribute("graphic_line_color", "#EB6119");
}

function formatDonationRecipientNode($node) {
  $node->addAttribute("shape", "rectangle");
  $node->addAttribute("label_bg_line_color", "#EB6119");
  $node->addAttribute("graphic_fill_color", "#EB6119");
  $node->addAttribute("graphic_line_color", "#EB6119");
}

function appendNode($nodeID) {
  global $dbConn;
  global $nodes;
  global $edges;
  global $linkID;
  global $details;
  global $dev;
  global $xml;
  $categoriesEnabled = true;
  $postcodesEnabled = false;
  $lobbyistsEnabled = true;
  $politicialDonationsEnabled = true;
  $path = "ngapi/";
  //if ($dev) $path = "ngapi-dev/";
  $node = explode("-", $nodeID);
  $graphType = array_shift($node);
  $graphTarget = htmlspecialchars_decode(implode("-",$node));
  createMySQLlink();
  if ($graphType == "agency") {
    include ($path . 'agency.inc.php');
  }
  if ($graphType == "lobbyistclient" && $lobbyistsEnabled) {
	  $result = mysql_query("SELECT supplierABN
	FROM `contractnotice`
	WHERE supplierName LIKE \"%" . $graphTarget . "%\"
	LIMIT 1 ");
  if (mysql_num_rows($result) > 0) {
    $abn = mysql_fetch_assoc($result);
    $graphType = "supplier";
    $graphTarget = $abn['supplierABN'];
  } else {
    include ($path . 'lobbyistclient.inc.php');
  }
  }
  if ($graphType == "supplier") {
    include ($path . 'supplier.inc.php');
  }
  if ($graphType == "category" && $categoriesEnabled) {
    include ($path . 'category.inc.php');
  }
  if ($graphType == "politician") {
    include ($path . 'politician.inc.php');
  }
  if ($graphType == "lobbyist" && $lobbyistsEnabled) {
    include ($path . 'lobbyist.inc.php');
  }
  if ($graphType == "postcode" && $postcodesEnabled) {
    include ($path . 'postcode.inc.php');
  }
  if ($graphType == "donationrecipient" && $politicialDonationsEnabled) {
    include ($path . 'donationrecipient.inc.php');
  }
}
$depth = $_REQUEST['depth'];
$nodeID = $_REQUEST['node_id'];
if ($argc > 1) $nodeID = $argv[1];
$dev = ($_REQUEST['dev'] == "yes");
if ($argv[2] == "dev") $dev = true;
if ($nodeID == "" || $nodeID == "[node_id]") {
  die("bad URL" . $nodeID);
} else {
  appendNode($nodeID);
}
$dom = dom_import_simplexml($xml)->ownerDocument;
$dom->formatOutput = true;
echo $dom->saveXML();
?>
