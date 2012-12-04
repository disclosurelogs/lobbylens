<?php

include "../libs/config.php";
include '../libs/Yaml/Yaml.php';
include '../libs/Yaml/Parser.php';
include '../libs/Yaml/Inline.php';
include '../libs/Yaml/Dumper.php';
include '../libs/Yaml/Escaper.php';
include '../libs/Yaml/Unescaper.php';

use Symfony\Component\Yaml\Yaml;

$dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

$state_datasets = Array(
    "SA" => "South Australian Lobbyist Register",
    "WA" => "Western Australian Lobbyist Register",
    "VIC" => "Victorian Lobbyist Register",
    "TAS" => "Tasmanian Lobbyist Register",
    "QLD" => "Queensland Lobbyist Register",
    "NSW" => "New South Wales Lobbyist Register"
);
$state_urls = Array(
    //"SA" => '',
    "WA" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-wa-register-of-lobbyists&query=select+*+from+`swdata`&apikey=',
    "VIC" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-vic-register-of-lobbyists&query=select+*+from+`swdata`&apikey=',
    "TAS" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-tas-register-of-lobbyists&query=select+*+from+`swdata`&apikey=',
    "QLD" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-qld-register-of-lobbyists&query=select+*+from+`swdata`&apikey=',
    "NSW" => 'https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-nsw-register-of-lobbyists&query=select+*+from+`swdata`&apikey='
);

function add_lobbyist($state, $abn, $business_name, $trading_name) {
    global $dbConn;
    $abn = str_replace(Array(" ", "-", "N/A"), "", $abn);
    if ($abn == "")
        $abn = NULL;
    $lobbyistID = find_lobbyist($abn, $business_name, $trading_name);

    if ($lobbyistID == NULL) {
        echo "not found, insert new record <Br>\n";

        $lobins = $dbConn->prepare('INSERT INTO lobbyists (business_name, trading_name, abn, ' . $state . ')
        VALUES (?,?,?,\'True\') RETURNING "lobbyistID";');
        $lobins->execute(Array($business_name, $trading_name, $abn));
        $err = $dbConn->errorInfo();
        if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
            echo " failed lobbyist insert.<br>";
            print_r($err);
            die();
        } else {
            $result = $lobins->fetch(PDO::FETCH_ASSOC);

            echo "is new client #" . $result['lobbyistID'] . " <br> \n";
            set_time_limit(30);
            $lobbyistID = $result['lobbyistID'];
        }
    } else {
        echo "exists @ ID: " . $lobbyistID . "<br> \n";

        $lobins = $dbConn->prepare('UPDATE lobbyists SET ' . $state . '=\'True\' WHERE "lobbyistID" = ?;');
        $lobins->execute(Array($lobbyistID));
        $err = $dbConn->errorInfo();
        if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
            echo $query . " failed relation insert.<br>\n";
            print_r($err);
            die();
        } else {
            echo ".";
            set_time_limit(30);
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
    //echo $findlobbyist->rowCount() . " rows found <br>\n";
    if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
        echo " failed lobbyist search.<br>\n";
        print_r($err);
        die();
    } else {
        if ($findlobbyist->rowCount() == 0) {
            echo "not found <Br>\n";
            $findlobbyistalias = $dbConn->prepare('SELECT "lobbyistID" from lobbyists_aliases where alias = ? OR alias = ?;');
            $findlobbyistalias->execute(Array($business_name, $trading_name));
            if ($findlobbyistalias->rowCount() == 0) {
                echo "not found alias <Br>\n";
                return NULL;
            } else {
                $lobbyist = $findlobbyistalias->fetch(PDO :: FETCH_ASSOC);
                //print_r($lobbyist);
                echo "found alias " . $lobbyist['lobbyistID'] . " <br>\n";
                set_time_limit(30);
                return $lobbyist['lobbyistID'];
            }
        } else {
            $lobbyist = $findlobbyist->fetch(PDO :: FETCH_ASSOC);
            //print_r($lobbyist);
            echo "found " . $lobbyist['lobbyistID'] . " <br>\n";
            set_time_limit(30);
            return $lobbyist['lobbyistID'];
        }
    }
}

