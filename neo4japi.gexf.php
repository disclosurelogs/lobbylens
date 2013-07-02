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
$nodeList = Array();

function add_node($id, $label, $parent="") {
global $nodes,$nodeList;
    if (!in_array($id,$nodeList)) {
          $nodes.= "<node id='".urlencode($id)."' label=\"".htmlentities($label)."\" ".($parent != ""? "pid='$parent'><viz:size value='".rand(1,50)."'/>":"><viz:size value='2'/>")
              ."<viz:color b='".rand(0,255)."' g='".rand(0,255)."' r='".rand(0,255)."'/>"
                  ."</node>". PHP_EOL;
        $nodeList[] = $id;
    }
}

function add_edge($from, $to) {
          global $edges;
          $edges.= "<edge id='".urlencode($from.$to)."' source='".urlencode($from)."' target='".urlencode($to)."' />". PHP_EOL;

}

$nodeID = (isset($_REQUEST['node_id']) ? $_REQUEST['node_id'] : "");
/*if ($nodeID == "" || $nodeID == "[node_id]") {
    ?>
    Network Graph API supports the following central node types:
    "agency"
    "supplier"
    "category"
    "lobbyist"
    "lobbyistclient"
    "donationrecipient"
    <?php
//    die();
} */

// Connecting to the default port 7474 on localhost
$client = new Everyman\Neo4j\Client();
//print_r($client->getServerInfo());

//https://github.com/jadell/neo4jphp/wiki/Caching
$plugin = new Everyman\Neo4j\Cache\Variable();
$client->getEntityCache()->setCache($plugin);

/*$memcached = new Memcached();
$memcached->addServer('localhost', 11211);

$plugin = new Everyman\Neo4j\Cache\Memcached($memcached);
$client->getEntityCache()->setCache($plugin);*/

$character = $client->getNode(100);
add_node($character->getId(), $character->getProperty("name"));

foreach ($character->getProperties() as $key => $value) {
   // echo "$key: $value\n";
}
foreach ($character->getRelationships() as $rel) {
    //echo($rel->getStartNode()->getId()." -> ".$rel->getEndNode()->getId()."<br>");
    add_edge($rel->getStartNode()->getId(),$rel->getEndNode()->getId());
    add_node($rel->getStartNode()->getId(), $rel->getStartNode()->getProperty("name"));
    add_node($rel->getEndNode()->getId(), $rel->getEndNode()->getProperty("name"));
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


header('Content-Type: application/gexf+xml');
echo '<?xml version="1.0" encoding="UTF-8"?>
<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:viz="http://www.gexf.net/1.2draft/viz" version="1.2">
    <meta lastmodifieddate="2009-03-20">
        <creator>Gexf.net</creator>
        <description>A hello world! file</description>
    </meta>
    <graph mode="static" defaultedgetype="directed">

        <nodes>'. $nodes. '</nodes>
        <edges>'. $edges.' </edges>
    </graph>
</gexf>'. PHP_EOL;

?>
