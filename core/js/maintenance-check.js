// Check every 20 seconds via status.php if maintenance is over
window.setInterval(checkStatus, 20000);

function checkStatus() {
	var request = new XMLHttpRequest();
	var ocroot = location.pathname.substr(
		0, location.pathname.indexOf('index.php')
	);
	request.open("GET", ocroot+'status.php', true);
	request.send();
	request.onreadystatechange = function() {
		if (request.readyState === 4) {
			var response = request.responseText;
			var responseobj = JSON.parse(response);
			if (responseobj.maintenance === 'false') {
				window.location.reload();
			}
		}
	};
}
