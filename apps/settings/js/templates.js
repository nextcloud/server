(function() {
  var template = Handlebars.template, templates = OC.Settings.Templates = OC.Settings.Templates || {};
templates['federationscopemenu'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=container.propertyIsEnumerable, alias2=depth0 != null ? depth0 : (container.nullContext || {}), alias3=container.hooks.helperMissing, alias4="function", alias5=container.escapeExpression;

  return "		<li tabindex=\"0\">\n			<a href=\"#\" class=\"menuitem action action-"
    + alias5(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":4,"column":45},"end":{"line":4,"column":53}}}) : helper)))
    + " permanent "
    + ((stack1 = helpers["if"].call(alias2,(depth0 != null ? depth0.active : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":4,"column":64},"end":{"line":4,"column":91}}})) != null ? stack1 : "")
    + "\" data-action=\""
    + alias5(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":4,"column":106},"end":{"line":4,"column":114}}}) : helper)))
    + "\">\n"
    + ((stack1 = helpers["if"].call(alias2,(depth0 != null ? depth0.iconClass : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.program(6, data, 0),"data":data,"loc":{"start":{"line":5,"column":4},"end":{"line":9,"column":11}}})) != null ? stack1 : "")
    + "				<p>\n					<strong class=\"menuitem-text\">"
    + alias5(((helper = (helper = helpers.displayName || (depth0 != null ? depth0.displayName : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"displayName","hash":{},"data":data,"loc":{"start":{"line":11,"column":35},"end":{"line":11,"column":50}}}) : helper)))
    + "</strong><br>\n					<span class=\"menuitem-text-detail\">"
    + alias5(((helper = (helper = helpers.tooltip || (depth0 != null ? depth0.tooltip : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"tooltip","hash":{},"data":data,"loc":{"start":{"line":12,"column":40},"end":{"line":12,"column":51}}}) : helper)))
    + "</span>\n				</p>\n			</a>\n		</li>\n";
},"2":function(container,depth0,helpers,partials,data) {
    return "active";
},"4":function(container,depth0,helpers,partials,data) {
    var helper, alias1=container.propertyIsEnumerable;

  return "					<span class=\"icon "
    + container.escapeExpression(((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"iconClass","hash":{},"data":data,"loc":{"start":{"line":6,"column":23},"end":{"line":6,"column":36}}}) : helper)))
    + "\"></span>\n";
},"6":function(container,depth0,helpers,partials,data) {
    return "					<span class=\"no-icon\"></span>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=container.propertyIsEnumerable;

  return "<ul>\n"
    + ((stack1 = helpers.each.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.items : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":1},"end":{"line":16,"column":10}}})) != null ? stack1 : "")
    + "</ul>\n";
},"useData":true});
})();