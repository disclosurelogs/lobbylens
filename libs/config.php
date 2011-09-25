<?php

date_default_timezone_set("Australia/ACT");
if ($_SERVER['SERVER_NAME'] == "localhost" || !$_SERVER['SERVER_NAME']) {
set_include_path("/var/www/lobbylens/libs/:/var/www/lobbylens/public_html/:" . get_include_path());
} else {
set_include_path("/home/team7/libs/:/home/team7/public_html/:".get_include_path());	
}
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);
// common libs
require_once "dbconn.php";
require_once "wordcloud.php";

function ucsmart($str) {
  $shortWords = Array("The","Pty","Ltd","Inc","Red","Oil","A","An","And","At","For","In"
		      ,"Of","On","Or","The","To","With","Uni", "One", "Box", "Utz");
  $strArray =  explode(" ",preg_replace("/(?<=(?<!:|’s)\W)
            (A|An|And|At|For|In|Of|On|Or|The|To|With)
            (?=\W)/e", 'strtolower("$1")', ucwords(strtolower($str))));
  foreach($strArray as &$word) {
    if (strlen($word) <= 3 && !in_array($word,$shortWords)) $word = strtoupper($word);
  }
  return implode(" ",$strArray);
}

function abnLookup($orgname) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  //curl_setopt($ch, CURLOPT_REFERER, "http://lobbylens.info");
  $guid = "5f2f943e-15f4-4782-8fad-9a0fe83a2f47";
  $url = "http://abr.business.gov.au/ABRXMLSearchRPC/ABRXMLSearch.asmx/ABRSearchByNameSimpleProtocol?name=" . urlencode($orgname) . "&postcode=&legalName=Y&tradingName=Y&NSW=Y&SA=Y&ACT=Y&VIC=Y&WA=Y&NT=Y&QLD=Y&TAS=Y&authenticationGuid=$guid";
  curl_setopt($ch, CURLOPT_URL, $url);
  $body = curl_exec($ch);
  // If it's failing here, maybe you don't have php bindings for curl installed?
  $xml = new SimpleXMLElement($body);
  $result = $xml->response->searchResultsList->searchResultsRecord[0];
  return $result->ABN->identifierValue;
}

function local_url() {
  return "http://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\')."/";
}
function searchName($input) {
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
      $result = str_ireplace($cleanseNames, "", $input);
      return trim($result);
}
function include_header($title = "") {
	header("Content-Type: text/html; charset=UTF-8")
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>LobbyLens<?php if ($title != "") echo " - $title"; ?></title>
<link rel="stylesheet" type="text/css" href="style-screen.css" media="screen" />
<link rel="stylesheet" type="text/css" href="style-print.css" media="print" />
<!-- BEGIN IE ActiveX activation workaround by Chris Benjaminsen -->
<script type="text/javascript">function writeHTML(a){document.write(a)}</script>
<script type="text/javascript" src="javascript:'function writeHTML(a){document.write(a)}'"></script>
<!-- END Workaround -->
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function()
{
  //hide the all of the element with class msg_body
  $(".msg_body").hide();
  //toggle the componenet with class msg_body
  $(".msg_head").click(function()
  {
    $(this).next(".msg_body").slideToggle(600);
  });
});
</script></head>
<body>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? 
"https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-12341040-1");
pageTracker._trackPageview();
} catch(err) {}</script>
<div id="header">
  <h1><a href="http://lobbylens.info">Lobby Lens</a></h1>
</div>
<div id="nav">
  <ul>
    <li><a href="index.php" class="current">home</a></li>
    <li><a href="about.php">about</a></li>
    <li><a href="supplier.php">suppliers</a></li>
    <li><a href="agencyWordCloud.php">agencies</a></li>
    <li><a href="lobbyistWordCloud.php">lobbyists</a></li>
    <li><a href="categories.php">industries</a></li>
  </ul>
</div>
<div id="content">
<?php
}
?>
