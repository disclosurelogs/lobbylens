<?php
include "libs/config.php";

function add_node($id, $label, $parent="") {

          echo "<node id='$id' label=\"".htmlentities($label,ENT_XML1)."\" ".($parent != ""? "pid='$parent'><viz:size value='1'/>":"><viz:size value='2'/>")
              ."<viz:color b='".rand(0,255)."' g='".rand(0,255)."' r='".rand(0,255)."'/>"
                  ."</node>". PHP_EOL;
}

function add_edge($from, $to) {

          echo "<edge id='$from$to' source='$from' target='$to' />". PHP_EOL;

}

$nodeID = (isset($_REQUEST['node_id']) ? $_REQUEST['node_id'] : "");
if ($nodeID == "" || $nodeID == "[node_id]") {
    ?>
    Network Graph API supports the following central node types:
    "agency"
    "supplier"
    "category"
    "lobbyist"
    "lobbyistclient"
    "donationrecipient"
    <?php
    die();
}
     header('Content-Type: application/gexf+xml');
echo '<?xml version="1.0" encoding="UTF-8"?>
<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:viz="http://www.gexf.net/1.2draft/viz" version="1.2">
    <meta lastmodifieddate="2009-03-20">
        <creator>Gexf.net</creator>
        <description>A hello world! file</description>
    </meta>
    <graph mode="static" defaultedgetype="directed">

        <nodes>'. PHP_EOL;
$url = local_url() . "ngapi.xml.php?node_id=" . urlencode(stripslashes($nodeID));
$xml = file_get_contents($url);
$graph = new SimpleXMLElement($xml);
$nodes = Array();
foreach ($graph->xpath('//node') as $node) {
    $id = (string) $node['id'];
    if (strstr($id, "donor-"))
        continue;
    else {
        $label = (string) $node['label'];
        if (strstr($label, ":")) {
            $labelparts = explode(":", $nodes[$id]);
            array_shift($labelparts);
            $label = trim(implode($labelparts));
        }
        add_node($id, $label);
    }
}
echo '</nodes>
        <edges>'. PHP_EOL;
foreach ($graph->xpath('//edge') as $edge) {
    add_edge($edge['head_node_id'],$edge['tail_node_id']);
            }
echo ' </edges>
    </graph>
</gexf>'. PHP_EOL;

?>
