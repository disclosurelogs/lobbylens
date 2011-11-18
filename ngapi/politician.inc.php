<?php
$politicianName = $graphTarget;
$politicianNode = $nodes->addChild('node');
$politicianNode->addAttribute("id","politician-".$politicianName);
$politicianNode->addAttribute("label",str_replace('.',' ',$politicianName));
formatPoliticianNode($politicianNode);
$xml->addChild('name',htmlentities($politicianName));
$portfolios = $dbConn->prepare("
SELECT portfolios.portfolio from (SELECT representative_id,firstname,surname,party,house,division_id,	portfolio
FROM portfolio2representative
INNER JOIN representatives
ON portfolio2representative.representative_id=representatives.id
INNER JOIN portfolios
ON portfolio2representative.portfolio_id=portfolios.id) as portfolios WHERE firstname = ? AND surname = ?
");
$portfolios->execute(explode('.',$politicianName));
foreach ($portfolios->fetchAll() as $row) {
	$portfolio[] = $row['portfolio'];
}
foreach ($portfolio as $pf) { 
	$result = $dbConn->prepare ("SELECT DISTINCT agencyName from contractnotice where agencyName like '%".$pf."%';");

    foreach ($result->fetchAll() as $row) {
  $existing = $nodes->xpath('//node[@id="'."agency-" . $row['agencyName'].'"]');
  $exists = !empty($existing);
        if (!$exists) {
        $node = $nodes->addChild('node');
        $node->addAttribute("id","agency-".$row['agencyName']);
        $node->addAttribute("label",$row['agencyName']);
	formatPoliticianNode($node);
        $link = $edges->addChild('edge');
        $tail_node_id = "politician-".$politicianName;
        $head_node_id = "agency-".$row['agencyName'];
        $link->addAttribute("id", $head_node_id. "|" . $tail_node_id);
        $link->addAttribute("tooltip", str_replace('.',' ',$politicianName). " has a portfolio responsiblity for " . $row['agencyName']);
        $link->addAttribute("tail_node_id",$tail_node_id);
        $link->addAttribute("head_node_id",$head_node_id);
        }
}
}
?>
