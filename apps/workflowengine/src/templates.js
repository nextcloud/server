(function() {
  var template = Handlebars.template, templates = OCA.WorkflowEngine.Templates = OCA.WorkflowEngine.Templates || {};
templates['operation'] = template({"1":function(container,depth0,helpers,partials,data) {
    return " modified";
},"3":function(container,depth0,helpers,partials,data) {
    return "			<span class=\"button-delete icon-delete\"></span>\n";
},"5":function(container,depth0,helpers,partials,data,blockParams,depths) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<div class=\"check\" data-id=\""
    + alias4(((helper = (helper = helpers.index || (data && data.index)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"index","hash":{},"data":data}) : helper)))
    + "\">\n				<select class=\"check-class\">\n"
    + ((stack1 = helpers.each.call(alias1,(depths[1] != null ? depths[1].classes : depths[1]),{"name":"each","hash":{},"fn":container.program(6, data, 0, blockParams, depths),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "				</select>\n				<select class=\"check-operator\">\n"
    + ((stack1 = helpers.each.call(alias1,(helpers.getOperators || (depth0 && depth0.getOperators) || alias2).call(alias1,(depth0 != null ? depth0["class"] : depth0),{"name":"getOperators","hash":{},"data":data}),{"name":"each","hash":{},"fn":container.program(8, data, 0, blockParams, depths),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "				</select>\n				<input type=\"text\" class=\"check-value\" value=\""
    + alias4(((helper = (helper = helpers.value || (depth0 != null ? depth0.value : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"value","hash":{},"data":data}) : helper)))
    + "\">\n				<span class=\"button-delete-check icon-delete\"></span>\n			</div>\n";
},"6":function(container,depth0,helpers,partials,data,blockParams,depths) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "						<option value=\""
    + alias4(((helper = (helper = helpers["class"] || (depth0 != null ? depth0["class"] : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"class","hash":{},"data":data}) : helper)))
    + "\" "
    + ((stack1 = (helpers.selectItem || (depth0 && depth0.selectItem) || alias2).call(alias1,(depth0 != null ? depth0["class"] : depth0),(depths[1] != null ? depths[1]["class"] : depths[1]),{"name":"selectItem","hash":{},"data":data})) != null ? stack1 : "")
    + ">"
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "</option>\n";
},"8":function(container,depth0,helpers,partials,data,blockParams,depths) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "						<option value=\""
    + alias4(((helper = (helper = helpers.operator || (depth0 != null ? depth0.operator : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"operator","hash":{},"data":data}) : helper)))
    + "\" "
    + ((stack1 = (helpers.selectItem || (depth0 && depth0.selectItem) || alias2).call(alias1,(depth0 != null ? depth0.operator : depth0),(depths[1] != null ? depths[1].operator : depths[1]),{"name":"selectItem","hash":{},"data":data})) != null ? stack1 : "")
    + ">"
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "</option>\n";
},"10":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return ((stack1 = helpers["if"].call(alias1,((stack1 = (depth0 != null ? depth0.operation : depth0)) != null ? stack1.id : stack1),{"name":"if","hash":{},"fn":container.program(11, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		<button class=\"button-save pull-right\">"
    + container.escapeExpression(((helper = (helper = helpers.saveTXT || (depth0 != null ? depth0.saveTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"saveTXT","hash":{},"data":data}) : helper)))
    + "</button>\n";
},"11":function(container,depth0,helpers,partials,data) {
    var helper;

  return "			<button class=\"button-reset pull-right\">"
    + container.escapeExpression(((helper = (helper = helpers.resetTXT || (depth0 != null ? depth0.resetTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"resetTXT","hash":{},"data":data}) : helper)))
    + "</button>\n";
},"13":function(container,depth0,helpers,partials,data) {
    var helper;

  return "		<span class=\"icon-loading-small pull-right\"></span>\n		<span class=\"pull-right\">"
    + container.escapeExpression(((helper = (helper = helpers.savingTXT || (depth0 != null ? depth0.savingTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"savingTXT","hash":{},"data":data}) : helper)))
    + "</span>\n	";
},"15":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.message : depth0),{"name":"if","hash":{},"fn":container.program(16, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"16":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "\n		<span class=\"msg pull-right "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.errorMessage : depth0),{"name":"if","hash":{},"fn":container.program(17, data, 0),"inverse":container.program(19, data, 0),"data":data})) != null ? stack1 : "")
    + "\">\n					"
    + container.escapeExpression(((helper = (helper = helpers.message || (depth0 != null ? depth0.message : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"message","hash":{},"data":data}) : helper)))
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.errorMessage : depth0),{"name":"if","hash":{},"fn":container.program(21, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n				</span>\n	";
},"17":function(container,depth0,helpers,partials,data) {
    return "error";
},"19":function(container,depth0,helpers,partials,data) {
    return "success";
},"21":function(container,depth0,helpers,partials,data) {
    var helper;

  return " "
    + container.escapeExpression(((helper = (helper = helpers.errorMessage || (depth0 != null ? depth0.errorMessage : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"errorMessage","hash":{},"data":data}) : helper)));
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data,blockParams,depths) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression, alias5=container.lambda;

  return "<div class=\"operation"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasChanged : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0, blockParams, depths),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\">\n	<div class=\"operation-header\">\n		<input type=\"text\" class=\"operation-name\" placeholder=\""
    + alias4(((helper = (helper = helpers.shortRuleDescTXT || (depth0 != null ? depth0.shortRuleDescTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shortRuleDescTXT","hash":{},"data":data}) : helper)))
    + "\" value=\""
    + alias4(alias5(((stack1 = (depth0 != null ? depth0.operation : depth0)) != null ? stack1.name : stack1), depth0))
    + "\" />\n		<input type=\"text\" class=\"operation-operation\" value=\""
    + alias4(alias5(((stack1 = (depth0 != null ? depth0.operation : depth0)) != null ? stack1.operation : stack1), depth0))
    + "\" />\n"
    + ((stack1 = helpers["if"].call(alias1,((stack1 = (depth0 != null ? depth0.operation : depth0)) != null ? stack1.id : stack1),{"name":"if","hash":{},"fn":container.program(3, data, 0, blockParams, depths),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "	</div>\n\n	<div class=\"checks\">\n"
    + ((stack1 = helpers.each.call(alias1,((stack1 = (depth0 != null ? depth0.operation : depth0)) != null ? stack1.checks : stack1),{"name":"each","hash":{},"fn":container.program(5, data, 0, blockParams, depths),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "	</div>\n	<button class=\"button-add\">"
    + alias4(((helper = (helper = helpers.addRuleTXT || (depth0 != null ? depth0.addRuleTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"addRuleTXT","hash":{},"data":data}) : helper)))
    + "</button>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasChanged : depth0),{"name":"if","hash":{},"fn":container.program(10, data, 0, blockParams, depths),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.saving : depth0),{"name":"if","hash":{},"fn":container.program(13, data, 0, blockParams, depths),"inverse":container.program(15, data, 0, blockParams, depths),"data":data})) != null ? stack1 : "")
    + "\n</div>\n";
},"useData":true,"useDepths":true});
templates['operations'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<div class=\"operations\"></div>\n<button class=\"button-add-operation\">"
    + container.escapeExpression(((helper = (helper = helpers.addRuleGroupTXT || (depth0 != null ? depth0.addRuleGroupTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"addRuleGroupTXT","hash":{},"data":data}) : helper)))
    + "</button>\n";
},"useData":true});
})();