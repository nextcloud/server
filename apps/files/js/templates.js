(function() {
  var template = Handlebars.template, templates = OCA.Files.Templates = OCA.Files.Templates || {};
templates['filesummary'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<span class=\"info\">\n	<span class=\"dirinfo\"></span>\n	<span class=\"connector\">"
    + container.escapeExpression(((helper = (helper = helpers.connectorLabel || (depth0 != null ? depth0.connectorLabel : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"connectorLabel","hash":{},"data":data}) : helper)))
    + "</span>\n	<span class=\"fileinfo\"></span>\n	<span class=\"hiddeninfo\"></span>\n	<span class=\"filter\"></span>\n</span>\n";
},"useData":true});
})();