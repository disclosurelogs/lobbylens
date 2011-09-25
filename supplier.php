<?php
include	("libs/config.php");
include_header("Suppliers");
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
	$suppliers = $dbConn->prepare('
		SELECT min("supplierName") as "supplierName", min("supplierABN") as "supplierABN", sum(value)
		FROM contractnotice WHERE "childCN" = 0             
		GROUP BY "supplierABN" HAVING sum(value) > 10000000	
		ORDER BY sum(value) DESC;
	');
	$suppliers->execute();
	$supplierABNs = Array();
	
	foreach ($suppliers->fetchAll() as $row) {
		$suppliercloud->addWord(ucsmart($row['supplierName']),$row['sum']);
		$supplierABNs[ucsmart($row['supplierName'])] = $row['supplierABN'];
	}
	$myCloud = $suppliercloud->showCloud('array');
	if(is_array($myCloud)) {
		echo '<div class="tagCloud">';
		foreach ($myCloud as $key => $value) {
			echo ' <a href="networkgraph.php?node_id=supplier-'.$supplierABNs[$value['word']].'" class="cloud'.$value['sizeRange'].'">'.$value['word'].'</a> &nbsp;';
		}
	}
	
?>
  </div>
</div>
</body>
</html>
