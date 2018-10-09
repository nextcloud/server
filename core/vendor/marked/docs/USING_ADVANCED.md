## The `marked` function

```js
marked(markdownString [,options] [,callback])
```

|Argument              |Type         |Notes                                                                                                |
|:---------------------|:------------|:----------------------------------------------------------------------------------------------------|
|markdownString        |`string`     |String of markdown source to be compiled.                                                            |
|<a href="#options">options</a>|`object`|Hash of options. Can also use `marked.setOptions`.                                                |
|callback              |`function`   |Called when `markdownString` has been parsed. Can be used as second argument if no `options` present.|

### Alternative using reference

```js
// Create reference instance
var myMarked = require('marked');

// Set options
// `highlight` example uses `highlight.js`
myMarked.setOptions({
  renderer: new marked.Renderer(),
  highlight: function(code) {
  	return require('highlight.js').highlightAuto(code).value;
  },
  pedantic: false,
  gfm: true,
  tables: true,
  breaks: false,
  sanitize: false,
  smartLists: true,
  smartypants: false,
  xhtml: false
});

// Compile
console.log(myMarked('I am using __markdown__.'));
```

<h2 id="options">Options</h2>

|Member     |Type      |Notes                                                                                                                        |
|:----------|:---------|:----------------------------------------------------------------------------------------------------------------------------|
|highlight  |`function`|A function to highlight code blocks. See also: <a href="#highlight">Asynchronous highlighting</a>.                           |
|renderer   |`object`  |An object containing functions to render tokens to HTML. See [extensibility](https://github.com/markedjs/marked/blob/master/docs/USING_PRO.md) for more details. Default: `new Renderer()`|
|pedantic   |`boolean` |Conform to obscure parts of `markdown.pl` as much as possible. Don't fix original markdown bugs or behavior. Default: `false`|
|gfm        |`boolean` |Use approved [GitHub Flavored Markdown (GFM) specification](https://github.github.com/gfm/).                                 |
|tables     |`boolean` |Use [GFM Tables extension](https://github.github.com/gfm/#tables-extension-). Requires `gfm` be `true`.                      |
|breaks     |`boolean` |Use GFM [hard](https://github.github.com/gfm/#hard-line-breaks) and [soft](https://github.github.com/gfm/#soft-line-breaks) line breaks. Requires `gfm` be `true`. Default: `false`|
|sanitize   |`boolean` |Ignore HTML passed into `markdownString` (sanitize the input). Default: `false`                                              |
|smartlists |`boolean` |Use smarter list behavior than those found in `markdown.pl`. Default: `true`                                                 |
|smartypants|`boolean` |Use "smart" typographic punctuation for things like quotes and dashes.                                                       |
|xhtml      |`boolean` |Self-close the tags for void elements (&lt;br/&gt;, &lt;img/&gt;, etc.) with a "/" as required by XHTML. Default: `false`    |

<h2 id="highlight">Asynchronous highlighting</h2>

Unlike `highlight.js` the `pygmatize.js` library uses asynchronous highlighting. This example demonstrates that marked is agnostic when it comes to the highlighter you use.

```js
myMarked.setOptions({
  highlight: function(code, lang, callback) {
    require('pygmentize-bundled') ({ lang: lang, format: 'html' }, code, function (err, result) {
      callback(err, result.toString());
    });
  }
});

console.log(myMarked(markdownString));
```

In both examples, `code` is a `string` representing the section of code to pass to the highlighter. In this example, `lang` is a `string` informing the highlighter what programming lnaguage to use for the `code` and `callback` is the `function` the asynchronous highlighter will call once complete.