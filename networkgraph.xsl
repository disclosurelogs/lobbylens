<?xml version="1.0" encoding="ISO-8859-1"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="graph_data">
  <html>
  <body>
  <ul>
    <xsl:for-each select="edges/edge">
      <li>
		<small> <a>
		<xsl:attribute name="href">
			<xsl:text>?node_id=</xsl:text>
			<xsl:value-of select="@head_node_id"/>
		</xsl:attribute>
		[explore head]
		</a></small>
	<xsl:value-of select="@tooltip"/> 
		<small> <a>
		<xsl:attribute name="href">
			<xsl:text>?node_id=</xsl:text>
			<xsl:value-of select="@tail_node_id"/>
		</xsl:attribute>
		[explore tail]
		</a></small>
      </li>
    </xsl:for-each>
  </ul>
  </body>
  </html>
</xsl:template>

</xsl:stylesheet>
