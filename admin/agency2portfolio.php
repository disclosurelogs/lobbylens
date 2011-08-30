<?php

// Returns portfolio scraped live from directory.gov.au
// or null if can't find a portfolio
function agency2portfolio ($agency) {
	static $cache = array();
	if (isset($cache[$agency])) { return $cache[$agency]; }
	$c = curl_init('http://www.directory.gov.au/searchres.php');
	curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_HEADER, false);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_REFERER, 'http://www.directory.gov.au/adsearch.php');
	curl_setopt($c, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; en-GB; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3');
	curl_setopt($c, CURLOPT_POSTFIELDS, array(
		'advkeywordfield' => '',
		'advorgunitfield' => $agency,
		'advrolefield' => '',
		'advsection' => 'All',
		'advsurnamefield' => '',
		'search' => 'Submit Query'
	));
	$results = curl_exec($c);
	
	if (preg_match('#<span\s+class="standardlinks"><a\s+href="([^"]+)">#smi', $results, $m)) {
		$nextURL = $m[1];
	} else {
		$cache[$agency] = false; return false;
	}
	
	curl_setopt($c, CURLOPT_URL, 'http://www.directory.gov.au' . $nextURL);
	curl_setopt($c, CURLOPT_HTTPGET, true);
	curl_setopt($c, CURLOPT_REFERER, 'http://www.directory.gov.au/searchres.php');
	$results = curl_exec($c);
	if (preg_match('#portfolios:\s+([^<]+)#ims', $results, $m)) {
		$cache[$agency] = $m[1]; return $m[1];
	} else {
		$cache[$agency] = false; return false;
	}
}

?>
