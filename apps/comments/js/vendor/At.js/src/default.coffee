KEY_CODE =
  ESC: 27
  TAB: 9
  ENTER: 13
  CTRL: 17
  A: 65
  P: 80
  N: 78
  LEFT: 37
  UP:38
  RIGHT: 39
  DOWN: 40
  BACKSPACE: 8
  SPACE: 32

# Functions set for handling and rendering the data.
# Others developers can override these methods to tweak At.js such as matcher.
# We can override them in `callbacks` settings.
#
# @mixin
#
# The context of these functions is `$.atwho.Controller` object and they are called in this sequences:
#
# [beforeSave, matcher, filter, remoteFilter, sorter, tplEvl, highlighter, beforeInsert, afterMatchFailed]
#
DEFAULT_CALLBACKS =

  # It would be called to restructure the data before At.js invokes `Model#save` to save data
  # By default, At.js will convert it to a Hash Array.
  #
  # @param data [Array] data to refacotor.
  # @return [Array] Data after refactor.
  beforeSave: (data) ->
    Controller.arrayToDefaultHash data

  # It would be called to match the `flag`.
  # It will match at start of line or after whitespace
  #
  # @param flag [String] current `flag` ("@", etc)
  # @param subtext [String] Text from start to current caret position.
  # @param should_startWithSpace [boolean] accept white space as beginning of match.
  # @param acceptSpaceBar [boolean] accept a space bar in the center of match,
  #                                 so you can match a first and last name, for ex.
  #
  # @return [String | null] Matched result.
  matcher: (flag, subtext, should_startWithSpace, acceptSpaceBar) ->
    # escape RegExp
    flag = flag.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&")
    flag = '(?:^|\\s)' + flag if should_startWithSpace

    # À
    _a = decodeURI("%C3%80")
    # ÿ
    _y = decodeURI("%C3%BF")
    space = if acceptSpaceBar then "\ " else ""
    regexp = new RegExp "#{flag}([A-Za-z#{_a}-#{_y}0-9_#{space}\'\.\+\-]*)$|#{flag}([^\\x00-\\xff]*)$",'gi'
    match = regexp.exec subtext
    if match then match[2] || match[1] else null

  # ---------------------

  # Filter data by matched string.
  #
  # @param query [String] Matched string.
  # @param data [Array] data list
  # @param searchKey [String] at char for searching.
  #
  # @return [Array] result data.
  filter: (query, data, searchKey) ->
    # !!null #=> false; !!undefined #=> false; !!'' #=> false;
    _results = []
    for item in data
      _results.push item if ~new String(item[searchKey]).toLowerCase().indexOf query.toLowerCase()
    _results

  # If a function is given, At.js will invoke it if local filter can not find any data
  #
  # @param params [String] matched query
  # @param callback [Function] callback to render page.
  remoteFilter: null
  # remoteFilter: (query, callback) ->
  #   $.ajax url,
  #     data: params
  #     success: (data) ->
  #       callback(data)

  # Sorter data of course.
  #
  # @param query [String] matched string
  # @param items [Array] data that was refactored
  # @param searchKey [String] at char to search
  #
  # @return [Array] sorted data
  sorter: (query, items, searchKey) ->
    return items unless query

    _results = []
    for item in items
      item.atwho_order = new String(item[searchKey]).toLowerCase().indexOf query.toLowerCase()
      _results.push item if item.atwho_order > -1

    _results.sort (a,b) -> a.atwho_order - b.atwho_order

  # Evaluate the template either as a string or as a function
  # this allows someone to pass in a set of data that needs a
  # different template for different data results
  #
  # @param tpl [function] the template function or string
  # @param map [Hash] Data map to eval.
  tplEval: (tpl, map) ->
    template = tpl
    try
      template = tpl(map) unless typeof tpl == 'string'
      template.replace /\$\{([^\}]*)\}/g, (tag, key, pos) -> map[key]
    catch error
      ""


  # Highlight the `matched query` string.
  #
  # @param li [String] HTML String after eval.
  # @param query [String] matched query.
  #
  # @return [String] highlighted string.
  highlighter: (li, query) ->
    return li if not query
    regexp = new RegExp(">\\s*([^\<]*?)(" + query.replace("+","\\+") + ")([^\<]*)\\s*<", 'ig')
    li.replace regexp, (str, $1, $2, $3) -> '> '+$1+'<strong>' + $2 + '</strong>'+$3+' <'

  # What to do before inserting item's value into inputor.
  #
  # @param value [String] content to insert
  # @param $li [jQuery Object] the chosen item
  # @param e [event Object] from the user selection (keyDown or click)
  beforeInsert: (value, $li, e) ->
    value

  # You can adjust the menu's offset here.
  #
  # @param offset [Hash] offset will be applied to menu
  # beforeReposition: (offset) ->
  #   offset.left += 10
  #   offset.top += 10
  #   offset
  beforeReposition: (offset) -> offset

  afterMatchFailed: (at, el) ->
