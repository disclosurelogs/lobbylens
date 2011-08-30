<?php
try {
  if ($_SERVER['SERVER_NAME'] == "localhost") {
    $dbConn = new PDO(
    /*DSN */
    'mysql:host=localhost;dbname=contractDashboard',
    /*USER*/
    'root',
    /*PASS*/
    '', array(
      PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
    ));
  } 
}
catch(PDOException $e) {
  die('Unable to connect to database server.');
}
catch(Exception $e) {
  die('Unknown error in ' . __FILE__ . '.');
}
$dbConn->exec('SET NAMES \'utf8\'');
?>
