(function() {
  var template = Handlebars.template, templates = OCA.Versions.Templates = OCA.Versions.Templates || {};
templates['item'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "				<div class=\"version-details\">\n					<span class=\"size has-tooltip\" title=\""
    + alias4(((helper = (helper = helpers.altSize || (depth0 != null ? depth0.altSize : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"altSize","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.humanReadableSize || (depth0 != null ? depth0.humanReadableSize : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"humanReadableSize","hash":{},"data":data}) : helper)))
    + "</span>\n				</div>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<a href=\"#\" class=\"revertVersion\" title=\""
    + alias4(((helper = (helper = helpers.revertLabel || (depth0 != null ? depth0.revertLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"revertLabel","hash":{},"data":data}) : helper)))
    + "\"><img src=\""
    + alias4(((helper = (helper = helpers.revertIconUrl || (depth0 != null ? depth0.revertIconUrl : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"revertIconUrl","hash":{},"data":data}) : helper)))
    + "\" /></a>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression, alias5=helpers.blockHelperMissing, buffer = 
  "<li data-revision=\""
    + alias4(((helper = (helper = helpers.timestamp || (depth0 != null ? depth0.timestamp : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"timestamp","hash":{},"data":data}) : helper)))
    + "\">\n	<div>\n		<div class=\"preview-container\">\n			<img class=\"preview\" src=\""
    + alias4(((helper = (helper = helpers.previewUrl || (depth0 != null ? depth0.previewUrl : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"previewUrl","hash":{},"data":data}) : helper)))
    + "\" width=\"44\" height=\"44\"/>\n		</div>\n		<div class=\"version-container\">\n			<div>\n				<a href=\""
    + alias4(((helper = (helper = helpers.downloadUrl || (depth0 != null ? depth0.downloadUrl : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"downloadUrl","hash":{},"data":data}) : helper)))
    + "\" class=\"downloadVersion\" download=\""
    + alias4(((helper = (helper = helpers.downloadName || (depth0 != null ? depth0.downloadName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"downloadName","hash":{},"data":data}) : helper)))
    + "\"><img src=\""
    + alias4(((helper = (helper = helpers.downloadIconUrl || (depth0 != null ? depth0.downloadIconUrl : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"downloadIconUrl","hash":{},"data":data}) : helper)))
    + "\" />\n					<span class=\"versiondate has-tooltip live-relative-timestamp\" data-timestamp=\""
    + alias4(((helper = (helper = helpers.millisecondsTimestamp || (depth0 != null ? depth0.millisecondsTimestamp : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"millisecondsTimestamp","hash":{},"data":data}) : helper)))
    + "\" title=\""
    + alias4(((helper = (helper = helpers.formattedTimestamp || (depth0 != null ? depth0.formattedTimestamp : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"formattedTimestamp","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.relativeTimestamp || (depth0 != null ? depth0.relativeTimestamp : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"relativeTimestamp","hash":{},"data":data}) : helper)))
    + "</span>\n				</a>\n			</div>\n";
  stack1 = ((helper = (helper = helpers.hasDetails || (depth0 != null ? depth0.hasDetails : depth0)) != null ? helper : alias2),(options={"name":"hasDetails","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!helpers.hasDetails) { stack1 = alias5.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  buffer += "		</div>\n";
  stack1 = ((helper = (helper = helpers.canRevert || (depth0 != null ? depth0.canRevert : depth0)) != null ? helper : alias2),(options={"name":"canRevert","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!helpers.canRevert) { stack1 = alias5.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "	</div>\n</li>\n";
},"useData":true});
templates['template'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<ul class=\"versions\"></ul>\n<div class=\"clear-float\"></div>\n<div class=\"empty hidden\">\n	<div class=\"emptycontent\">\n		<div class=\"icon-history\"></div>\n		<p>"
    + alias4(((helper = (helper = helpers.emptyResultLabel || (depth0 != null ? depth0.emptyResultLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"emptyResultLabel","hash":{},"data":data}) : helper)))
    + "</p>\n	</div>\n</div>\n<input type=\"button\" class=\"showMoreVersions hidden\" value=\""
    + alias4(((helper = (helper = helpers.moreVersionsLabel || (depth0 != null ? depth0.moreVersionsLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"moreVersionsLabel","hash":{},"data":data}) : helper)))
    + "\" name=\"show-more-versions\" id=\"show-more-versions\" />\n<div class=\"loading hidden\" style=\"height: 50px\"></div>\n";
},"useData":true});
})();