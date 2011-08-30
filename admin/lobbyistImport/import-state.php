<?php
  include "../../../libs/config.php";
  include('./spyc/spyc.php');
  createMySQLlink();
  $state = $_REQUEST['state'];
  // lobbyists
  $row = 0;
  $success = 0;
  $file = Spyc::YAMLLoad("lobbyists-$state.yml");
  foreach ($file as $lobbyist) {
      $lobbyistID = 0;
      echo "<h1>{$lobbyist['trading_name']}</h1>";
      $query = "SELECT lobbyistID from lobbyists where trading_name = '" . mysql_real_escape_string($lobbyist['trading_name']) . "' OR abn = " . (float)str_replace(" ", "", $lobbyist["abn"]) . ";";
      $existresult = mysql_query($query);
      if (mysql_num_rows($existresult) == 0) {
          $query = "INSERT INTO lobbyists (business_name, trading_name, abn, $state) VALUES ('" . mysql_real_escape_string($lobbyist['business_name']) . "','" . mysql_real_escape_string($lobbyist['trading_name']) . "', " . (float)str_replace(" ", "", $lobbyist["abn"]) . ",'True');";
          $result = mysql_query($query);
          $lobbyistID;
          if ($result && $lobbyistID = mysql_insert_id($result)) {
              $success++;
          } else
              echo $query . " failed insert.<br>" . mysql_error($result);
      } else {
          $row = mysql_fetch_row($existresult);
          echo "exists @ ID: " . $row[0] . "<br>";
          $lobbyistID = $row[0];
          $query = "UPDATE lobbyists SET $state='True' WHERE lobbyistID = {$row[0]};";
          $result = mysql_query($query);
          if ($result)
              $success++;
          else
              echo $query . " failed lobby state set.<br>" . mysql_error();
      }
      if (sizeof($lobbyist["clients:"]) == 0)
          echo("lobbyist {$lobbyist['business_name']} has no clients; no further import required?");
      if ($lobbyistID === 0)
          die("lobbyist DB ID == 0, terminating import");
      // clients
      foreach ($lobbyist["clients:"] as $client) {
          if (is_array($client))
              $business_name = trim($client["name"]);
          else
              $business_name = trim($client);
          if (strpos($business_name, "wqA=") === false && $business_name != "") {
              $abn = 0;
              $cleanseNames = array("Ltd", "\xE2\x80\x93", "\xE2\x80\x99", "Pty", "Limited", "Corporation", "Australiasia", "The ", "S.p.A", "SpA");
              $searchName = str_replace($cleanseNames, "", $business_name);
              echo "client: $business_name (searched as '$searchName')<br>";
              flush();
              // search for existing abn via name
              $query = "SELECT lobbyistClientID, abn from lobbyist_clients where business_name = '" . mysql_real_escape_string($business_name) . "' OR business_name LIKE '%" . mysql_real_escape_string($searchName) . "%';";
              $existresult = mysql_query($query);
              $clientID = 0;
              $abn = 0;
              if (mysql_num_rows($existresult) == 0) {
                  // if name did not match.
                  $query = "SELECT supplierABN from supplierDetails where supplierName LIKE '%" . mysql_real_escape_string($searchName) . "%';";
                  $result = mysql_query($query);
                  if ($result) {
                      $row = mysql_fetch_row($result);
                      if ($row[1] > 0)
                          $abn = $row[1];
                  }
                  if ($abn == 0) {
                      $abn = abnLookup($business_name);
                  }
              } else {
                  // found ABN or clientID
                  $row = mysql_fetch_row($existresult);
                  $clientID = $row[0];
                  $abn = $row[1];
              }
              if ($clientID == 0) {
                  // search for existing clientID
                  $query = "SELECT lobbyistClientID from lobbyist_clients where abn = $abn;";
                  $existIDresult = mysql_query($query);
                  if (mysql_num_rows($existIDresult) == 0) {
                      $query = "INSERT INTO lobbyist_clients (business_name, abn, $state)
          VALUES (\"$business_name\"," . (float)$abn . ",'True');";
                      $result = mysql_query($query);
                      if (!$result) {
                          echo $query . " failed insert.<br>" . mysql_error();
                      } else {
                          $clientID = mysql_insert_id();
                          echo "is new client #$clientID <br>";
                      }
                  } else {
                      $row = mysql_fetch_array($existIDresult);
                      $clientID = $row[0];
                  }
              }
              echo "exists @ ID: " . $clientID . "<br>";
              
              $query = "UPDATE lobbyist_clients  SET $state='True' WHERE lobbyistClientID = $clientID;";
              $result = mysql_query($query);
              if ($result)
                  $success++;
              else
                  echo $query . " failed client state update.<br>" . mysql_error();
              
              if ($clientID == 0 and $abn == 0) {
                  echo "<br><b>Manual intervention required for $business_name in relationship with lobbyist $lobbyistID</b><br>";
              } else {
                  $query = "INSERT INTO lobbyist_relationships (lobbyistID, lobbyistClientID)
        VALUES ($lobbyistID,$clientID);";
                  $result = mysql_query($query);
                  if ($result)
                      $success++;
                  else {
                      if (strpos(mysql_error(), "Duplicate entry") === false)
                          echo $query . " failed relationship insert.<br>" . mysql_error();
                  }
              }
              flush();
          }
      }
      echo $success . " lobbyist clients inserted<br><hr>";
  }
?>

