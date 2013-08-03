<?php
include "libs/config.php";

// https://github.com/jadell/neo4jphp
spl_autoload_register(function ($className) {
    $libPath = 'libs/neo4jphp/lib/';
    $classFile = str_replace('\\',DIRECTORY_SEPARATOR,$className).'.php';
    $classPath = $libPath.$classFile;
    if (file_exists($classPath)) {
        require($classPath);
    }
});

$nodes = "";
$edges = "";
$description = "";
$nodeList = Array();

function add_node($node) {
global $nodes,$nodeList;
    if (!in_array($node->getId(),$nodeList)) {
          $nodes.= "<node id='".urlencode($node->getId())."' label=\"".htmlentities($node->getProperty("name"))."\">"
              ."<viz:color b='".rand(0,255)."' g='".rand(0,255)."' r='".rand(0,255)."'/>"
                  ."</node>". PHP_EOL;
        $nodeList[] = $node->getId();
    }
}

function add_edge($rel) {
          global $edges;
          $edges.= "<edge id='".urlencode($rel->getId())."' source='".urlencode($rel->getStartNode()->getId())."' target='".urlencode($rel->getEndNode()->getId())."' />". PHP_EOL;

}

function expandNode($node) {
    global $description;
    $description .= ($description == ""? "" : " and ").$node->getProperty("name");
    add_node($node);

    foreach ($node->getProperties() as $key => $value) {
        // echo "$key: $value\n";
    }
    foreach ($node->getRelationships() as $rel) {
        //echo($rel->getStartNode()->getId()." -> ".$rel->getEndNode()->getId()."<br>");
        add_edge($rel);
        add_node($rel->getStartNode());
        add_node($rel->getEndNode());
    }
}

$ids = (isset($_REQUEST['ids']) ? $_REQUEST['ids'] : "");

// Connecting to the default port 7474 on localhost
$client = new Everyman\Neo4j\Client();
//$client = new Everyman\Neo4j\Client('192.168.1.127');
//print_r($client->getServerInfo());

//https://github.com/jadell/neo4jphp/wiki/Caching
$plugin = new Everyman\Neo4j\Cache\Variable();
$client->getEntityCache()->setCache($plugin);

/*$memcached = new Memcached();
$memcached->addServer('localhost', 11211);

$plugin = new Everyman\Neo4j\Cache\Memcached($memcached);
$client->getEntityCache()->setCache($plugin);*/

$requests = explode(";",$ids);


foreach ($requests as $request) {
    /*Array("type" => "node", id=>"100", "options" => Array()),
    Array("type" => "node", id=>"101", "options" => Array()),*/
    // Array("type" => "path", from=>"1234", to=>"4321","options" => Array())

    $parts = explode("-",$request);

    $requestType = $parts[0];
    $requestId = $parts[1];
    if ($requestType == 'node') {
        expandNode($client->getNode($requestId));
    } else {
        findNode($requestType,$requestId);
    }
}

function findNode($type,$id) {
    global $client;
    $typeMapping = Array (
        "agency" => Array("label" => "Agency", "id" => "agencyID")
    );

    $queryString =
        "MATCH (n:".$typeMapping[$type]["label"].")".
        "WHERE n.".$typeMapping[$type]["id"]." = {nodeId}".
        "RETURN n";

    $query = new Everyman\Neo4j\Cypher\Query($client, $queryString, array('nodeId' => $id));
    $result = $query->getResultSet();

    foreach ($result as $row) {
        expandNode( $row[0]);

    }

}

// https://github.com/jadell/neo4jphp/wiki/Paths

/*  https://github.com/jadell/neo4jphp/wiki/Traversals
 * $traversal = new Everyman\Neo4j\Traversal($client);
$traversal->addRelationship('KNOWS', Relationship::DirectionOut)
    ->setPruneEvaluator(Traversal::PruneNone)
    ->setReturnFilter(Traversal::ReturnAll)
    ->setMaxDepth(4);

$nodes = $traversal->getResults($startNode, Traversal::ReturnTypeNode);

 */

/*https://github.com/jadell/neo4jphp/wiki/Cypher-and-gremlin-queries
$queryString = "START n=node({nodeId}) ".
 "MATCH (n)<-[:KNOWS]-(x)".
 "WHERE x.name = {name}".
 "RETURN x";
$query = new Everyman\Neo4j\Cypher\Query($client, $queryString, array('nodeId' => 1, 'name' => 'Bob'));
$result = $query->getResultSet();
foreach ($result as $row) {
 echo $row['x']->getProperty('name') . "\n";
}*/


if (!isset($_REQUEST['debug'])) {
    header('Content-Type: application/gexf+xml');
    header('Content-Disposition: attachment; filename="'.urlencode(str_replace(" ","_",strtolower($description))).'.gexf.xml"');
}
echo '<?xml version="1.0" encoding="UTF-8"?>
<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:viz="http://www.gexf.net/1.2draft/viz" version="1.2">
    <meta lastmodifieddate="2009-03-20">
        <creator>lobbyist.disclosurelo.gs</creator>
        <description>'. $description. '</description>
    </meta>
    <graph mode="static" defaultedgetype="directed">

        <nodes>'. $nodes. '</nodes>
        <edges>'. $edges.' </edges>
    </graph>
</gexf>'. PHP_EOL;

?>
