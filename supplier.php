<?php
include	("libs/config.php");
include_header();
?>
  <h2>Suppliers</h2>
  <form action="supplierSearch.php" method="post">
    <label for="suppliersearch">Supplier name:
    <input type="text" name="suppliersearch" />
    </label>
    <input type="submit" value="Search" />
  </form>
  <div class="tagCloud">
    <?php

	$suppliercloud = new wordcloud();
	$suppliers = $dbConn->prepare("
		SELECT supplierName, supplierABN, value
		FROM `contractnotice`
		WHERE value > 10000000	
		AND childCN = 0
		GROUP BY supplierABN
		ORDER BY value DESC;
	");
	$suppliers->execute();
	$supplierABNs = Array();
	
	foreach ($suppliers->fetchAll() as $row) {
		$suppliercloud->addWord(ucsmart($row['supplierName']),$row['value']);
		$supplierABNs[ucsmart($row['supplierName'])] = $row['supplierABN'];
	}
	$myCloud = $suppliercloud->showCloud('array');
	if(is_array($myCloud)) {
		echo '<div class="tagCloud">';
		foreach ($myCloud as $key => $value) {
			echo ' <a href="networkgraph.php?node_id=supplier-'.$supplierABNs[$value['word']].'" class="cloud'.$value['sizeRange'].'">'.$value['word'].'</a> &nbsp;';
		}
	}
	
	createMySQLlink();
	
	$unspscresult = mysql_query ("select * from UNSPSCcategories;");
	while ($row = mysql_fetch_assoc($unspscresult)) {
		$unspsc[$row['UNSPSC']] = $row['Title'];
	}
	
?>
  </div>
</div>
</body>
</html>
