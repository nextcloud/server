###
  Implement Github like autocomplete mentions
  http://ichord.github.com/At.js

  Copyright (c) 2013 chord.luo@gmail.com
  Licensed under the MIT license.
###

###
本插件操作 textarea 或者 input 内的插入符
只实现了获得插入符在文本框中的位置，我设置
插入符的位置.
###
"use strict";

pluginName = 'caret'

class EditableCaret
  constructor: (@$inputor) ->
    @domInputor = @$inputor[0]

  # NOTE: Duck type
  setPos: (pos) -> @domInputor
  getIEPosition: -> this.getPosition()
  getPosition: ->
    offset = this.getOffset()
    inputor_offset = @$inputor.offset()
    offset.left -= inputor_offset.left
    offset.top -= inputor_offset.top
    offset

  getOldIEPos: ->
    textRange = oDocument.selection.createRange()
    preCaretTextRange = oDocument.body.createTextRange()
    preCaretTextRange.moveToElementText(@domInputor)
    preCaretTextRange.setEndPoint("EndToEnd", textRange)
    preCaretTextRange.text.length

  getPos: ->
    if range = this.range() # Major Browser and IE > 10
      clonedRange = range.cloneRange()
      clonedRange.selectNodeContents(@domInputor)
      clonedRange.setEnd(range.endContainer, range.endOffset)
      pos = clonedRange.toString().length
      clonedRange.detach()
      pos
    else if oDocument.selection #IE < 9
      this.getOldIEPos()

  getOldIEOffset: ->
    range = oDocument.selection.createRange().duplicate()
    range.moveStart "character", -1
    rect = range.getBoundingClientRect()
    { height: rect.bottom - rect.top, left: rect.left, top: rect.top }

  getOffset: (pos) ->
    if oWindow.getSelection and range = this.range()
      # endContainer would be the inputor in Firefox at the begnning of a line
      if range.endOffset - 1 > 0 and range.endContainer is not @domInputor
        clonedRange = range.cloneRange()
        clonedRange.setStart(range.endContainer, range.endOffset - 1)
        clonedRange.setEnd(range.endContainer, range.endOffset)
        rect = clonedRange.getBoundingClientRect()
        offset = { height: rect.height, left: rect.left + rect.width, top: rect.top }
        clonedRange.detach()
      # At the begnning of the inputor, the offset height is 0 in Chrome and Safari
      # This work fine in all browers but except while the inputor break a line into two (wrapped line).
      # so we can't use it in all cases.
      if !offset or offset?.height == 0
        clonedRange = range.cloneRange()
        shadowCaret = $ oDocument.createTextNode "|"
        clonedRange.insertNode shadowCaret[0]
        clonedRange.selectNode shadowCaret[0]
        rect = clonedRange.getBoundingClientRect()
        offset = {height: rect.height, left: rect.left, top: rect.top }
        shadowCaret.remove()
        clonedRange.detach()
    else if oDocument.selection # ie < 9
      offset = this.getOldIEOffset()

    if offset
      offset.top += $(oWindow).scrollTop()
      offset.left += $(oWindow).scrollLeft()

    offset

  range: ->
    return unless oWindow.getSelection
    sel = oWindow.getSelection()
    if sel.rangeCount > 0 then sel.getRangeAt(0) else null


