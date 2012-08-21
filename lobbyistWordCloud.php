<?php
include	("libs/config.php");
include_header("Lobbyists");
?>
  <h2>Lobbyists</h2>
  <div class="tagCloud">
    <?php


	$lobbyistcloud = new wordcloud();
	$lobbyists = $dbConn->prepare('
SELECT "ABN" AS lobbyist_abn, min(trading_name) as lobbyist_name, count(1) as client_count
FROM lobbyists
INNER JOIN lobbyist_relationships ON lobbyists."lobbyistID" = lobbyist_relationships."lobbyistID"
GROUP BY lobbyist_abn
	');
	$lobbyists->execute();

	foreach($lobbyists->fetchAll() as $row) {
	    $lobbyistcloud->addWord($row['lobbyist_name'],$row['client_count'],$row['lobbyist_abn']);
	}

	$myCloud = $lobbyistcloud->showCloud('array');
	if(is_array($myCloud)){
		echo '<div class="tagCloud">';
	    foreach ($myCloud as $key => $value) {
	        echo ' <a href="networkgraph.php?node_id=lobbyist-'.$value['ref'].'" class="cloud'.$value['sizeRange'].'">'.$value['word'].'</a> &nbsp;';
	    }
	}

?>
  </div>
<?php
include_footer();
?>

