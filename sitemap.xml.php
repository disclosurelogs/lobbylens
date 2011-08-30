<?php
$last_updated = Array();
$sections = Array(
  "agency",
  "category",
  "lobbyist",
  "politician",
  "supplier",
  "donationrecipient",
  "lobbyistclient"
);
require_once "libs/config.php";
createMySQLlink();
$result = mysql_query("SELECT title, DATE_FORMAT(lastUpdated,'%Y-%m-%d') as lastUpdated from datasets");
while ($row = mysql_fetch_array($result)) {
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
  foreach($sections as $section) {
    echo "<sitemap>
      <loc>".local_url()."sitemap.xml.php?section=$section</loc>
      <lastmod>" . $last_updated[$section] . '</lastmod></sitemap>';
  }
  echo '</sitemapindex>';
} else {
  echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
  if ($_REQUEST['section'] == "agency") {
    $result = mysql_query("SELECT DISTINCT agencyName from contractnotice");
    while ($row = mysql_fetch_array($result)) {
      echo " <url><loc>".local_url()."networkgraph.php?node_id=agency-".htmlspecialchars ($row['agencyName'])."</loc>
      <lastmod>" . $last_updated['agency'] . "</lastmod></url>\n";
    }
  }
  if ($_REQUEST['section'] == "supplier") {
    $result = mysql_query("SELECT DISTINCT supplierABN from contractnotice");
    while ($row = mysql_fetch_array($result)) {
      echo " <url><loc>".local_url()."networkgraph.php?node_id=supplier-{$row['supplierABN']}</loc>
      <lastmod>" . $last_updated['supplier'] . "</lastmod></url>\n";
    }
  }
  if ($_REQUEST['section'] == "category") {
    $result = mysql_query('SELECT LEFT( categoryUNSPSC, 2 ) as cat FROM `contractnotice`
				GROUP BY cat;');
    while ($row = mysql_fetch_array($result)) {
      echo " <url><loc>".local_url()."networkgraph.php?node_id=category-{$row['cat']}000000</loc>
      <lastmod>" . $last_updated['supplier'] . "</lastmod></url>\n";
    }
  }
  if ($_REQUEST['section'] == "lobbyist") {
    $result = mysql_query("SELECT DISTINCT abn from lobbyists");
    while ($row = mysql_fetch_array($result)) {
      echo " <url><loc>".local_url()."networkgraph.php?node_id=lobbyist-{$row['abn']}</loc>
      <lastmod>" . $last_updated['lobbyist'] . "</lastmod></url>\n";
    }
  }
  if ($_REQUEST['section'] == "lobbyistclient") {
    $result = mysql_query("SELECT DISTINCT business_name from lobbyist_clients");
    while ($row = mysql_fetch_array($result)) {
      echo " <url><loc>".local_url()."networkgraph.php?node_id=lobbyistclient-".htmlspecialchars ($row['business_name'])."</loc>
      <lastmod>" . $last_updated['lobbyistclient'] . "</lastmod></url>\n";
    }
  }
  if ($_REQUEST['section'] == "politician") {
    $result = mysql_query("SELECT distinct concat(concat(firstname,'.'), surname) as name
FROM portfolio2representative
INNER JOIN representatives ON portfolio2representative.representative_id = representatives.id
INNER JOIN portfolios ON portfolio2representative.portfolio_id = portfolios.id");
    while ($row = mysql_fetch_array($result)) {
      echo " <url><loc>".local_url()."networkgraph.php?node_id=politician-{$row['name']}</loc>
      <lastmod>" . $last_updated['politician'] . "</lastmod></url>\n";
    }
  }
  if ($_REQUEST['section'] == "donationrecipient") {
    $result = mysql_query("select distinct RecipientClientNm from political_donations ");
    while ($row = mysql_fetch_array($result)) {
      echo " <url><loc>".local_url()."networkgraph.php?node_id=donationrecipient-".htmlentities($row['RecipientClientNm'])."</loc>
      <lastmod>" . $last_updated['donationrecipient'] . "</lastmod></url>\n";
    }
  }
  echo '</urlset>';
}
?>
