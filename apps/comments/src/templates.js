(function() {
  var template = Handlebars.template, templates = OCA.Comments.Templates = OCA.Comments.Templates || {};
templates['filesplugin'] = template({"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<a class=\"action action-comment permanent\" title=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"countMessage") || (depth0 != null ? lookupProperty(depth0,"countMessage") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"countMessage","hash":{},"data":data,"loc":{"start":{"line":1,"column":50},"end":{"line":1,"column":66}}}) : helper)))
    + "\" href=\"#\">\n	<img class=\"svg\" src=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"iconUrl") || (depth0 != null ? lookupProperty(depth0,"iconUrl") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"iconUrl","hash":{},"data":data,"loc":{"start":{"line":2,"column":23},"end":{"line":2,"column":34}}}) : helper)))
    + "\"/>\n</a>\n";
},"useData":true});
})();