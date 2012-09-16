<?php
include "libs/config.php";
$nodeID = (isset($_REQUEST['node_id']) ? $_REQUEST['node_id'] : "");
$xml = file_get_contents(local_url() . "ngapi.xml.php?node_id=" . urlencode(stripslashes($selectedNodeID)));
$graph = new SimpleXMLElement($xml);
$name = $graph->xpath('//name');

include_header($name[0]);

echo "<!--". local_url() . "ngapi.xml.php?node_id=" . urlencode(stripslashes($selectedNodeID)) . "-->";
echo '<p>Click on nodes to expand the network, hover over nodes, or lines joining nodes, to display more 
information.</p>';
echo '<div class="msg_list">
<p class="msg_head">';
echo $name[0];
echo ' </p>
<div class="msg_body"><ul>';
$nodes = Array();
foreach ($graph->xpath('//node') as $node) {
    $id = (string) $node['id'];
    if (strstr($id, "donor-"))
        continue;
    else {
        $nodes[$id] = (string) $node['label'];
        if (strstr($nodes[$id], ":")) {
            $labelparts = explode(":", $nodes[$id]);
            array_shift($labelparts);
            $nodes[$id] = trim(implode($labelparts));
        }
    }
}
foreach ($graph->xpath('//edge') as $edge) {
    $message = $edge['tooltip'];
    $message = str_replace($nodes[(string) $edge['head_node_id']], '<a href="?node_id=' . (string) $edge['head_node_id'] . '">' . htmlspecialchars($nodes[(string) $edge['head_node_id']]) . "</a>", $message);
    $message = str_replace($nodes[(string) $edge['tail_node_id']], '<a href="?node_id=' . (string) $edge['tail_node_id'] . '">' . htmlspecialchars($nodes[(string) $edge['tail_node_id']]) . "</a>", $message);
    echo "<li>$message</li>";
}?>
