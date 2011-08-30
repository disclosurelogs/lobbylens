<?php
	$config = '"networkgraph_config.xml"';
	$selectedNodeID = "gov";
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>LobbyLens</title>
<link rel="stylesheet" type="text/css" href="style-screen.css" media="screen" />
<link rel="stylesheet" type="text/css" href="style-print.css" media="print" />
<!-- BEGIN IE ActiveX activation workaround by Chris Benjaminsen -->
<script type="text/javascript">function writeHTML(a){document.write(a)}</script>
<script type="text/javascript" src="javascript:'function writeHTML(a){document.write(a)}'"></script>
<!-- END Workaround -->
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
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
		<!--
		
			// Constellation Roamer configuration
			
			/** the background color of the Constellation SWF */
			var backgroundColor = "#ffffff";
			
			/** the dimensions of the Constellation SWF */
			var constellationWidth = "100%";
			var constellationHeight = "600px";
			
			/** the ID of the node which is displayed as soon as the Constellation SWF loads */
			var selectedNodeID = <? echo '"'.$selectedNodeID.'"' ?>;
			
			/** the ID of this instance of the Constellation SWF */
			var instanceID = "1";
			
			/** the URL of the configuration file */
			var configURL = <? echo $config ?>;

			// print out the HTML which embeds the Constellation SWF in this page
			
			var flashvars = 'selected_node_id=' + selectedNodeID + '&instance_id=' + instanceID + '&config_url=' + configURL;			
			writeHTML('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" '
				+ 'codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" '
				+ 'width="' + constellationWidth + '" '
				+ 'height="' + constellationHeight + '" '
				+ 'id="constellation_roamer">'
					+ '<param name="allowScriptAccess" value="sameDomain" />'
					+ '<param name="movie" value="../constellation_roamer.swf" />'
					+ '<param name="quality" value="high" />'
					+ '<param name="bgcolor" value="' + backgroundColor + '" />'
					+ '<param name="scale" value="noscale" />'
					+ '<param name="flashvars" value="' + flashvars + '" />'
				+ '<embed src="../constellation_roamer.swf" quality="high" '
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
