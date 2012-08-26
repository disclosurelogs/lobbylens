<?php
include ("libs/config.php");
include_header("About");
?>
  <h2>About LobbyLens</h2>
  <p> LobbyLens 
    correlates 
    data about Federal Government business over the last 18 months. It shows the connections between government contracts, business details, politician responsiblities, lobbyists, clients of lobbyists and the location of these entities.</p>
  <p>LobbyLens was developed at the Australian Government 2.0 hackday <a href="http://govhack.org">GovHack</a> and judged the Best in Show. It 
went on to win 3rd place (Notable Mashing Achievements category) in the <A href="http://mashupaustralia.org">MashupAustralia</a> competition</p>
  <p> As mentioned by: <a href="http://www.smh.com.au/opinion/society-and-culture/hack-day-like-a-tech-version-of-a-hippy-commune-20091102-hrw6.html">Bella Counihan&mdash;Sydney Morning Herald</a>, <a href="http://www.smh.com.au/technology/technology-news/app-to-find-nearest-toilet-data-flood-unleashed-20091104-hwqj.html">Asher 
    Moses&mdash;Sydney Morning Herald</a>, <a href="http://www.financeminister.gov.au/media/2009/mr_742009.html">The Hon Lindsay Tanner MP&mdash;Minister for 
    Finance and Deregulation</a>, <a href="http://www.psnews.com.au/Page_psn1943.html">PSnews</a>, <a 
href="http://www.smh.com.au/technology/technology-news/yes-minister-tweeting-could-be-the-new-way-of-working-20091208-kfk3.html">Ari Sharp 
&mdash; Sydney Morning Herald</a>, <a href="http://gov2.net.au/consultation/2009/12/07/draftreport/#334">Government 2.0 Taskforce &mdash; Getting on 
with Government 2.0 Report</a></p>
  <h3>Data sources</h3>
  <ul>
    <?php
    $datasets = $dbConn->prepare("SELECT * FROM datasets");
$datasets->execute();

foreach ($datasets->fetchAll() as $row) {
  $lastUpdate = explode(" ",$row['lastUpdated']);
  echo "<li>".$row['title']." from <a href=\"".$row['URL']."\">".$row['sourceName']."</a> (last updated: ".$lastUpdate[0].")";
}
?></ul>
  <h3>Team</h3>
  <ul>
    <li><a href="http://twitter.com/zephell">@zephell</a>: Kelvin Nicholson, Sydney</li>
    <li><a href="http://twitter.com/frglps">@frglps</a>: <a href="http://flworpower.com">Christian Hope</a>, Melbourne</li>
    <li><a href="http://twitter.com/DorisSpiel">@DorisSpiel</a>: Doris Spielthenner, Melbourne</li>
    <li><a href="http://twitter.com/mjec">@mjec</a>: Michael Cordover, Hobart</li>
    <li><a href="http://twitter.com/maxious">@maxious</a>: <a href="http://maxious.lambdacomplex.org">Alexander Sadleir</a>, Canberra</li>
  </ul>
  <h3>Acknowledgments</h3>
  <ul>
    <li>Daniel McLaren <a href="http://asterisq.com/">asterisq.com</a></li>
    <li>Nicole Jones</li>
    <li><a href="http://github.com/mlandauer/nsw_lobbyist_register">Matthew Landauer</a>, NSW Lobbyist Register parser</li>
  </ul>
  <h3>Open Source</h3>
  LobbyLens is thankful for the resources that the Open Source community provides for conducting a project such as this.
  <ul>
    <li><a href="lobbylens.sql.gz">LobbyLens database dump</a></li>
    <li><a href="lobbylens.Jan16th2010.tgz">LobbyLens source code</a></li>
    <li><a href="http://maxious.lambdacomplex.org/git/">Lobbyist Register -&gt; YAML parser</a></li>
    <li><a href="ngapi.xml.php">Network Graph API</a></li>
  </ul>
  <h3>Contact us</h3>
  <p><a href="mailto:govhack@lambdacomplex.org">govhack@lambdacomplex.org</a> </p>

<?php
include_footer();
?>
