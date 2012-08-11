<?php
include ("libs/config.php");
if (isset($_REQUEST['node_id'])) {
    $selectedNodeID = $_REQUEST['node_id'];
} else {
    $agencies = $dbConn->prepare('
SELECT "agencyName"
FROM contractnotice
ORDER BY random() LIMIT 1; ');
    $agencies->execute();
    $result = $agencies->fetch(PDO::FETCH_ASSOC);
    $selectedNodeID = 'agency-' . $result['agencyName'];
}
if (isset($_REQUEST['dev']) || $_SERVER['SERVER_NAME'] == "localhost") {
    $config = '"networkgraph_config-dev.xml"';
} else {
    $config = '"networkgraph_config.xml"';
}
// start loading the requested node so it can be in the <title> tag
// todo, make this a seperate API for faster loading
// todo, load the XML use AJAX/jQuery to display/change as graph changes
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
}
echo '</ul>
</div>
</div>';
?>
<script type="text/javascript">
    <!--
		
    // Constellation Roamer configuration
			
    /** the background color of the Constellation SWF */
    var backgroundColor = "#ffffff";
			
    /** the dimensions of the Constellation SWF */
    var constellationWidth = "100%";
    var constellationHeight = "80%";
			
    /** the ID of the node which is displayed as soon as the Constellation SWF loads */
    var selectedNodeID = <?php echo '"' . $selectedNodeID . '"' ?>;
			
    /** the ID of this instance of the Constellation SWF */
    var instanceID = "1";
			
    /** the URL of the configuration file */
    var configURL = <?php echo $config ?>;

    // print out the HTML which embeds the Constellation SWF in this page
			
    var flashvars = 'selected_node_id=' + selectedNodeID + '&instance_id=' + instanceID + '&config_url=' + configURL;			
    writeHTML('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" '
        + 'codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" '
        + 'width="' + constellationWidth + '" '
        + 'height="' + constellationHeight + '" '
        + 'id="constellation_roamer">'
        + '<param name="allowScriptAccess" value="sameDomain" />'
        + '<param name="movie" value="constellation_roamer.swf" />'
        + '<param name="quality" value="high" />'
        + '<param name="bgcolor" value="' + backgroundColor + '" />'
        + '<param name="scale" value="noscale" />'
        + '<param name="flashvars" value="' + flashvars + '" />'
        + '<embed src="constellation_roamer.swf" quality="high" '
        + 'bgcolor="' + backgroundColor + '" '
        + 'width="' + constellationWidth + '" '
        + 'height="' + constellationHeight + '" '
        + 'name="constellation_roamer" align="middle" '
        + 'scale="noscale" allowScriptAccess="sameDomain" '
        + 'type="application/x-shockwave-flash" '
        + 'flashvars="' + flashvars + '" '
        + 'pluginspage="http://www.macromedia.com/go/getflashplayer" /></object>');
    -->
</script>
</div>
</body>
</html>
