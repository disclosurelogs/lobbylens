<?php

$last_updated = Array();
$sections = Array(
    "agency",
    "category",
    "lobbyist",
    "supplier",
    "donationrecipient",
    "lobbyistclient"
);
require_once "libs/config.php";
$result = $dbConn->query('SELECT title, to_char("lastUpdated",\'YYYY-MM-DD\') as "lastUpdated" from datasets');
foreach ($result->fetchAll() as $row) {
    if ($row['title'] == "Contract Notices") {
        $last_updated['agency'] = $row['lastUpdated'];
        $last_updated['supplier'] = $row['lastUpdated'];
        $last_updated['category'] = $row['lastUpdated'];
    }
    if ($row['title'] == "Federal Government Lobbyists Register") {
        $last_updated['lobbyist'] = $row['lastUpdated'];
        $last_updated['lobbyistclient'] = $row['lastUpdated'];
    }
    if ($row['title'] == "Portfolio Responsibilities") {
        $last_updated['politician'] = $row['lastUpdated'];
    }
    if ($row['title'] == "Annual Financial Disclosure Returns (Political Donations) 2004-2009") {
        $last_updated['donationrecipient'] = $row['lastUpdated'];
    }
}
header("Content-Type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
if (isset($_REQUEST['section']) == false) {
    echo '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"
         xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($sections as $section) {
        echo "<sitemap>
      <loc>" . local_url() . "sitemap.xml.php?section=$section</loc>
      <lastmod>" . $last_updated[$section] . '</lastmod></sitemap>';
    }
    echo '</sitemapindex>';
} else {
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    if ($_REQUEST['section'] == "agency") {
       $result = $dbConn->query('SELECT DISTINCT "agencyName" from contractnotice');
       foreach ($result->fetchAll() as $row) {
            echo " <url><loc>" . local_url() . "networkgraph.php?node_id=agency-" . urlencode($row['agencyName']) . "</loc>
      <lastmod>" . $last_updated['agency'] . "</lastmod></url>\n";
        }
    }
    if ($_REQUEST['section'] == "supplier") {
       $result = $dbConn->query('SELECT DISTINCT "supplierABN" from contractnotice');
       foreach ($result->fetchAll() as $row) {
            echo " <url><loc>" . local_url() . "networkgraph.php?node_id=supplier-{$row['supplierABN']}</loc>
      <lastmod>" . $last_updated['supplier'] . "</lastmod></url>\n";
        }
    }
    if ($_REQUEST['section'] == "category") {
       $result = $dbConn->query('SELECT substr( "categoryUNSPSC"::text, 0, 3 ) as cat FROM contractnotice
				GROUP BY cat;');
       foreach ($result->fetchAll() as $row) {
           if ($row['cat'] != "") {
            echo " <url><loc>" . local_url() . "networkgraph.php?node_id=category-{$row['cat']}000000</loc>
      <lastmod>" . $last_updated['supplier'] . "</lastmod></url>\n";
           }
        }
    }
    if ($_REQUEST['section'] == "lobbyist") {
       $result = $dbConn->query('SELECT DISTINCT abn from lobbyists');
       foreach ($result->fetchAll() as $row) {
            echo " <url><loc>" . local_url() . "networkgraph.php?node_id=lobbyist-{$row['abn']}</loc>
      <lastmod>" . $last_updated['lobbyist'] . "</lastmod></url>\n";
        }
    }
    if ($_REQUEST['section'] == "lobbyistclient") {
       $result = $dbConn->query("SELECT DISTINCT business_name from lobbyist_clients");
       foreach ($result->fetchAll() as $row) {
            echo " <url><loc>" . local_url() . "networkgraph.php?node_id=lobbyistclient-" . urlencode($row['business_name']) . "</loc>
      <lastmod>" . $last_updated['lobbyistclient'] . "</lastmod></url>\n";
        }
    }
    if ($_REQUEST['section'] == "donationrecipient") {
       $result = $dbConn->query('select distinct "RecipientClientNm" from political_donations ');
       foreach ($result->fetchAll() as $row) {
            echo " <url><loc>" . local_url() . "networkgraph.php?node_id=donationrecipient-" . urlencode($row['RecipientClientNm']) . "</loc>
      <lastmod>" . $last_updated['donationrecipient'] . "</lastmod></url>\n";
        }
    }
    echo '</urlset>';
}
?>
