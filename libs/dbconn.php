<?php
try {
    $dbConn = new PDO("pgsql:dbname=contractDashboard;user=postgres;password=snmc;host=localhost");
     $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e) {
  die('Unable to connect to database server.');
}
catch(Exception $e) {
  die('Unknown error in ' . __FILE__ . '.');
}
?>
