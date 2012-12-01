<?php

include "../libs/config.php";
//$dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

$state_datasets = Array(
    "SA" => "South Australian Lobbyist Register",
    "WA" => "Western Australian Lobbyist Register",
    "VIC" => "Victorian Lobbyist Register",
    "TAS" => "Tasmanian Lobbyist Register",
    "QLD" => "Queensland Lobbyist Register",
    "NSW" => "New South Wales Lobbyist Register"
);
$state_urls = Array(
    "SA" => '',
    "WA" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-wa-register-of-lobbyists&query=select+*+from+`swdata`&apikey=',
    "VIC" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-vic-register-of-lobbyists&query=select+*+from+`swdata`&apikey=',
    "TAS" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-tas-register-of-lobbyists&query=select+*+from+`swdata`&apikey=',
    "QLD" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-qld-register-of-lobbyists&query=select+*+from+`swdata`&apikey=',
    "NSW" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-nsw-register-of-lobbyists&query=select+*+from+`swdata`&apikey='
);

function add_lobbyist($state, $abn, $business_name, $trading_name) {
    global $dbConn;
    if ($abn == "")
        $abn = NULL;
    $lobbyistID = find_lobbyist($abn, $business_name, $trading_name);

    if ($lobbyistID == NULL) {
        echo "not found, insert new record <Br>\n";

        $lobins = $dbConn->prepare("INSERT INTO lobbyists (business_name, trading_name, abn, $state)
        VALUES (?,?,?,'True');");
        $lobins->execute(Array($business_name, $trading_name, $abn));
        $err = $dbConn->errorInfo();
        if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
            echo $query . " failed relation insert.<br>";
            print_r($err);
            die();
        } else {
            echo ".";
            set_time_limit(10);
        }
    } else {
        echo "exists @ ID: " . $lobbyistID . "<br>";

        $lobins = $dbConn->prepare('UPDATE lobbyists SET ' . $state . '=\'True\' WHERE "lobbyistID" = ?;');
        $lobins->execute(Array($lobbyistID));
        $err = $dbConn->errorInfo();
        if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
            echo $query . " failed relation insert.<br>";
            print_r($err);
            die();
        } else {
            echo ".";
            set_time_limit(10);
        }
    }
    if ($lobbyistID === 0)
        die("lobbyist DB ID == 0, terminating import");
    return $lobbyistID;
}

function find_lobbyist($abn, $business_name, $trading_name) {
    global $dbConn;
    echo "looking for lobbyist $abn, $business_name, $trading_name <br> \n";
    if ($abn != NULL) {
        $findlobbyist = $dbConn->prepare('SELECT "lobbyistID" from lobbyists where  abn = ? OR business_name = ? OR trading_name = ?;');
        $findlobbyist->execute(Array($abn, $business_name, $trading_name));
    } else {
        $findlobbyist = $dbConn->prepare('SELECT "lobbyistID" from lobbyists where business_name = ? OR trading_name = ?;');
        $findlobbyist->execute(Array($business_name, $trading_name));
    }
    $err = $dbConn->errorInfo();
    echo $findlobbyist->rowCount() . " rows found <br>\n";
    if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
        echo $query . " failed relation insert.<br>";
        print_r($err);
        die();
    } else {
        if ($findlobbyist->rowCount() == 0) {
            echo "not found <Br>\n";
            return NULL;
        } else {
            $lobbyist = $findlobbyist->fetch(PDO :: FETCH_ASSOC);
            print_r($lobbyist);
            echo "found " . $lobbyist['lobbyistID'] . " <br>\n";
            set_time_limit(10);
            return $lobbyist['lobbyistID'];
        }
    }
}

function add_client($lobbyistID, $clientName) {
    $searchName = cleanseName($business_name);
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
          VALUES (\"$business_name\"," . (float) $abn . ",'True');";
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
}

function add_relationship($state, $lobbyistID, $clientID) {
    $stateupdate = $dbConn->prepare("UPDATE lobbyist_clients  SET ?='True' WHERE lobbyistClientID = ?;");
    $stateupdate->execute(Array($state, $clientID));
    $err = $dbConn->errorInfo();
    if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
        echo $query . " failed client state update.<br>";
        print_r($err);
        die();
    } else {
        echo ".";
        set_time_limit(10);
    }


    if ($clientID == 0 and $abn == 0) {
        echo "<br><b>Manual intervention required for client $clientID in relationship with lobbyist $lobbyistID</b><br>";
    } else {

        $relupdate = $dbConn->prepare("INSERT INTO lobbyist_relationships (lobbyistID, lobbyistClientID)
        VALUES (?,?);");
        $relupdate->execute(Array($lobbyistID, $clientID));
        $err = $dbConn->errorInfo();
        if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
            echo $query . " failed relation insert.<br>";
            print_r($err);
            die();
        } else {
            echo ".";
            set_time_limit(10);
        }
    }
}

// federal parser
$datasetName = "Federal Government Lobbyists Register";
$lobbyist_clients = json_decode(file_get_contents("clients.json"));
$lobbyists = json_decode(file_get_contents("lobbyists.json"));
//$lobbyist_clients = json_decode(getPage('https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=australian-government-register-of-lobbyists&query=select+*+from+`lobbyist_clients`&apikey='));
//
//$lobbyists = json_decode(getPage('https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=australian-government-register-of-lobbyists&query=select+*+from+`lobbyists`&apikey='));
foreach ($lobbyists as $lobbyist) {
    $lobbyistID = add_lobbyist("federal", str_replace(" ", "", $lobbyist->abn), $lobbyist->business_entity_name, $lobbyist->trading_name);
}
die();
foreach ($lobbyist_clients as $lobbyist_client) {
    $lobbyistID = find_lobbyist($lobbyist_client->lobbyist_name);
    $clientID = add_client($lobbyistID, $lobbyist_client->client_name);
    add_relationship($lobbyistID, $clientID);
}
// state parsers
foreach ($state_urls as $state => $url) {
    if ($url == "") {
        echo "Skipping $datasetName due to no URL<br>\n";
        continue;
    }
    $lobbyists = json_decode(getPage($url));
    foreach ($lobbyists as $lobbyist) {
        $lobbyistID = add_lobbyist(strtolower($state), $lobbyist->abn, $lobbyist->business_name, $lobbyist->trading_name);
        foreach ($lobbyist->clients as $client) {
            $clientID = add_client($lobbyistID, $client);
            add_relationship(strtolower($state), $lobbyistID, $clientID);
        }
    }
}
?>
