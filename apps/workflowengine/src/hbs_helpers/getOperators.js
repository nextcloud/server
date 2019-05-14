module.exports = function(classname) {
	var check = OCA.WorkflowEngine.getCheckByClass(classname);
	if (!_.isUndefined(check)) {
		return check['operators'];
	}
	return [];
}
