/*
  jQuery placeholder plugin
  by Andrey Kuzmin, @unsoundscapes

  Based on existing plugin http://mths.be/placeholder by @mathias
  and this demo http://robertnyman.com/2011/05/02/ by @robertnyman

  Adopted to toggle placeholder on user input instead of focus

  Released under the MIT license
*/

(function (factory) {
  'use strict';

  if (typeof define === 'function' && define.amd) {
    // AMD. Register as anonymous module.
    define(['jquery'], factory)
  } else {
    // Browser globals.
    factory(jQuery)
  }
}(function ($) {
  'use strict';

  var isInputSupported = 'placeholder' in document.createElement('input')
    , isTextareaSupported = 'placeholder' in document.createElement('textarea')
    , $placeholders = $()

  function getAttributes (element) {
    // Return an object of element attributes
    var newAttrs = {}
      , rinlinejQuery = /^jQuery\d+$/

    $.each(element.attributes, function () {
      if (this.specified && !rinlinejQuery.test(this.name)) {
        newAttrs[this.name] = this.value
      }
    })
    return newAttrs
  }

  function setCaretTo (element, index) {
    // Set caret to specified @index
    if (element.createTextRange) {
      var range = element.createTextRange()
      range.move('character', index)
      range.select()
    } else if (element.selectionStart !== null) {
      element.focus()
      element.setSelectionRange(index, index)
    }
  }


  function Placeholder (element, options) {
    this.options = options || {}
    this.$replacement = this.$element = $(element)
    this.initialize.apply(this, arguments)
    // Cache all elements with placeholders
    $placeholders = $placeholders.add(element)
  }

  Placeholder.prototype = {

    initialize: function () {
      this.isHidden = true
      this.placeholderAttr = this.$element.attr('placeholder')
      // do not mess with default behavior
      this.$element.removeAttr('placeholder')
      this.isPassword = this.$element.is('[type=password]')
      if (this.isPassword) this.makeReplacement()
      this.$replacement.on({
        'keydown.placeholder': $.proxy(this.hide, this)
      , 'focus.placeholder drop.placeholder click.placeholder': $.proxy(this.setCaret, this)
      })
      this.$element.on({
        'blur.placeholder keyup.placeholder': $.proxy(this.show, this)
      })
      this.show()
    }

    // Set or get input value
    // Setting value toggles placeholder
  , val: function (value) {
      if (value === undefined) {
        return this.isHidden ? this.$element[0].value : '';
      }
      if (value === '') {
        if (this.isHidden) {
          this.$element[0].value = value
          this.show()
        }
      } else {
        if (!this.isHidden) this.hide()
        this.$element[0].value = value
      }
      return this
    }

    // Hide placeholder at user input
  , hide: function (e) {
      var isActiveElement = this.$replacement.is(':focus')
      if (this.isHidden) return;
      if (!e || !(e.shiftKey && e.keyCode === 16) && e.keyCode !== 9) {
        this.isHidden = true
        if (this.isPassword) {
          this.$replacement.before(this.$element.show()).hide()
          if (isActiveElement) this.$element.focus()
        } else {
          this.$element[0].value = ''
          this.$element.removeClass(this.options.className)
        }
      }
    }

    // Show placeholder on blur and keyup
  , show: function (e) {
      var isActiveElement = this.$element.is(':focus')
      if (!this.isHidden) return;
      if (this.$element[0].value === '') {
        this.isHidden = false
        if (this.isPassword) {
          this.$element.before(this.$replacement.show()).hide()
          if (isActiveElement) this.$replacement.focus()
        } else {
          this.$element[0].value = this.placeholderAttr
          this.$element.addClass(this.options.className)
          if (isActiveElement) this.setCaret(e)
        }
      }
    }

    // Set caret at the beginning of the input
  , setCaret: function (e) {
      if (e && !this.isHidden) {
        setCaretTo(this.$replacement[0], 0)
        e.preventDefault()
      }
    }

    // Make and return replacement element
  , makeReplacement: function () {
      // we can't use $.fn.clone because ie <= 8 doesn't allow type change
      var replacementAttributes =
        $.extend(
          getAttributes(this.$element[0])
        , { 'type': 'text'
          , 'value': this.placeholderAttr
          }
        )

      // replacement should not have input name
      delete replacementAttributes.name

      this.$replacement = $('<input>', replacementAttributes)
        .data('placeholder', this)
        .addClass(this.options.className)

      return this.$replacement;
    }

  }


  // Override jQuery val and prop hooks
  $.valHooks.input = $.valHooks.textarea = $.propHooks.value = {
    get: function (element) {
      var placeholder = $(element).data('placeholder')
      return placeholder ? placeholder.val() : element.value;
    }
  , set: function (element, value) {
      var placeholder = $(element).data('placeholder')
      return placeholder ? placeholder.val(value) : element.value = value;
    }
  }


  // Plugin definition
  $.fn.placeholder = function (option) {
    return this.each(function () {
      var $this = $(this)
        , data = $this.data('placeholder')
        , options = $.extend({}, $.fn.placeholder.defaults, typeof option === 'object' && option)

      if (!data && $this.is('[placeholder]') && (options.force ||
          !isInputSupported && $this.is('input') ||
          !isTextareaSupported && $this.is('textarea'))) {
        $this.data('placeholder', data = new Placeholder(this, options))
      }

      if (data && typeof option === 'string') data[option]()
    })
  }
  $.fn.placeholder.defaults = {
    force: false
  , className: 'placeholder'
  }
  $.fn.placeholder.Constructor = Placeholder


  // Events
  $(document).on('submit.placeholder', 'form', function () {
    // Clear the placeholder values so they don't get submitted
    $placeholders.placeholder('hide')
    // And then restore them back
    setTimeout(function () { $placeholders.placeholder('show') }, 10)
  })
  $(window).on('beforeunload.placeholder', function () {
    // Clear placeholders upon page reload
    $placeholders.placeholder('hide')
  })

  return Placeholder

}));
