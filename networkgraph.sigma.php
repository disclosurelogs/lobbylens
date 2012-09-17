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
     <div id="sigma-example" width="960" style="min-height:800px;background-color: #333;"></div>
  <script src="js/sigma.min.js"></script>
  <script src="js/sigma/plugins/sigma.parseGexf.js"></script>
  <script src="js/sigma/plugins/sigma.forceatlas2.js"></script>
  <script type="text/javascript">function init() {
  // Instanciate sigma.js and customize rendering :
  var sigInst = sigma.init(document.getElementById('sigma-example')).drawingProperties({
    defaultLabelColor: '#fff',
    defaultLabelSize: 14,
    defaultLabelBGColor: '#fff',
    defaultLabelHoverColor: '#000',
    labelThreshold: 6,
    defaultEdgeType: 'curve'
  }).graphProperties({
    minNodeSize: 0.5,
    maxNodeSize: 5,
    minEdgeSize: 5,
    maxEdgeSize: 5
  }).mouseProperties({
    maxRatio: 32
  });

  // Parse a GEXF encoded file to fill the graph
  // (requires "sigma.parseGexf.js" to be included)
  sigInst.parseGexf('ngapi.gexf.php?node_id=<?php echo $selectedNodeID ?>');
 sigInst.bind('downnodes',function(event){
    var nodes = event.content;
 });
  // Start the ForceAtlas2 algorithm
  // (requires "sigma.forceatlas2.js" to be included)
  sigInst.startForceAtlas2();
  
  // Draw the graph :
  sigInst.draw();
}

if (document.addEventListener) {
  document.addEventListener("DOMContentLoaded", init, false);
} else {
  window.onload = init;
}
</script>

<?php
include_footer();
?>

