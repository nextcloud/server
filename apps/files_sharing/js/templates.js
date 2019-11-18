(function() {
  var template = Handlebars.template, templates = OCA.Sharing.Templates = OCA.Sharing.Templates || {};
templates['files_drop'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=container.propertyIsEnumerable;

  return "		<span class=\"icon-loading-small\"></span> "
    + container.escapeExpression(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"name","hash":{},"data":data,"loc":{"start":{"line":3,"column":43},"end":{"line":3,"column":51}}}) : helper)))
    + "\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=container.propertyIsEnumerable, alias2=depth0 != null ? depth0 : (container.nullContext || {}), alias3=container.hooks.helperMissing, alias4="function", alias5=container.escapeExpression;

  return "		<img src=\""
    + alias5(((helper = (helper = helpers.iconSrc || (depth0 != null ? depth0.iconSrc : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"iconSrc","hash":{},"data":data,"loc":{"start":{"line":5,"column":12},"end":{"line":5,"column":23}}}) : helper)))
    + "\"/> "
    + alias5(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":5,"column":27},"end":{"line":5,"column":35}}}) : helper)))
    + "\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=container.propertyIsEnumerable, alias2=depth0 != null ? depth0 : (container.nullContext || {}), alias3=container.hooks.helperMissing, alias4="function", alias5=container.escapeExpression;

  return "<li data-toggle=\"tooltip\" title=\""
    + alias5(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":1,"column":33},"end":{"line":1,"column":41}}}) : helper)))
    + "\" data-name=\""
    + alias5(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":1,"column":54},"end":{"line":1,"column":62}}}) : helper)))
    + "\">\n"
    + ((stack1 = helpers["if"].call(alias2,(depth0 != null ? depth0.isUploading : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(3, data, 0),"data":data,"loc":{"start":{"line":2,"column":1},"end":{"line":6,"column":8}}})) != null ? stack1 : "")
    + "</li>\n";
},"useData":true});
})();