function find_lobbyist_by_name($name) {
    global $dbConn;
    echo "looking for lobbyist $name <br> \n";

    $findlobbyist = $dbConn->prepare('SELECT "lobbyistID" from lobbyists where business_name = ? OR trading_name = ?;');
    $findlobbyist->execute(Array($name, $name));

    $err = $dbConn->errorInfo();
    //echo $findlobbyist->rowCount() . " rows found <br>\n";
    if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
        echo $query . " failed searcn.<br>\n";
        print_r($err);
        die();
    } else {
        if ($findlobbyist->rowCount() == 0) {
            echo "not found <Br>\n";
            $findlobbyistalias = $dbConn->prepare('SELECT "lobbyistID" from lobbyists_aliases where alias = ?;');
            $findlobbyistalias->execute(Array($name));
            if ($findlobbyistalias->rowCount() == 0) {
                echo "not found alias <Br>\n";
                return NULL;
            } else {
                $lobbyist = $findlobbyistalias->fetch(PDO :: FETCH_ASSOC);
                //print_r($lobbyist);
                echo "found alias " . $lobbyist['lobbyistID'] . " <br>\n";
                set_time_limit(30);
                return $lobbyist['lobbyistID'];
            }
        } else {
            $lobbyist = $findlobbyist->fetch(PDO :: FETCH_ASSOC);
            //print_r($lobbyist);
            echo "found " . $lobbyist['lobbyistID'] . " <br>\n";
            set_time_limit(30);
            return $lobbyist['lobbyistID'];
        }
    }
}

function add_client_alias($lobbyistClientID, $clientName) {
    global $dbConn;
    $insclient = $dbConn->prepare('INSERT INTO lobbyist_clients_aliases ("lobbyistClientID", alias)
          VALUES (?,?);');
    $insclient->bindParam(1, $lobbyistClientID);
    $insclient->bindParam(2, $clientName);
    $insclient->execute();
    $err = $dbConn->errorInfo();
    if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
        echo " failed client insert.<br>\n";
        print_r($err);
        die();
    }
}

function add_client($state, $clientName) {
    global $dbConn;
    $alias = false;
    $searchName = "%" . cleanseName($clientName) . "%";
    echo "client: $clientName (searched as '$searchName')<br>\n";
    flush();
    // search for existing abn via name

    $findclient = $dbConn->prepare('SELECT "lobbyistClientID", "ABN" from lobbyist_clients where business_name = ? OR business_name LIKE ?');
    $findclient->execute(Array($clientName, $searchName));
    $err = $dbConn->errorInfo();
    if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
        echo " find client err<br>\n";
        print_r($err);
        die();
    }
    $clientID = 0;
    $abn = 0;
    if ($findclient->rowCount() == 0) {
        // check cached aliases
        $findclientalias = $dbConn->prepare('SELECT "lobbyistClientID" from lobbyist_clients_aliases where alias = ?');
        $findclientalias->execute(Array($clientName));
        $err = $dbConn->errorInfo();
        if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
            echo " find client alias err<br>\n";
            print_r($err);
            die();
        }
        if ($findclientalias->rowCount() == 0) {
            // if name still did not match.
            $findsupplier = $dbConn->prepare('SELECT "supplierName", "supplierABN" from supplierDetails where "supplierName" LIKE ?;');
            $findsupplier->execute(Array($searchName));
            $err = $dbConn->errorInfo();
            if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
                echo "find supplier err<br>\n";
                print_r($err);
                die();
            }
            if ($findsupplier->rowCount() != 0) {
                $row = $findsupplier->fetch(PDO :: FETCH_ASSOC);
                if ($row['supplierABN'] != null && $row['supplierABN'] > 0) {
                    $abn = $row['supplierABN'];
                }
                $alias = true;
            }
            if ($abn == 0) {
                echo "lookup ABN online ";
                //set_time_limit(30);
                $abn = abnLookup($clientName);
            }
            $findclientbyABN = $dbConn->prepare('SELECT "lobbyistClientID" from lobbyist_clients where "ABN" = ?;');
            $findclientbyABN->execute(Array($abn));
            $err = $dbConn->errorInfo();
            if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
                echo " rfind client by abn err<br>\n";
                print_r($err);
                die();
            }
            if ($findclientbyABN->rowCount() != 0) {
                $row = $findclientbyABN->fetch(PDO :: FETCH_ASSOC);
                $clientID = $row['lobbyistClientID'];
                $alias = true;
            }
        } else {
            echo "found client via alias";
            $row = $findclientalias->fetch(PDO :: FETCH_ASSOC);
            $clientID = $row['lobbyistClientID'];
            //$abn = $row['ABN'];
            $alias = false;
        }
    } else {
        // found ABN or clientID
        $row = $findclient->fetch(PDO :: FETCH_ASSOC);
        $clientID = $row['lobbyistClientID'];
        $abn = $row['ABN'];
        $alias = false;
    }

    if ($clientID == 0) {
        // insert new client
        echo "$clientName, $abn";
        $insclient = $dbConn->prepare('INSERT INTO lobbyist_clients (business_name, "ABN", ' . $state . ')
          VALUES (?,?,\'True\') RETURNING "lobbyistClientID";');
        $insclient->bindParam(1, $clientName);
        $insclient->bindParam(2, $abn);
        $insclient->execute();
        $err = $dbConn->errorInfo();
        if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
            echo " failed client insert.<br>\n";
            print_r($err);
            die();
        } else {
            $result = $insclient->fetch(PDO::FETCH_ASSOC);

            echo "is new client #" . $result['lobbyistClientID'] . " <br>";
            set_time_limit(30);
            return $result['lobbyistClientID'];
        }
    } else {
        echo "exists @ ID: " . $clientID . "<br>\n";
        if ($alias) {
            add_client_alias($clientID, $clientName);
        }
        return $clientID;
    }
}

