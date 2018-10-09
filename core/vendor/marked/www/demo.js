$(function () {
	var $inputElem = $('#input');
	var $outputTypeElem = $('#outputType');
	var $previewElem = $('#preview');
	var $htmlElem = $('#html');
	var $lexerElem = $('#lexer');
	var $syntaxElem = $('#syntax');
	var inputDirty = true;
	var $activeElem = null;

	if (top.document.location.href.match(/\?blank=1$/)) {
		$inputElem.val('');
	}

	$outputTypeElem.change(function () {
		$('#rightContainer .pane').hide();
		$activeElem = $('#' + $outputTypeElem.val()).show();
	}).change();

	var noticeChange = function () {
		inputDirty = true;
	};
	$inputElem.
		change(noticeChange).
		keyup(noticeChange).
		keypress(noticeChange).
		keydown(noticeChange);

	var jsonString = function (input) {
		var output = (input + '').
			replace(/\n/g, '\\n').
			replace(/\r/g, '\\r').
			replace(/\t/g, '\\t').
			replace(/\f/g, '\\f').
			replace(/[\\"']/g, '\\$&').
			replace(/\u0000/g, '\\0');
		return '"' + output + '"';
	};

	var getScrollSize = function () {
		var e = $activeElem[0];

		return e.scrollHeight - e.clientHeight;
	};
	var getScrollPercent = function () {
		var size = getScrollSize();

		if (size <= 0) {
			return 1;
		}

		return $activeElem.scrollTop() / size;
	};
	var setScrollPercent = function (percent) {
		$activeElem.scrollTop(percent * getScrollSize());
	};

	var delayTime = 1;
	var checkForChanges = function () {
		if (inputDirty) {
			inputDirty = false;
			var startTime = new Date();

			// Save scroll position
			var scrollPercent = getScrollPercent();

			// Convert
			var markdown = $inputElem.val();
			var lexed = marked.lexer(markdown);

			// Grab lexed output and convert to a string before the parser
			// destroys the data
			var lexedList = [];

			for (var i = 0; i < lexed.length; i ++) {
				var lexedLine = [];
				for (var j in lexed[i]) {
					lexedLine.push(j + ":" + jsonString(lexed[i][j]));
				}
				lexedList.push("{" + lexedLine.join(", ") + "}");
			}

			var parsed = marked.parser(lexed);

			// Assign
			$previewElem.html(parsed);
			$htmlElem.val(parsed);
			$lexerElem.val(lexedList.join("\n"));

			// Set the scroll percent
			setScrollPercent(scrollPercent);

			var endTime = new Date();
			delayTime = endTime - startTime;
			if (delayTime < 50) {
				delayTime = 50;
			} else if (delayTime > 500) {
				delayTime = 1000;
			}
		}
		window.setTimeout(checkForChanges, delayTime);
	};
	checkForChanges();
	setScrollPercent(0);
});
