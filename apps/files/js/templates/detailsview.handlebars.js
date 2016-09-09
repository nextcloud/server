(function() {
  var template = Handlebars.template, templates = OCA.Files.Templates = OCA.Files.Templates || {};
templates['detailsview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "<ul class=\"tabHeaders\">\n"
    + ((stack1 = helpers.each.call(depth0 != null ? depth0 : {},(depth0 != null ? depth0.tabHeaders : depth0),{"name":"each","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</ul>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : {}, alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "	<li class=\"tabHeader\" data-tabid=\""
    + alias4(((helper = (helper = helpers.tabId || (depth0 != null ? depth0.tabId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"tabId","hash":{},"data":data}) : helper)))
    + "\" data-tabindex=\""
    + alias4(((helper = (helper = helpers.tabIndex || (depth0 != null ? depth0.tabIndex : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"tabIndex","hash":{},"data":data}) : helper)))
    + "\">\n		<a href=\"#\">"
    + alias4(((helper = (helper = helpers.label || (depth0 != null ? depth0.label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data}) : helper)))
    + "</a>\n	</li>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : {};

  return "<div class=\"detailFileInfoContainer\"></div>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.tabHeaders : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "<div class=\"tabsContainer\">\n</div>\n<a class=\"close icon-close\" href=\"#\" alt=\""
    + container.escapeExpression(((helper = (helper = helpers.closeLabel || (depth0 != null ? depth0.closeLabel : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"closeLabel","hash":{},"data":data}) : helper)))
    + "\"></a>\n";
},"useData":true});
})();
