(function() {
  var template = Handlebars.template, templates = OCA.Files.Templates = OCA.Files.Templates || {};
templates['detailsview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "<ul class=\"tabHeaders\">\n"
    + ((stack1 = helpers.each.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.tabHeaders : depth0),{"name":"each","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</ul>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "	<li class=\"tabHeader\" data-tabid=\""
    + alias4(((helper = (helper = helpers.tabId || (depth0 != null ? depth0.tabId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"tabId","hash":{},"data":data}) : helper)))
    + "\" tabindex=\"0\">\n	    "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.tabIcon : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n		<a href=\"#\" tabindex=\"-1\">"
    + alias4(((helper = (helper = helpers.label || (depth0 != null ? depth0.label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data}) : helper)))
    + "</a>\n	</li>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<span class=\"icon "
    + container.escapeExpression(((helper = (helper = helpers.tabIcon || (depth0 != null ? depth0.tabIcon : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"tabIcon","hash":{},"data":data}) : helper)))
    + "\"></span>";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "<div class=\"detailFileInfoContainer\"></div>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.tabHeaders : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "<div class=\"tabsContainer\"></div>\n<a class=\"close icon-close\" href=\"#\"><span class=\"hidden-visually\">"
    + container.escapeExpression(((helper = (helper = helpers.closeLabel || (depth0 != null ? depth0.closeLabel : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"closeLabel","hash":{},"data":data}) : helper)))
    + "</span></a>\n";
},"useData":true});
templates['favorite_mark'] = template({"1":function(container,depth0,helpers,partials,data) {
    return "permanent";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression, buffer = 
  "<div class=\"favorite-mark ";
  stack1 = ((helper = (helper = helpers.isFavorite || (depth0 != null ? depth0.isFavorite : depth0)) != null ? helper : alias2),(options={"name":"isFavorite","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!helpers.isFavorite) { stack1 = helpers.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "\">\n	<span class=\"icon "
    + alias4(((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"iconClass","hash":{},"data":data}) : helper)))
    + "\" />\n	<span class=\"hidden-visually\">"
    + alias4(((helper = (helper = helpers.altText || (depth0 != null ? depth0.altText : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"altText","hash":{},"data":data}) : helper)))
    + "</span>\n</div>\n";
},"useData":true});
templates['file_action_trigger'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<img class=\"svg\" alt=\""
    + alias4(((helper = (helper = helpers.altText || (depth0 != null ? depth0.altText : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"altText","hash":{},"data":data}) : helper)))
    + "\" src=\""
    + alias4(((helper = (helper = helpers.icon || (depth0 != null ? depth0.icon : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"icon","hash":{},"data":data}) : helper)))
    + "\" />\n";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.iconClass : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasDisplayName : depth0),{"name":"unless","hash":{},"fn":container.program(6, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"4":function(container,depth0,helpers,partials,data) {
    var helper;

  return "			<span class=\"icon "
    + container.escapeExpression(((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"iconClass","hash":{},"data":data}) : helper)))
    + "\" />\n";
},"6":function(container,depth0,helpers,partials,data) {
    var helper;

  return "			<span class=\"hidden-visually\">"
    + container.escapeExpression(((helper = (helper = helpers.altText || (depth0 != null ? depth0.altText : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"altText","hash":{},"data":data}) : helper)))
    + "</span>\n";
},"8":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<span> "
    + container.escapeExpression(((helper = (helper = helpers.displayName || (depth0 != null ? depth0.displayName : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"displayName","hash":{},"data":data}) : helper)))
    + "</span>";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<a class=\"action action-"
    + alias4(((helper = (helper = helpers.nameLowerCase || (depth0 != null ? depth0.nameLowerCase : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"nameLowerCase","hash":{},"data":data}) : helper)))
    + "\" href=\"#\" data-action=\""
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.icon : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(3, data, 0),"data":data})) != null ? stack1 : "")
    + "	"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.displayName : depth0),{"name":"if","hash":{},"fn":container.program(8, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n</a>\n";
},"useData":true});
templates['fileactionsmenu'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li class=\""
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.inline : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " action-"
    + alias4(((helper = (helper = helpers.nameLowerCase || (depth0 != null ? depth0.nameLowerCase : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"nameLowerCase","hash":{},"data":data}) : helper)))
    + "-container\">\n			<a href=\"#\" class=\"menuitem action action-"
    + alias4(((helper = (helper = helpers.nameLowerCase || (depth0 != null ? depth0.nameLowerCase : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"nameLowerCase","hash":{},"data":data}) : helper)))
    + " permanent\" data-action=\""
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "\">\n				"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.icon : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.program(6, data, 0),"data":data})) != null ? stack1 : "")
    + "				<span>"
    + alias4(((helper = (helper = helpers.displayName || (depth0 != null ? depth0.displayName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"displayName","hash":{},"data":data}) : helper)))
    + "</span>\n			</a>\n		</li>\n";
},"2":function(container,depth0,helpers,partials,data) {
    return "hidden";
},"4":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<img class=\"icon\" src=\""
    + container.escapeExpression(((helper = (helper = helpers.icon || (depth0 != null ? depth0.icon : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"icon","hash":{},"data":data}) : helper)))
    + "\"/>\n";
},"6":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.iconClass : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.program(9, data, 0),"data":data})) != null ? stack1 : "");
},"7":function(container,depth0,helpers,partials,data) {
    var helper;

  return "						<span class=\"icon "
    + container.escapeExpression(((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"iconClass","hash":{},"data":data}) : helper)))
    + "\"></span>\n";
},"9":function(container,depth0,helpers,partials,data) {
    return "						<span class=\"no-icon\"></span>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "<ul>\n"
    + ((stack1 = helpers.each.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.items : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</ul>\n";
},"useData":true});
templates['filemultiselectmenu'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li class=\"item-"
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "\">\n			<a href=\"#\" class=\"menuitem action "
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + " permanent\" data-action=\""
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.iconClass : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.program(4, data, 0),"data":data})) != null ? stack1 : "")
    + "				<span class=\"label\">"
    + alias4(((helper = (helper = helpers.displayName || (depth0 != null ? depth0.displayName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"displayName","hash":{},"data":data}) : helper)))
    + "</span>\n			</a>\n		</li>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var helper;

  return "					<span class=\"icon "
    + container.escapeExpression(((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"iconClass","hash":{},"data":data}) : helper)))
    + "\"></span>\n";
},"4":function(container,depth0,helpers,partials,data) {
    return "					<span class=\"no-icon\"></span>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "<ul>\n"
    + ((stack1 = helpers.each.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.items : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</ul>\n";
},"useData":true});
templates['filesummary'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<span class=\"info\">\n	<span class=\"dirinfo\"></span>\n	<span class=\"connector\">"
    + container.escapeExpression(((helper = (helper = helpers.connectorLabel || (depth0 != null ? depth0.connectorLabel : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"connectorLabel","hash":{},"data":data}) : helper)))
    + "</span>\n	<span class=\"fileinfo\"></span>\n	<span class=\"hiddeninfo\"></span>\n	<span class=\"filter\"></span>\n</span>\n";
},"useData":true});
templates['mainfileinfodetailsview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<a href=\"#\" class=\"action action-favorite favorite permanent\">\n				<span class=\"icon "
    + alias4(((helper = (helper = helpers.starClass || (depth0 != null ? depth0.starClass : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"starClass","hash":{},"data":data}) : helper)))
    + "\" title=\""
    + alias4(((helper = (helper = helpers.starAltText || (depth0 != null ? depth0.starAltText : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"starAltText","hash":{},"data":data}) : helper)))
    + "\"></span>\n			</a>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<span class=\"size\" title=\""
    + alias4(((helper = (helper = helpers.altSize || (depth0 != null ? depth0.altSize : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"altSize","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.size || (depth0 != null ? depth0.size : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"size","hash":{},"data":data}) : helper)))
    + "</span>, ";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"thumbnailContainer\"><a href=\"#\" class=\"thumbnail action-default\"><div class=\"stretcher\"/></a></div>\n<div class=\"file-details-container\">\n	<div class=\"fileName\">\n		<h3 title=\""
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "\" class=\"ellipsis\">"
    + alias4(((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data}) : helper)))
    + "</h3>\n		<a class=\"permalink\" href=\""
    + alias4(((helper = (helper = helpers.permalink || (depth0 != null ? depth0.permalink : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"permalink","hash":{},"data":data}) : helper)))
    + "\" title=\""
    + alias4(((helper = (helper = helpers.permalinkTitle || (depth0 != null ? depth0.permalinkTitle : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"permalinkTitle","hash":{},"data":data}) : helper)))
    + "\" data-clipboard-text=\""
    + alias4(((helper = (helper = helpers.permalink || (depth0 != null ? depth0.permalink : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"permalink","hash":{},"data":data}) : helper)))
    + "\">\n			<span class=\"icon icon-clippy\"></span>\n			<span class=\"hidden-visually\">"
    + alias4(((helper = (helper = helpers.permalinkTitle || (depth0 != null ? depth0.permalinkTitle : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"permalinkTitle","hash":{},"data":data}) : helper)))
    + "</span>\n		</a>\n	</div>\n	<div class=\"file-details ellipsis\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasFavoriteAction : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasSize : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "<span class=\"date live-relative-timestamp\" data-timestamp=\""
    + alias4(((helper = (helper = helpers.timestamp || (depth0 != null ? depth0.timestamp : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"timestamp","hash":{},"data":data}) : helper)))
    + "\" title=\""
    + alias4(((helper = (helper = helpers.altDate || (depth0 != null ? depth0.altDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"altDate","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.date || (depth0 != null ? depth0.date : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"date","hash":{},"data":data}) : helper)))
    + "</span>\n	</div>\n</div>\n<div class=\"hidden permalink-field\">\n	<input type=\"text\" value=\""
    + alias4(((helper = (helper = helpers.permalink || (depth0 != null ? depth0.permalink : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"permalink","hash":{},"data":data}) : helper)))
    + "\" placeholder=\""
    + alias4(((helper = (helper = helpers.permalinkTitle || (depth0 != null ? depth0.permalinkTitle : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"permalinkTitle","hash":{},"data":data}) : helper)))
    + "\" readonly=\"readonly\"/>\n</div>\n";
},"useData":true});
templates['newfilemenu'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li>\n			<a href=\"#\" class=\"menuitem\" data-templatename=\""
    + alias4(((helper = (helper = helpers.templateName || (depth0 != null ? depth0.templateName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"templateName","hash":{},"data":data}) : helper)))
    + "\" data-filetype=\""
    + alias4(((helper = (helper = helpers.fileType || (depth0 != null ? depth0.fileType : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"fileType","hash":{},"data":data}) : helper)))
    + "\" data-action=\""
    + alias4(((helper = (helper = helpers.id || (depth0 != null ? depth0.id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data}) : helper)))
    + "\"><span class=\"icon "
    + alias4(((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"iconClass","hash":{},"data":data}) : helper)))
    + " svg\"></span><span class=\"displayname\">"
    + alias4(((helper = (helper = helpers.displayName || (depth0 != null ? depth0.displayName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"displayName","hash":{},"data":data}) : helper)))
    + "</span></a>\n		</li>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<ul>\n	<li>\n		<label for=\"file_upload_start\" class=\"menuitem\" data-action=\"upload\" title=\""
    + alias4(((helper = (helper = helpers.uploadMaxHumanFilesize || (depth0 != null ? depth0.uploadMaxHumanFilesize : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"uploadMaxHumanFilesize","hash":{},"data":data}) : helper)))
    + "\" tabindex=\"0\"><span class=\"svg icon icon-upload\"></span><span class=\"displayname\">"
    + alias4(((helper = (helper = helpers.uploadLabel || (depth0 != null ? depth0.uploadLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"uploadLabel","hash":{},"data":data}) : helper)))
    + "</span></label>\n	</li>\n"
    + ((stack1 = helpers.each.call(alias1,(depth0 != null ? depth0.items : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</ul>\n";
},"useData":true});
templates['newfilemenu_filename_form'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<form class=\"filenameform\">\n	<input id=\""
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-input-"
    + alias4(((helper = (helper = helpers.fileType || (depth0 != null ? depth0.fileType : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"fileType","hash":{},"data":data}) : helper)))
    + "\" type=\"text\" value=\""
    + alias4(((helper = (helper = helpers.fileName || (depth0 != null ? depth0.fileName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"fileName","hash":{},"data":data}) : helper)))
    + "\" autocomplete=\"off\" autocapitalize=\"off\">\n	<input type=\"submit\" value=\" \" class=\"icon-confirm\" />\n</form>\n";
},"useData":true});
templates['operationprogressbar'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<div id=\"uploadprogressbar\">\n	<em class=\"label outer\" style=\"display:none\"></em>\n</div>\n<button class=\"stop icon-close\" style=\"display:none\">\n	<span class=\"hidden-visually\">"
    + container.escapeExpression(((helper = (helper = helpers.textCancelButton || (depth0 != null ? depth0.textCancelButton : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"textCancelButton","hash":{},"data":data}) : helper)))
    + "</span>\n</button>\n";
},"useData":true});
templates['operationprogressbarlabel'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<em class=\"label\">\n	<span class=\"desktop\">"
    + alias4(((helper = (helper = helpers.textDesktop || (depth0 != null ? depth0.textDesktop : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"textDesktop","hash":{},"data":data}) : helper)))
    + "</span>\n	<span class=\"mobile\">"
    + alias4(((helper = (helper = helpers.textMobile || (depth0 != null ? depth0.textMobile : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"textMobile","hash":{},"data":data}) : helper)))
    + "</span>\n</em>\n";
},"useData":true});
templates['template_addbutton'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<a href=\"#\" class=\"button new\">\n	<span class=\"icon "
    + alias4(((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"iconClass","hash":{},"data":data}) : helper)))
    + "\"></span>\n	<span class=\"hidden-visually\">"
    + alias4(((helper = (helper = helpers.addText || (depth0 != null ? depth0.addText : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"addText","hash":{},"data":data}) : helper)))
    + "</span>\n</a>\n";
},"useData":true});
})();