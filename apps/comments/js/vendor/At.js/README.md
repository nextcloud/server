**An autocompletion library to autocomplete mentions, smileys etc. just like on Github!**  
[![Build Status](https://travis-ci.org/ichord/At.js.png)](https://travis-ci.org/ichord/At.js)

#### Notice

At.js now **depends on** [Caret.js](https://github.com/ichord/Caret.js).  
Please read [**CHANGELOG.md**](CHANGELOG.md) for more details if you are going to update to new version.

### Demo
http://ichord.github.com/At.js

### Documentation
https://github.com/ichord/At.js/wiki

### Compatibility

* `textarea` - Chrome, Safari, Firefox, IE7+ (maybe IE6)
* `contentEditable` - Chrome, Safari, Firefox, IE9+

### Features Preview

* Support IE 7+ for **textarea**.
* Supports HTML5  [**contentEditable**](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/Content_Editable) elements (NOT including IE 8)
* Can listen to any character and not just '@'. Can set up multiple listeners for different characters with different behavior and data
* Listener events can be bound to multiple inputors.
* Format returned data using templates
* Keyboard controls in addition to mouse
    - `Tab` or `Enter` keys select the value
    - `Up` and `Down` navigate between values (and `Ctrl-P` and `Ctrl-N` also)
    - `Right` and `left` will re-search the keyword.
* Custom data handlers and template renderers using a group of configurable callbacks
* Supports AMD

### Requirements

* jQuery >= 1.7.0.
* [Caret.js](https://github.com/ichord/Caret.js)
    (You can use `Component` or `Bower` to install it.)

### Integrating with your Application

Simply include the following files in your HTML and you are good to go.

```html
<link href="css/jquery.atwho.css" rel="stylesheet">
<script src="http://code.jquery.com/jquery.js"></script>
<script src="js/jquery.caret.js"></script>
<script src="js/jquery.atwho.js"></script>
```

```javascript
$('#inputor').atwho({
    at: "@",
    data:['Peter', 'Tom', 'Anne']
})
```

#### Bower & Component
For installing using Bower you can use `jquery.atwho` and for Component please use `ichord/At.js`.

#### Rails
You can include At.js in your `Rails` application using the gem [jquery-atwho-rails](https://github.com/ichord/jquery-atwho-rails).

### Core Team Members

* [@ichord](https://twitter.com/_ichord) (twitter)

