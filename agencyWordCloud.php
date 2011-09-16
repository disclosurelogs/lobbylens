<?php
include	("libs/config.php");
include_header();
?>
  <h2>Agencies</h2>
  <div class="tagCloud">
<?php

	$agencycloud = new wordcloud();
	$agencies = $dbConn->prepare('
	    SELECT "agencyName", sum(value)
	    FROM contractnotice WHERE "childCN" = 0		
	    GROUP BY "agencyName"
	    ORDER BY sum(value) DESC
	');
	$agencies->execute();

	foreach($agencies->fetchAll() as $row) {
	    $agencycloud->addWord($row['agencyName'],$row['sum']);
	}

	$myCloud = $agencycloud->showCloud('array');
	if(is_array($myCloud)){
		echo '<div class="tagCloud">';
	    foreach ($myCloud as $key => $value) {
	        echo ' <a href="networkgraph.php?node_id=agency-'.htmlentities($value['word']).'" class="cloud'.$value['sizeRange'].'">'.$value['word'].'</a> &nbsp;';
	    }
	}

?>
</div>
</div>
</body>
</html>
