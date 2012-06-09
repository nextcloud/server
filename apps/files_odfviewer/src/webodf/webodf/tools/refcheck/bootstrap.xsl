<?xml version='1.0' encoding='UTF-8'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
  xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
  xmlns:o="urn:odfkeys" xmlns:x="dummy" exclude-result-prefixes="o">
 <xsl:output method="xml" indent="yes"/>
 <xsl:namespace-alias stylesheet-prefix="x" result-prefix="xsl"/>

 <xsl:template name="join" >
  <xsl:param name="values"/>
  <xsl:param name="separator"/>
  <xsl:for-each select="$values">
   <xsl:choose>
    <xsl:when test="position() = 1">
     <xsl:value-of select="."/>
    </xsl:when>
    <xsl:otherwise>
     <xsl:value-of select="concat($separator, .) "/>
    </xsl:otherwise>
   </xsl:choose>
  </xsl:for-each>
 </xsl:template>

 <xsl:template name="getdefs">
  <xsl:param name="name"/>
  <xsl:param name="separator"/>
  <xsl:variable name="key" select="../o:key[@name=$name]"/>
  <xsl:if test="$key/@extends">
   <xsl:call-template name="getdefs">
    <xsl:with-param name="name" select="$key/@extends"/>
    <xsl:with-param name="separator" select="$separator"/>
   </xsl:call-template>
   <xsl:text>|</xsl:text>
  </xsl:if>
  <xsl:call-template name="join">
   <xsl:with-param name="values" select="$key/o:def"/>
   <xsl:with-param name="separator" select="$separator"/>
  </xsl:call-template>
 </xsl:template>

 <xsl:template match="o:key" mode="define">
  <xsl:variable name="def">
   <xsl:call-template name="getdefs">
    <xsl:with-param name="name" select="@name"/>
    <xsl:with-param name="separator" select="'|'"/>
   </xsl:call-template>
  </xsl:variable>
  <xsl:variable name="ref">
   <xsl:call-template name="join">
    <xsl:with-param name="values" select="o:ref"/>
    <xsl:with-param name="separator" select="'|'"/>
   </xsl:call-template>
  </xsl:variable>
  <x:template name="check{@name}">
   <x:param name="stylesfontfaces" />
   <x:param name="fontfaces" />
   <x:param name="officestyles" />
   <x:param name="stylesautostyles" />
   <x:param name="contentautostyles" />
   <x:param name="masterstyles" />
   <x:param name="body" />
   <x:call-template name="checkReferences">
    <x:with-param name="name" select="'{@name}'"/>
    <x:with-param name="definitions" select="{$def}"/>
    <x:with-param name="references" select="{$ref}"/>
   </x:call-template>
  </x:template>
 </xsl:template>

 <xsl:template match="o:key" mode="call">
  <x:call-template name="check{@name}">
   <x:with-param name="stylesfontfaces" select="$stylesfontfaces"/>
   <x:with-param name="fontfaces" select="$fontfaces"/>
   <x:with-param name="officestyles" select="$officestyles"/>
   <x:with-param name="stylesautostyles" select="$stylesautostyles"/>
   <x:with-param name="contentautostyles" select="$contentautostyles"/>
   <x:with-param name="masterstyles" select="$masterstyles"/>
   <x:with-param name="body" select="$body"/>
  </x:call-template>
 </xsl:template>

 <xsl:template match="/o:keys">
  <x:stylesheet version="1.0">
   <xsl:for-each select="namespace::*"><xsl:copy/></xsl:for-each>

   <xsl:apply-templates select="o:key" mode="define"/>

   <x:template name="check">
    <x:param name="stylesfontfaces" />
    <x:param name="fontfaces" />
    <x:param name="officestyles" />
    <x:param name="stylesautostyles" />
    <x:param name="contentautostyles" />
    <x:param name="masterstyles" />
    <x:param name="body" />
    <xsl:apply-templates select="o:key" mode="call"/>
   </x:template>

   <x:template name="checkuniqueitem">
    <x:param name="listname"/>
    <x:param name="list"/>
    <x:param name="item"/>
    <x:if test="count($list[.=$item])!=1">
     <x:message>
      <x:value-of select="concat('The key with value &quot;', $item, '&quot; is used more than once in ', $listname, '.')"/>
     </x:message>
    </x:if>
   </x:template>
   
   <x:template name="checkunique">
    <x:param name="listname"/>
    <x:param name="list"/>
    <x:variable name="count">
    </x:variable>
    <x:for-each select="$list">
     <x:call-template name="checkuniqueitem">
      <x:with-param name="listname" select="$listname"/>
      <x:with-param name="list" select="$list"/>
      <x:with-param name="item" select="."/>
     </x:call-template>
    </x:for-each>
   </x:template>
   
   <x:template name="verifyOccurance">
    <x:param name="name"/>
    <x:param name="definitions"/>
    <x:param name="reference"/>
    <x:if test="count($definitions[.=$reference])=0">
     <x:message>
      <x:value-of select="concat('There is no key with value &quot;', $reference, '&quot; in the key list ', $name, '.')"/>
     </x:message>
    </x:if>
   </x:template>

   <x:template name="checkReferences">
    <x:param name="name"/>
    <x:param name="definitions"/>
    <x:param name="references"/>
   
    <x:call-template name="checkunique">
     <x:with-param name="listname" select="$name"/>
     <x:with-param name="list" select="$definitions"/>
    </x:call-template>
   
    <x:for-each select="$references">
     <x:call-template name="verifyOccurance">
      <x:with-param name="name" select="$name"/>
      <x:with-param name="definitions" select="$definitions"/>
      <x:with-param name="reference" select="."/>
     </x:call-template>
    </x:for-each>
   </x:template>

   <x:template match="/office:document">
    <x:call-template name="check">
     <x:with-param name="stylesfontfaces" select="office:font-face-decls"/>
     <x:with-param name="fontfaces" select="office:font-face-decls"/>
     <x:with-param name="officestyles" select="office:styles"/>
     <x:with-param name="stylesautostyles" select="office:automatic-styles"/>
     <x:with-param name="contentautostyles" select="office:automatic-styles"/>
     <x:with-param name="masterstyles" select="office:master-styles"/>
     <x:with-param name="body" select="office:body"/>
    </x:call-template>
   </x:template>

   <x:template match="/office:document-styles">
    <x:call-template name="check">
     <x:with-param name="stylesfontfaces" select="office:font-face-decls"/>
     <x:with-param name="fontfaces" select="office:font-face-decls"/>
     <x:with-param name="officestyles" select="office:styles"/>
     <x:with-param name="stylesautostyles" select="office:automatic-styles"/>
     <x:with-param name="contentautostyles" select="not-available"/>
     <x:with-param name="masterstyles" select="office:master-styles"/>
     <x:with-param name="body" select="not-available"/>
    </x:call-template>
   </x:template>

   <x:template match="/office:document-content">
    <x:variable name="stylesxml" select="document('styles.xml', .)/office:document-styles" />
    <x:variable name="stylesfontfaces" select="$stylesxml/office:font-face-decls" />
    <x:call-template name="check">
     <x:with-param name="stylesfontfaces" select="$stylesfontfaces"/>
     <x:with-param name="fontfaces" select="$stylesfontfaces|office:font-face-decls"/>
     <x:with-param name="officestyles" select="$stylesxml/office:styles"/>
     <x:with-param name="stylesautostyles" select="$stylesxml/office:automatic-styles"/>
     <x:with-param name="contentautostyles" select="office:automatic-styles"/>
     <x:with-param name="masterstyles" select="$stylesxml/office:master-styles"/>
     <x:with-param name="body" select="office:body"/>
    </x:call-template>
   </x:template>
  </x:stylesheet>
 </xsl:template>

</xsl:stylesheet>