class InputCaret

  constructor: (@$inputor) ->
    @domInputor = @$inputor[0]

  getIEPos: ->
    # https://github.com/ichord/Caret.js/wiki/Get-pos-of-caret-in-IE
    inputor = @domInputor
    range = oDocument.selection.createRange()
    pos = 0
    # selection should in the inputor.
    if range and range.parentElement() is inputor
      normalizedValue = inputor.value.replace /\r\n/g, "\n"
      len = normalizedValue.length
      textInputRange = inputor.createTextRange()
      textInputRange.moveToBookmark range.getBookmark()
      endRange = inputor.createTextRange()
      endRange.collapse false
      if textInputRange.compareEndPoints("StartToEnd", endRange) > -1
        pos = len
      else
        pos = -textInputRange.moveStart "character", -len
    pos

  getPos: ->
    if oDocument.selection then this.getIEPos() else @domInputor.selectionStart

  setPos: (pos) ->
    inputor = @domInputor
    if oDocument.selection #IE
      range = inputor.createTextRange()
      range.move "character", pos
      range.select()
    else if inputor.setSelectionRange
      inputor.setSelectionRange pos, pos
    inputor

  getIEOffset: (pos) ->
    textRange = @domInputor.createTextRange()
    pos ||= this.getPos()
    textRange.move('character', pos)

    x = textRange.boundingLeft
    y = textRange.boundingTop
    h = textRange.boundingHeight

    {left: x, top: y, height: h}

  getOffset: (pos) ->
    $inputor = @$inputor
    if oDocument.selection
      offset = this.getIEOffset(pos)
      offset.top += $(oWindow).scrollTop() + $inputor.scrollTop()
      offset.left += $(oWindow).scrollLeft() + $inputor.scrollLeft()
      offset
    else
      offset = $inputor.offset()
      position = this.getPosition(pos)
      offset =
        left: offset.left + position.left - $inputor.scrollLeft()
        top: offset.top + position.top - $inputor.scrollTop()
        height: position.height

  getPosition: (pos)->
    $inputor = @$inputor
    format = (value) ->
      value = value.replace(/<|>|`|"|&/g, '?').replace(/\r\n|\r|\n/g,"<br/>")
      if /firefox/i.test navigator.userAgent
        value = value.replace(/\s/g, '&nbsp;')
      value
    pos = this.getPos() if pos is undefined
    start_range = $inputor.val().slice(0, pos)
    end_range = $inputor.val().slice(pos)
    html = "<span style='position: relative; display: inline;'>"+format(start_range)+"</span>"
    html += "<span id='caret' style='position: relative; display: inline;'>|</span>"
    html += "<span style='position: relative; display: inline;'>"+format(end_range)+"</span>"

    mirror = new Mirror($inputor)
    at_rect = mirror.create(html).rect()

  getIEPosition: (pos) ->
    offset = this.getIEOffset pos
    inputorOffset = @$inputor.offset()
    x = offset.left - inputorOffset.left
    y = offset.top - inputorOffset.top
    h = offset.height

    {left: x, top: y, height: h}

# @example
#   mirror = new Mirror($("textarea#inputor"))
#   html = "<p>We will get the rect of <span>@</span>icho</p>"
#   mirror.create(html).rect()
class Mirror
  css_attr: [
    "borderBottomWidth",
    "borderLeftWidth",
    "borderRightWidth",
    "borderTopStyle",
    "borderRightStyle",
    "borderBottomStyle",
    "borderLeftStyle",
    "borderTopWidth",
    "boxSizing",
    "fontFamily",
    "fontSize",
    "fontWeight",
    "height",
    "letterSpacing",
    "lineHeight",
    "marginBottom",
    "marginLeft",
    "marginRight",
    "marginTop",
    "outlineWidth",
    "overflow",
    "overflowX",
    "overflowY",
    "paddingBottom",
    "paddingLeft",
    "paddingRight",
    "paddingTop",
    "textAlign",
    "textOverflow",
    "textTransform",
    "whiteSpace",
    "wordBreak",
    "wordWrap",
  ]

  constructor: (@$inputor) ->

  mirrorCss: ->
    css =
      position: 'absolute'
      left: -9999
      top: 0
      zIndex: -20000
    if @$inputor.prop( 'tagName' ) == 'TEXTAREA'
      @css_attr.push( 'width' )
    $.each @css_attr, (i,p) =>
      css[p] = @$inputor.css p
    css

  create: (html) ->
    @$mirror = $('<div></div>')
    @$mirror.css this.mirrorCss()
    @$mirror.html(html)
    @$inputor.after(@$mirror)
    this

  # 获得标记的位置
  #
  # @return [Object] 标记的坐标
  #   {left: 0, top: 0, bottom: 0}
  rect: ->
    $flag = @$mirror.find "#caret"
    pos = $flag.position()
    rect = {left: pos.left, top: pos.top, height: $flag.height() }
    @$mirror.remove()
    rect

Utils =
  contentEditable: ($inputor)->
    !!($inputor[0].contentEditable && $inputor[0].contentEditable == 'true')

methods =
  pos: (pos) ->
    if pos or pos == 0
      this.setPos pos
    else
      this.getPos()

  position: (pos) ->
    if oDocument.selection then this.getIEPosition pos else this.getPosition pos

  offset: (pos) ->
    offset = this.getOffset(pos)
    offset

oDocument = null
oWindow = null
oFrame = null
setContextBy = (settings) ->
  if iframe = settings?.iframe
    oFrame = iframe
    oWindow = iframe.contentWindow
    oDocument = iframe.contentDocument || oWindow.document
  else
    oFrame = undefined
    oWindow = window
    oDocument = document
discoveryIframeOf = ($dom) ->
  oDocument = $dom[0].ownerDocument
  oWindow = oDocument.defaultView || oDocument.parentWindow
  try
    oFrame = oWindow.frameElement
  catch error
    # throws error in cross-domain iframes

$.fn.caret = (method, value, settings) ->
  # http://stackoverflow.com/questions/16010204/get-reference-of-window-object-from-a-dom-element
  if methods[method]
    if $.isPlainObject(value)
      setContextBy value
      value = undefined
    else
      setContextBy settings
    caret = if Utils.contentEditable(this) then new EditableCaret(this) else new InputCaret(this)
    methods[method].apply caret, [value]
  else
    $.error "Method #{method} does not exist on jQuery.caret"



$.fn.caret.EditableCaret = EditableCaret
$.fn.caret.InputCaret = InputCaret
$.fn.caret.Utils = Utils
$.fn.caret.apis = methods
