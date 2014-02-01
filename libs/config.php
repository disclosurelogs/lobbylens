<?php
date_default_timezone_set("Australia/ACT");

// common libs
require_once "dbconn.php";
require_once "wordcloud.php";
/*require "amon-php/amon.php";
Amon::config(array('address'=> 'http://127.0.0.1:2464', 
		'protocol' => 'http', 
		'secret_key' => "GNLu8aqN7aXxoh8JoDGJjqeScAXd6L8Zq4sjixmVwLo"));*/
//Amon::setup_exception_handler();

function ucsmart($str) {
    $shortWords = Array("The", "Pty", "Ltd", "Inc", "Red", "Oil", "A", "An", "And", "At", "For", "In"
        , "Of", "On", "Or", "The", "To", "With", "Uni", "One", "Box", "Utz");
    $strArray = explode(" ", preg_replace("/(?<=(?<!:|’s)\W)
            (A|An|And|At|For|In|Of|On|Or|The|To|With)
            (?=\W)/e", 'strtolower("$1")', ucwords(strtolower($str))));
    foreach ($strArray as &$word) {
        if (strlen($word) <= 3 && !in_array($word, $shortWords))
            $word = strtoupper($word);
    }
    return implode(" ", $strArray);
}

if (!function_exists('money_format')) {
    function money_format($a,$b) {
        return $b;
    }
}
function getPage($url) {

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    // ssl ignore
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $page = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "<font color=red> Database temporarily unavailable: ";
        echo curl_errno($ch) . " " . curl_error($ch);
Amon::log(curl_errno($ch) . " " . curl_error($ch), array('error'));
        echo $url;

        echo "</font><br>";
    }
    curl_close($ch);

    return $page;
}

# Convert a stdClass to an Array. http://www.php.net/manual/en/language.types.object.php#102735

function object_to_array(stdClass $Class) {
    # Typecast to (array) automatically converts stdClass -> array.
    $Class = (array) $Class;

    # Iterate through the former properties looking for any stdClass properties.
    # Recursively apply (array).
    foreach ($Class as $key => $value) {
        if (is_object($value) && get_class($value) === 'stdClass') {
            $Class[$key] = object_to_array($value);
        }
    }
    return $Class;
}

function abnLookup($orgname) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_REFERER, "http://lobbylens.info");
    $guid = "5f2f943e-15f4-4782-8fad-9a0fe83a2f47";
    $url = "http://abr.business.gov.au/ABRXMLSearchRPC/ABRXMLSearch.asmx/ABRSearchByNameSimpleProtocol?name=" . urlencode($orgname) . "&postcode=&legalName=Y&tradingName=Y&NSW=Y&SA=Y&ACT=Y&VIC=Y&WA=Y&NT=Y&QLD=Y&TAS=Y&authenticationGuid=$guid";
    curl_setopt($ch, CURLOPT_URL, $url);
    $body = curl_exec($ch);
    // If it's failing here, maybe you don't have php bindings for curl installed?
    $xml = new SimpleXMLElement($body);
    $result = $xml->response->searchResultsList->searchResultsRecord[0];
    return $result->ABN->identifierValue;
}

function local_url() {
    return "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/";
}

function searchName($input) {

    return "%" . cleanseName($input) . "%";
}

function cleanseName($input) {
    $cleanseNamesCorp = Array(
        "Ltd",
        "Limited",
        "Australiasia",
        "The ",
        "Pty",
        "Ltd",
        "Contractors",
        "P/L",
        "Inc.",
        "Inc",
        "Incorporated",
        "Hornibrook",
        ". .",
        "(IAG)",
        "- a coalition of professional associations and firms"
    );
    $cleanseNamesPolitical = Array(
        "(NSW)",
        "(QLD)",
        "Aust.",
        "(NSW/ACT)",
        "Aust ",
        "(Aus)",
        "(Inc)",
        "(WA)",
        "(Southern Region)",
        "(N.S.W.)",
        "(SA Branch)",
        "NSW",
        "SA Branch",
        "ACT",
        "QLD",
        ", SA",
        " WA",
        "- QLD Services Branch",
        "- Central and Southern Q",
        "- SA and NT Branch",
        "- TAS",
        "NSW & ACT Services Branch",
        "SA-NT Branch",
        "- National Office",
        "- Victoria Branch",
        "- National",
        "- Victoria Branch",
        "(Greater SA)",
        "(SA)",
        "(VIC)",
        "- NATIONAL",
        ". .",
        "(IAG)",
        "(NSW Div)",
        "(Queensland Branch)",
        "(ACT/NSW Bra",
        "(SA/NT)",
        ", WA Branch",
    );
    $cleanseNames = $cleanseNamesCorp + $cleanseNamesPolitical;
    return trim(str_ireplace($cleanseNames, "", $input));
}

function startsWith($haystack, $needle, $case = true) {
    if ($case) {
        return (strcmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
    }
    return (strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
}

function endsWith($haystack, $needle, $case = true) {
    if ($case) {
        return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
    }
    return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
}

function include_header($title = "") {
    header("Content-Type: text/html; charset=UTF-8")
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>LobbyLens<?php if ($title != "")
        echo " - $title";
    ?></title>
            <link rel="stylesheet" type="text/css" href="style-screen.css" media="screen" />
            <link rel="stylesheet" type="text/css" href="style-print.css" media="print" />
            <!-- BEGIN IE ActiveX activation workaround by Chris Benjaminsen -->
            <script type="text/javascript">function writeHTML(a){document.write(a)}</script>
            <script type="text/javascript" src="javascript:'function writeHTML(a){document.write(a)}'"></script>
            <!-- END Workaround -->
            <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
            <script type="text/javascript">
                $(document).ready(function()
                {
                    //hide the all of the element with class msg_body
                    $(".msg_body").hide();
                    //toggle the componenet with class msg_body
                    $(".msg_head").click(function()
                    {
                        $(this).next(".msg_body").slideToggle(600);
                    });
                });
            </script></head>
        <body>
            <script type="text/javascript">
                var gaJsHost = (("https:" == document.location.protocol) ? 
                    "https://ssl." : "http://www.");
                document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
            </script>
            <script type="text/javascript">
                try {
                    var pageTracker = _gat._getTracker("UA-12341040-1");
                    pageTracker._trackPageview();
                } catch(err) {}</script>
            <div id="header">
                <h1><a href="http://lobbylens.info">Lobby Lens</a></h1>
            </div>
            <div id="nav">
                <ul>
                    <li><a href="index.php" class="current">home</a></li>
                    <li><a href="about.php">about</a></li>
                    <li><a href="supplier.php">suppliers</a></li>
                    <li><a href="agencyWordCloud.php">agencies</a></li>
                    <li><a href="lobbyistWordCloud.php">lobbyists</a></li>
                    <li><a href="categories.php">industries</a></li>
                </ul>
            </div>
            <div id="content">
                <?php
            }

            function include_footer() {

                if (strpos($_SERVER['SERVER_NAME'], ".gs")) {
                    ?>
                    <script type="text/javascript">

                        var _gaq = _gaq || [];
                        _gaq.push(['_setAccount', 'UA-12341040-1']);
                        _gaq.push(['_trackPageview']);

                        (function() {
                            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
                        })();

                    </script>
                </div>
            </body>
        </html>
        <?php
    }
}
?>
