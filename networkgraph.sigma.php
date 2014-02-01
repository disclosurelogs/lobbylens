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
//echo "<!--". local_url() . "neo4japi.gexf.php?ids=" . urlencode(stripslashes($selectedNodeID)) . "-->";
$xml = file_get_contents(local_url() . "neo4japi.gexf.php?ids=" . urlencode(stripslashes($selectedNodeID)));
$graph = new SimpleXMLElement($xml);
$name = $graph->xpath("//*[name()='description'][1]");

include_header($name[0]);

echo '<p>Click on nodes to expand the network, hover over nodes, or lines joining nodes, to display more
information.</p>';
echo "<!--". local_url() . "neo4japi.gexf.php?ids=" . urlencode(stripslashes($selectedNodeID)) . "-->";
echo '<div class="msg_list">
<p class="msg_head">';
echo $name['label'];
echo ' </p>
<div class="msg_body"><ul>';
$nodes = Array();
foreach ($graph->xpath("//*[name()='node']") as $node) {
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
foreach ($graph->xpath("//*[name()='edge']") as $edge) {
    //$message = $edge['tooltip'];
    $message = '<a href="?node_id=' . (string) $edge['source'] . '">' . htmlspecialchars($nodes[(string) $edge['source']]) . "</a> ->";
    $message .= '<a href="?node_id=' . (string) $edge['target'] . '">' . htmlspecialchars($nodes[(string) $edge['target']]) . "</a>";
    echo "<li>$message</li>";
}
echo '</ul>
</div>
</div>';
?>

<div id="right" style="float: right; width: 29%;">
    <b>Current Node</b>
    Find links to ...
    Find closest link(s) to a lobbyist
    Find closest link(s) to a political party
    if agency: View Agency Details, View Agency FOI Documents, Make an FOI request to Agency
    if supplier: View Supplier Details

    <b>Current Searches</b>
    x NodeA
    x NodeA links to NodeB
    x

    <b>Add search</b>

    autociompleting search box here
</div>
    <div id="left" style="width:70%">
     <div id="sigma-container" width="70%" style="min-height:800px;background-color: #333;"></div>
 <!-- <script src="js/sigma/build/sigma.min.js"></script>-->
  <script src="js/sigma/build/sigma.min.js"></script>
  <script src="js/sigma/plugins/sigma.parsers.gexf/gexf-parser.js"></script>
  <script src="js/sigma/plugins/sigma.parsers.gexf/sigma.parsers.gexf.js"></script>
  <script src="js/sigma/plugins/sigma.layout.forceAtlas2/sigma.layout.forceAtlas2.js"></script>
  <script type="text/javascript">

  // Add a method to the graph model that returns an
  // object with every neighbors of a node inside:
  sigma.classes.graph.addMethod('neighbors', function(nodeId) {
    var k,
        neighbors = {},
        index = this.allNeighborsIndex[nodeId] || {};

    for (k in index)
      neighbors[k] = this.nodesIndex[k];

    return neighbors;
  });

  sigma.parsers.gexf(
    'neo4japi.gexf.php?ids=<?php echo urlencode($selectedNodeID) ?>',
    {
      container: 'sigma-container'
    },
    function(s) {
      // We first need to save the original colors of our
      // nodes and edges, like this:
      s.graph.nodes().forEach(function(n) {
        n.originalColor = n.color;
      });
      s.graph.edges().forEach(function(e) {
        e.originalColor = e.color;
      });

      // When a node is clicked, we check for each node
      // if it is a neighbor of the clicked one. If not,
      // we set its color as grey, and else, it takes its
      // original color.
      // We do the same for the edges, and we only keep
      // edges that have both extremities colored.
      s.bind('clickNode', function(e) {
        var nodeId = e.data.node.id,
            toKeep = s.graph.neighbors(nodeId);
        toKeep[nodeId] = e.data.node;

        s.graph.nodes().forEach(function(n) {
          if (toKeep[n.id])
            n.color = n.originalColor;
          else
            n.color = '#eee';
        });

        s.graph.edges().forEach(function(e) {
          if (toKeep[e.source] && toKeep[e.target])
            e.color = e.originalColor;
          else
            e.color = '#eee';
        });

        // Since the data has been modified, we need to
        // call the refresh method to make the colors
        // update effective.
        s.refresh();
      });

      // When the stage is clicked, we just color each
      // node and edge with its original color.
      s.bind('clickStage', function(e) {
        s.graph.nodes().forEach(function(n) {
          n.color = n.originalColor;
        });

        s.graph.edges().forEach(function(e) {
          e.color = e.originalColor;
        });

        // Same as in the previous event:
        s.refresh();
      });
    }
  );
</script>
    </div>

<?php
include_footer();
?>