function add_relationship($state, $lobbyistID, $clientID) {
    global $dbConn;
    $stateupdate = $dbConn->prepare('UPDATE lobbyist_clients  SET ' . $state . ' =\'True\' WHERE "lobbyistClientID" = ?;');
    $stateupdate->execute(Array($clientID));
    $err = $dbConn->errorInfo();
    if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
        echo " failed client state update.<br>\n";
        print_r($err);
        die();
    } else {
        echo ".";
        set_time_limit(30);
    }


    if ($clientID == 0 || $lobbyistID == 0) {
        echo "<br><b>Manual intervention required for client $clientID in relationship with lobbyist $lobbyistID</b><br>";
    } else {

        $relupdate = $dbConn->prepare('INSERT INTO lobbyist_relationships ("lobbyistID", "lobbyistClientID")
        VALUES (?,?);');
        $relupdate->execute(Array($lobbyistID, $clientID));
        $err = $dbConn->errorInfo();
        if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
            echo " failed relation insert.<br>\n";
            print_r($err);
            die();
        } else {
            echo ".";
            set_time_limit(30);
        }
    }
}

// federal parser
$datasetName = "Federal Government Lobbyists Register";
//$lobbyist_clients = json_decode(file_get_contents("clients.json"));
//$lobbyists = json_decode(file_get_contents("lobbyists.json"));
/* foreach ($lobbyists as $lobbyist) {
  $lobbyist_clients = json_decode(getPage('https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=australian-government-register-of-lobbyists&query=select+*+from+`lobbyist_clients`&apikey='));
  $lobbyists = json_decode(getPage('https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=australian-government-register-of-lobbyists&query=select+*+from+`lobbyists`&apikey='));

  $lobbyistID = add_lobbyist("federal", str_replace(" ", "", $lobbyist->abn), $lobbyist->business_entity_name, $lobbyist->trading_name);
  }

  foreach ($lobbyist_clients as $lobbyist_client) {
  $lobbyistID = find_lobbyist_by_name($lobbyist_client->lobbyist_name);
  $clientID = add_client("federal", $lobbyist_client->client_name);

  add_relationship("federal", $lobbyistID, $clientID);
  }
  die(); */
// state parsers
foreach ($state_urls as $state => $url) {
    if ($url == "") {
        echo "Skipping $state due to no URL<br>\n";
        continue;
    } else {
        $lobbyists = json_decode(getPage($url));
        foreach ($lobbyists as $lobbyist) {
            $abn = str_replace(Array("No A.B.N", "tba", "ACN"), "", $lobbyist->abn);
            $lobbyistID = add_lobbyist(strtolower($state), $abn, $lobbyist->business_name, $lobbyist->trading_name);
            //print_r($lobbyist->clients);
            $clients = Yaml::parse(str_replace("--- ", "", $lobbyist->clients));
            foreach ($clients as $client) {
                //echo $client;
                if (is_array($client)) {
                    $clientID = add_client(strtolower($state), $client['name']);
                } else {
                    $clientID = add_client(strtolower($state), $client);
                }
                add_relationship(strtolower($state), $lobbyistID, $clientID);
            }
        }
    }
}
?>
