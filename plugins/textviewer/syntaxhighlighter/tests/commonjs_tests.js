/**
 * This is a CommonJS compatibility test. You can run this file with node.
 */
require.paths.unshift(__dirname + '/../scripts');

var sys = require('sys'),
	shSyntaxHighlighter = require('shCore').SyntaxHighlighter,
	code = 'test',
	brushes = [
		'AS3',
		'AppleScript',
		'Bash',
		'CSharp',
		'ColdFusion',
		'Cpp',
		'Css',
		'Delphi',
		'Diff',
		'Erlang',
		'Groovy',
		'JScript',
		'Java',
		'JavaFX',
		'Perl',
		'Php',
		'Plain',
		'PowerShell',
		'Python',
		'Ruby',
		'Sass',
		'Scala',
		'Sql',
		'Vb',
		'Xml'
	]
	;

brushes.sort();

for (var i = 0; i < brushes.length; i++)
{
	var name = brushes[i],
		brush = require('shBrush' + name).Brush
		;
		
	brush = new brush();
	brush.init({ toolbar: false });
	
	var result = brush.getHtml(code);
	
	sys.puts(name + (result != null ? ': ok' : ': NOT OK'));
}
