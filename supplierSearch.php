<?php
include ("libs/config.php");
include_header("Supplier Search");
?>
<h2>Suppliers</h2>
<form action="supplierSearch.php" method="post">
    <label for="suppliersearch">Supplier name:
        <input type="text" name="suppliersearch" />
    </label>
    <input type="submit" value="Search" />
</form>
<?php
$suppliersearch = $_REQUEST['suppliersearch'];
if ($argc > 1)
    $suppliersearch = $argv[1];
if ($suppliersearch != "" && strlen($suppliersearch)> 2) {
    $sth = $dbConn->prepare('
    SELECT max("supplierName") as "supplierName", max("supplierABN") as "supplierABN", sum(value) as value
    FROM contractnotice
    WHERE "supplierName" ILIKE ?
    AND "childCN" = 0
    AND "supplierABN" != 0
    GROUP BY "supplierABN"
    ORDER BY max("supplierName") ASC;
    ');
    $suppliersearch = "%$suppliersearch%";
    $sth->bindParam(1, $suppliersearch, PDO::PARAM_STR);
    $sth->execute();
    echo "<ul id='supplierResults'>";
    foreach ($sth->fetchAll() as $row) {
        echo "<li><a class='foo' href=\"networkgraph.php?node_id=supplier-" . $row['supplierABN'] . "\">" . ucsmart($row['supplierName']) . "</a> <small>(ABN: 
" . $row['supplierABN'] . ")</small></li>";
    }
    echo "</ul>";
}
?>
</div>
</div>
</body>
</html>
