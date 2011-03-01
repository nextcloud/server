<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format">
<xsl:output method="text" omit-xml-declaration="yes" indent="no"/>
<xsl:preserve-space elements="headervalue paramvalue body"/>

	<xsl:template name="mimepart">

		<xsl:variable name="boundary">
				<xsl:for-each select="./header">
					<xsl:if test="string(./headername) = 'Content-Type'">
						<xsl:for-each select="./parameter">
							<xsl:if test="string(./paramname) = 'boundary'">
								<xsl:value-of select="paramvalue"/>
							</xsl:if>
						</xsl:for-each>
					</xsl:if>
				</xsl:for-each>
		</xsl:variable>

		<xsl:for-each select="header">

			<xsl:value-of select="headername"/>
			<xsl:text>: </xsl:text>
			<xsl:value-of select="headervalue"/>

			<xsl:if test="count(./parameter) = 0">
				<xsl:text>&#13;&#10;</xsl:text>
			</xsl:if>

			<xsl:for-each select="parameter">
				<xsl:text>;&#13;&#10;&#09;</xsl:text>
				<xsl:value-of select="paramname"/>
				<xsl:text>="</xsl:text>
				<xsl:value-of select="paramvalue"/>
				<xsl:text>"</xsl:text>
			</xsl:for-each>

			<xsl:if test="count(./parameter) > 0">
				<xsl:text>&#13;&#10;</xsl:text>
			</xsl:if>

		</xsl:for-each>

		<xsl:text>&#13;&#10;</xsl:text>

		<!-- Which to do, print a body or process subparts? -->
		<xsl:choose>
			<xsl:when test="count(./mimepart) = 0">
				<xsl:value-of select="body"/>
				<xsl:text>&#13;&#10;</xsl:text>
			</xsl:when>

			<xsl:otherwise>
				<xsl:for-each select="mimepart">
					<xsl:text>--</xsl:text><xsl:value-of select="$boundary"/><xsl:text>&#13;&#10;</xsl:text>
					<xsl:call-template name="mimepart"/>
				</xsl:for-each>

				<xsl:text>--</xsl:text><xsl:value-of select="$boundary"/><xsl:text>--&#13;&#10;</xsl:text>

			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

<!-- This is where the stylesheet really starts, matching the top level email element -->
	<xsl:template match="email">
		<xsl:call-template name="mimepart"/>
	</xsl:template>

</xsl:stylesheet>