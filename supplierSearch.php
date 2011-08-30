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
  <?php

	require_once "../libs/config.php";

$suppliersearch = $_REQUEST['suppliersearch'];
if ($argc > 1) $suppliersearch = $argv[1];
if ($suppliersearch != "") {
createMySQLlink();
    $result = mysql_query ("
    SELECT supplierName, supplierABN, value
    FROM `contractnotice`
    WHERE supplierName LIKE '%".$suppliersearch."%'
    AND childCN = 0
    AND supplierABN != 0
    GROUP BY supplierABN
    ORDER BY supplierName ASC;
    ");

	echo "<ul id='supplierResults'>";
    while($row = mysql_fetch_array($result)) {
        echo "<li><a class='foo' href=\"networkgraph.php?node_id=supplier-".$row['supplierABN']."\">".ucsmart($row['supplierName'])."</a> <small>(ABN: 
".$row['supplierABN'].")</small></li>";
    }
	echo "</ul>";
}

?>
</div>
</div>
</body>
</html>
