(function() {
  var template = Handlebars.template, templates = OC.SystemTags.Templates = OC.SystemTags.Templates || {};
templates['result'] = template({"1":function(container,depth0,helpers,partials,data) {
    return " new-item";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=container.propertyIsEnumerable;

  return "		<span class=\"label\">"
    + ((stack1 = ((helper = (helper = helpers.tagMarkup || (depth0 != null ? depth0.tagMarkup : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"tagMarkup","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "</span>\n";
},"5":function(container,depth0,helpers,partials,data) {
    var helper, alias1=container.propertyIsEnumerable;

  return "		<span class=\"label\">"
    + container.escapeExpression(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"name","hash":{},"data":data}) : helper)))
    + "</span>\n";
},"7":function(container,depth0,helpers,partials,data) {
    var helper, alias1=container.propertyIsEnumerable;

  return "		<span class=\"systemtags-actions\">\n			<a href=\"#\" class=\"rename icon icon-rename\" title=\""
    + container.escapeExpression(((helper = (helper = helpers.renameTooltip || (depth0 != null ? depth0.renameTooltip : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"renameTooltip","hash":{},"data":data}) : helper)))
    + "\"></a>\n		</span>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=container.propertyIsEnumerable, alias2=depth0 != null ? depth0 : (container.nullContext || {}), alias3=container.hooks.helperMissing, alias4="function", buffer = 
  "<span class=\"systemtags-item"
    + ((stack1 = helpers["if"].call(alias2,(depth0 != null ? depth0.isNew : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\" data-id=\""
    + container.escapeExpression(((helper = (helper = helpers.id || (depth0 != null ? depth0.id : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"id","hash":{},"data":data}) : helper)))
    + "\">\n<span class=\"checkmark icon icon-checkmark\"></span>\n"
    + ((stack1 = helpers["if"].call(alias2,(depth0 != null ? depth0.isAdmin : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.program(5, data, 0),"data":data})) != null ? stack1 : "");
  stack1 = ((helper = (helper = helpers.allowActions || (depth0 != null ? depth0.allowActions : depth0)) != null ? helper : alias3),(options={"name":"allowActions","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data}),(typeof helper === alias4 ? helper.call(alias2,options) : helper));
  if (!helpers.allowActions) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</span>\n";
},"useData":true});
templates['result_form'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=container.propertyIsEnumerable;

  return "		<a href=\"#\" class=\"delete icon icon-delete\" title=\""
    + container.escapeExpression(((helper = (helper = helpers.deleteTooltip || (depth0 != null ? depth0.deleteTooltip : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"deleteTooltip","hash":{},"data":data}) : helper)))
    + "\"></a>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=container.propertyIsEnumerable, alias2=depth0 != null ? depth0 : (container.nullContext || {}), alias3=container.hooks.helperMissing, alias4="function", alias5=container.escapeExpression;

  return "<form class=\"systemtags-rename-form\">\n	 <label class=\"hidden-visually\" for=\""
    + alias5(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-rename-input\">"
    + alias5(((helper = (helper = helpers.renameLabel || (depth0 != null ? depth0.renameLabel : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"renameLabel","hash":{},"data":data}) : helper)))
    + "</label>\n	<input id=\""
    + alias5(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-rename-input\" type=\"text\" value=\""
    + alias5(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias3),(typeof helper === alias4 ? helper.call(alias2,{"name":"name","hash":{},"data":data}) : helper)))
    + "\">\n"
    + ((stack1 = helpers["if"].call(alias2,(depth0 != null ? depth0.isAdmin : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</form>\n";
},"useData":true});
templates['selection'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=container.propertyIsEnumerable;

  return "	<span class=\"label\">"
    + ((stack1 = ((helper = (helper = helpers.tagMarkup || (depth0 != null ? depth0.tagMarkup : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"tagMarkup","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "</span>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=container.propertyIsEnumerable;

  return "	<span class=\"label\">"
    + container.escapeExpression(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"name","hash":{},"data":data}) : helper)))
    + "</span>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=container.propertyIsEnumerable;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isAdmin : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(3, data, 0),"data":data})) != null ? stack1 : "");
},"useData":true});
})();