const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, s as useSlots, A as onMounted, P as nextTick, B as onUnmounted, z as watch, n as computed, o as openBlock, f as createElementBlock, x as createVNode, p as createSlots, C as renderList, w as withCtx, i as renderSlot, I as normalizeProps, J as guardReactiveProps, u as unref, m as mergeProps, Z as isRef, c as createBlock, ad as Transition, h as createCommentVNode, K as resolveDynamicComponent, a4 as Teleport, v as normalizeClass, y as ref, a0 as toRef, a3 as h, ar as render, g as createBaseVNode, M as withModifiers, F as Fragment, N as normalizeStyle, t as toDisplayString, X as toValue, a2 as getCurrentInstance, O as reactive, D as useAttrs, j as createTextVNode, a6 as getCurrentScope, a7 as onScopeDispose, as as onBeforeUpdate, E as withDirectives, G as vShow, V as withKeys, k as useModel, q as mergeModels, l as useTemplateRef } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { F as mdiChevronUp, G as mdiChevronDown, n as mdiChevronRight, u as mdiChevronLeft, H as mdiClock, I as mdiCalendarBlank, N as NcButton, b as mdiClose } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { k as getFirstDay, O as getDayNamesMin, P as getDayNames } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { r as register, a4 as t44, b as t, c as createElementId, a5 as t13, N as NcIconSvgWrapper, _ as _export_sfc } from "./Web-BOM4en5n.chunk.mjs";
import { N as NcSelect } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import { b as getCanonicalLocale } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./index-o76qk6sn.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
const millisecondsInWeek = 6048e5;
const millisecondsInDay = 864e5;
const millisecondsInMinute = 6e4;
const millisecondsInHour = 36e5;
const millisecondsInSecond = 1e3;
const constructFromSymbol = /* @__PURE__ */ Symbol.for("constructDateFrom");
function constructFrom(date, value) {
  if (typeof date === "function") return date(value);
  if (date && typeof date === "object" && constructFromSymbol in date)
    return date[constructFromSymbol](value);
  if (date instanceof Date) return new date.constructor(value);
  return new Date(value);
}
function toDate(argument, context) {
  return constructFrom(context || argument, argument);
}
function addDays(date, amount, options) {
  const _date = toDate(date, options?.in);
  if (isNaN(amount)) return constructFrom(options?.in || date, NaN);
  if (!amount) return _date;
  _date.setDate(_date.getDate() + amount);
  return _date;
}
function addMonths(date, amount, options) {
  const _date = toDate(date, options?.in);
  if (isNaN(amount)) return constructFrom(date, NaN);
  if (!amount) {
    return _date;
  }
  const dayOfMonth = _date.getDate();
  const endOfDesiredMonth = constructFrom(date, _date.getTime());
  endOfDesiredMonth.setMonth(_date.getMonth() + amount + 1, 0);
  const daysInMonth = endOfDesiredMonth.getDate();
  if (dayOfMonth >= daysInMonth) {
    return endOfDesiredMonth;
  } else {
    _date.setFullYear(
      endOfDesiredMonth.getFullYear(),
      endOfDesiredMonth.getMonth(),
      dayOfMonth
    );
    return _date;
  }
}
function add(date, duration, options) {
  const {
    years = 0,
    months = 0,
    weeks = 0,
    days = 0,
    hours = 0,
    minutes = 0,
    seconds = 0
  } = duration;
  const _date = toDate(date, options?.in);
  const dateWithMonths = months || years ? addMonths(_date, months + years * 12) : _date;
  const dateWithDays = days || weeks ? addDays(dateWithMonths, days + weeks * 7) : dateWithMonths;
  const minutesToAdd = minutes + hours * 60;
  const secondsToAdd = seconds + minutesToAdd * 60;
  const msToAdd = secondsToAdd * 1e3;
  return constructFrom(date, +dateWithDays + msToAdd);
}
function addMilliseconds(date, amount, options) {
  return constructFrom(date, +toDate(date) + amount);
}
function addHours(date, amount, options) {
  return addMilliseconds(date, amount * millisecondsInHour);
}
let defaultOptions = {};
function getDefaultOptions$1() {
  return defaultOptions;
}
function startOfWeek(date, options) {
  const defaultOptions2 = getDefaultOptions$1();
  const weekStartsOn = options?.weekStartsOn ?? options?.locale?.options?.weekStartsOn ?? defaultOptions2.weekStartsOn ?? defaultOptions2.locale?.options?.weekStartsOn ?? 0;
  const _date = toDate(date, options?.in);
  const day = _date.getDay();
  const diff = (day < weekStartsOn ? 7 : 0) + day - weekStartsOn;
  _date.setDate(_date.getDate() - diff);
  _date.setHours(0, 0, 0, 0);
  return _date;
}
function startOfISOWeek(date, options) {
  return startOfWeek(date, { ...options, weekStartsOn: 1 });
}
function getISOWeekYear(date, options) {
  const _date = toDate(date, options?.in);
  const year = _date.getFullYear();
  const fourthOfJanuaryOfNextYear = constructFrom(_date, 0);
  fourthOfJanuaryOfNextYear.setFullYear(year + 1, 0, 4);
  fourthOfJanuaryOfNextYear.setHours(0, 0, 0, 0);
  const startOfNextYear = startOfISOWeek(fourthOfJanuaryOfNextYear);
  const fourthOfJanuaryOfThisYear = constructFrom(_date, 0);
  fourthOfJanuaryOfThisYear.setFullYear(year, 0, 4);
  fourthOfJanuaryOfThisYear.setHours(0, 0, 0, 0);
  const startOfThisYear = startOfISOWeek(fourthOfJanuaryOfThisYear);
  if (_date.getTime() >= startOfNextYear.getTime()) {
    return year + 1;
  } else if (_date.getTime() >= startOfThisYear.getTime()) {
    return year;
  } else {
    return year - 1;
  }
}
function getTimezoneOffsetInMilliseconds(date) {
  const _date = toDate(date);
  const utcDate = new Date(
    Date.UTC(
      _date.getFullYear(),
      _date.getMonth(),
      _date.getDate(),
      _date.getHours(),
      _date.getMinutes(),
      _date.getSeconds(),
      _date.getMilliseconds()
    )
  );
  utcDate.setUTCFullYear(_date.getFullYear());
  return +date - +utcDate;
}
function normalizeDates(context, ...dates) {
  const normalize = constructFrom.bind(
    null,
    dates.find((date) => typeof date === "object")
  );
  return dates.map(normalize);
}
function startOfDay(date, options) {
  const _date = toDate(date, options?.in);
  _date.setHours(0, 0, 0, 0);
  return _date;
}
function differenceInCalendarDays(laterDate, earlierDate, options) {
  const [laterDate_, earlierDate_] = normalizeDates(
    options?.in,
    laterDate,
    earlierDate
  );
  const laterStartOfDay = startOfDay(laterDate_);
  const earlierStartOfDay = startOfDay(earlierDate_);
  const laterTimestamp = +laterStartOfDay - getTimezoneOffsetInMilliseconds(laterStartOfDay);
  const earlierTimestamp = +earlierStartOfDay - getTimezoneOffsetInMilliseconds(earlierStartOfDay);
  return Math.round((laterTimestamp - earlierTimestamp) / millisecondsInDay);
}
function startOfISOWeekYear(date, options) {
  const year = getISOWeekYear(date, options);
  const fourthOfJanuary = constructFrom(date, 0);
  fourthOfJanuary.setFullYear(year, 0, 4);
  fourthOfJanuary.setHours(0, 0, 0, 0);
  return startOfISOWeek(fourthOfJanuary);
}
function addQuarters(date, amount, options) {
  return addMonths(date, amount * 3, options);
}
function addYears(date, amount, options) {
  return addMonths(date, amount * 12, options);
}
function compareAsc(dateLeft, dateRight) {
  const diff = +toDate(dateLeft) - +toDate(dateRight);
  if (diff < 0) return -1;
  else if (diff > 0) return 1;
  return diff;
}
function isDate(value) {
  return value instanceof Date || typeof value === "object" && Object.prototype.toString.call(value) === "[object Date]";
}
function isValid(date) {
  return !(!isDate(date) && typeof date !== "number" || isNaN(+toDate(date)));
}
function getQuarter(date, options) {
  const _date = toDate(date, options?.in);
  const quarter = Math.trunc(_date.getMonth() / 3) + 1;
  return quarter;
}
function differenceInCalendarYears(laterDate, earlierDate, options) {
  const [laterDate_, earlierDate_] = normalizeDates(
    options?.in,
    laterDate,
    earlierDate
  );
  return laterDate_.getFullYear() - earlierDate_.getFullYear();
}
function differenceInYears(laterDate, earlierDate, options) {
  const [laterDate_, earlierDate_] = normalizeDates(
    options?.in,
    laterDate,
    earlierDate
  );
  const sign = compareAsc(laterDate_, earlierDate_);
  const diff = Math.abs(differenceInCalendarYears(laterDate_, earlierDate_));
  laterDate_.setFullYear(1584);
  earlierDate_.setFullYear(1584);
  const partial = compareAsc(laterDate_, earlierDate_) === -sign;
  const result = sign * (diff - +partial);
  return result === 0 ? 0 : result;
}
function normalizeInterval(context, interval) {
  const [start, end] = normalizeDates(context, interval.start, interval.end);
  return { start, end };
}
function eachDayOfInterval(interval, options) {
  const { start, end } = normalizeInterval(options?.in, interval);
  let reversed = +start > +end;
  const endTime = reversed ? +start : +end;
  const date = reversed ? end : start;
  date.setHours(0, 0, 0, 0);
  let step = 1;
  const dates = [];
  while (+date <= endTime) {
    dates.push(constructFrom(start, date));
    date.setDate(date.getDate() + step);
    date.setHours(0, 0, 0, 0);
  }
  return reversed ? dates.reverse() : dates;
}
function startOfQuarter(date, options) {
  const _date = toDate(date, options?.in);
  const currentMonth = _date.getMonth();
  const month = currentMonth - currentMonth % 3;
  _date.setMonth(month, 1);
  _date.setHours(0, 0, 0, 0);
  return _date;
}
function eachQuarterOfInterval(interval, options) {
  const { start, end } = normalizeInterval(options?.in, interval);
  let reversed = +start > +end;
  const endTime = reversed ? +startOfQuarter(start) : +startOfQuarter(end);
  let date = reversed ? startOfQuarter(end) : startOfQuarter(start);
  let step = 1;
  const dates = [];
  while (+date <= endTime) {
    dates.push(constructFrom(start, date));
    date = addQuarters(date, step);
  }
  return reversed ? dates.reverse() : dates;
}
function startOfMonth(date, options) {
  const _date = toDate(date, options?.in);
  _date.setDate(1);
  _date.setHours(0, 0, 0, 0);
  return _date;
}
function endOfYear(date, options) {
  const _date = toDate(date, options?.in);
  const year = _date.getFullYear();
  _date.setFullYear(year + 1, 0, 0);
  _date.setHours(23, 59, 59, 999);
  return _date;
}
function startOfYear(date, options) {
  const date_ = toDate(date, options?.in);
  date_.setFullYear(date_.getFullYear(), 0, 1);
  date_.setHours(0, 0, 0, 0);
  return date_;
}
function endOfWeek(date, options) {
  const defaultOptions2 = getDefaultOptions$1();
  const weekStartsOn = options?.weekStartsOn ?? options?.locale?.options?.weekStartsOn ?? defaultOptions2.weekStartsOn ?? defaultOptions2.locale?.options?.weekStartsOn ?? 0;
  const _date = toDate(date, options?.in);
  const day = _date.getDay();
  const diff = (day < weekStartsOn ? -7 : 0) + 6 - (day - weekStartsOn);
  _date.setDate(_date.getDate() + diff);
  _date.setHours(23, 59, 59, 999);
  return _date;
}
function endOfQuarter(date, options) {
  const _date = toDate(date, options?.in);
  const currentMonth = _date.getMonth();
  const month = currentMonth - currentMonth % 3 + 3;
  _date.setMonth(month, 0);
  _date.setHours(23, 59, 59, 999);
  return _date;
}
const formatDistanceLocale = {
  lessThanXSeconds: {
    one: "less than a second",
    other: "less than {{count}} seconds"
  },
  xSeconds: {
    one: "1 second",
    other: "{{count}} seconds"
  },
  halfAMinute: "half a minute",
  lessThanXMinutes: {
    one: "less than a minute",
    other: "less than {{count}} minutes"
  },
  xMinutes: {
    one: "1 minute",
    other: "{{count}} minutes"
  },
  aboutXHours: {
    one: "about 1 hour",
    other: "about {{count}} hours"
  },
  xHours: {
    one: "1 hour",
    other: "{{count}} hours"
  },
  xDays: {
    one: "1 day",
    other: "{{count}} days"
  },
  aboutXWeeks: {
    one: "about 1 week",
    other: "about {{count}} weeks"
  },
  xWeeks: {
    one: "1 week",
    other: "{{count}} weeks"
  },
  aboutXMonths: {
    one: "about 1 month",
    other: "about {{count}} months"
  },
  xMonths: {
    one: "1 month",
    other: "{{count}} months"
  },
  aboutXYears: {
    one: "about 1 year",
    other: "about {{count}} years"
  },
  xYears: {
    one: "1 year",
    other: "{{count}} years"
  },
  overXYears: {
    one: "over 1 year",
    other: "over {{count}} years"
  },
  almostXYears: {
    one: "almost 1 year",
    other: "almost {{count}} years"
  }
};
const formatDistance = (token, count, options) => {
  let result;
  const tokenValue = formatDistanceLocale[token];
  if (typeof tokenValue === "string") {
    result = tokenValue;
  } else if (count === 1) {
    result = tokenValue.one;
  } else {
    result = tokenValue.other.replace("{{count}}", count.toString());
  }
  if (options?.addSuffix) {
    if (options.comparison && options.comparison > 0) {
      return "in " + result;
    } else {
      return result + " ago";
    }
  }
  return result;
};
function buildFormatLongFn(args) {
  return (options = {}) => {
    const width = options.width ? String(options.width) : args.defaultWidth;
    const format2 = args.formats[width] || args.formats[args.defaultWidth];
    return format2;
  };
}
const dateFormats = {
  full: "EEEE, MMMM do, y",
  long: "MMMM do, y",
  medium: "MMM d, y",
  short: "MM/dd/yyyy"
};
const timeFormats = {
  full: "h:mm:ss a zzzz",
  long: "h:mm:ss a z",
  medium: "h:mm:ss a",
  short: "h:mm a"
};
const dateTimeFormats = {
  full: "{{date}} 'at' {{time}}",
  long: "{{date}} 'at' {{time}}",
  medium: "{{date}}, {{time}}",
  short: "{{date}}, {{time}}"
};
const formatLong = {
  date: buildFormatLongFn({
    formats: dateFormats,
    defaultWidth: "full"
  }),
  time: buildFormatLongFn({
    formats: timeFormats,
    defaultWidth: "full"
  }),
  dateTime: buildFormatLongFn({
    formats: dateTimeFormats,
    defaultWidth: "full"
  })
};
const formatRelativeLocale = {
  lastWeek: "'last' eeee 'at' p",
  yesterday: "'yesterday at' p",
  today: "'today at' p",
  tomorrow: "'tomorrow at' p",
  nextWeek: "eeee 'at' p",
  other: "P"
};
const formatRelative = (token, _date, _baseDate, _options) => formatRelativeLocale[token];
function buildLocalizeFn(args) {
  return (value, options) => {
    const context = options?.context ? String(options.context) : "standalone";
    let valuesArray;
    if (context === "formatting" && args.formattingValues) {
      const defaultWidth = args.defaultFormattingWidth || args.defaultWidth;
      const width = options?.width ? String(options.width) : defaultWidth;
      valuesArray = args.formattingValues[width] || args.formattingValues[defaultWidth];
    } else {
      const defaultWidth = args.defaultWidth;
      const width = options?.width ? String(options.width) : args.defaultWidth;
      valuesArray = args.values[width] || args.values[defaultWidth];
    }
    const index = args.argumentCallback ? args.argumentCallback(value) : value;
    return valuesArray[index];
  };
}
const eraValues = {
  narrow: ["B", "A"],
  abbreviated: ["BC", "AD"],
  wide: ["Before Christ", "Anno Domini"]
};
const quarterValues = {
  narrow: ["1", "2", "3", "4"],
  abbreviated: ["Q1", "Q2", "Q3", "Q4"],
  wide: ["1st quarter", "2nd quarter", "3rd quarter", "4th quarter"]
};
const monthValues = {
  narrow: ["J", "F", "M", "A", "M", "J", "J", "A", "S", "O", "N", "D"],
  abbreviated: [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec"
  ],
  wide: [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
  ]
};
const dayValues = {
  narrow: ["S", "M", "T", "W", "T", "F", "S"],
  short: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
  abbreviated: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
  wide: [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday"
  ]
};
const dayPeriodValues = {
  narrow: {
    am: "a",
    pm: "p",
    midnight: "mi",
    noon: "n",
    morning: "morning",
    afternoon: "afternoon",
    evening: "evening",
    night: "night"
  },
  abbreviated: {
    am: "AM",
    pm: "PM",
    midnight: "midnight",
    noon: "noon",
    morning: "morning",
    afternoon: "afternoon",
    evening: "evening",
    night: "night"
  },
  wide: {
    am: "a.m.",
    pm: "p.m.",
    midnight: "midnight",
    noon: "noon",
    morning: "morning",
    afternoon: "afternoon",
    evening: "evening",
    night: "night"
  }
};
const formattingDayPeriodValues = {
  narrow: {
    am: "a",
    pm: "p",
    midnight: "mi",
    noon: "n",
    morning: "in the morning",
    afternoon: "in the afternoon",
    evening: "in the evening",
    night: "at night"
  },
  abbreviated: {
    am: "AM",
    pm: "PM",
    midnight: "midnight",
    noon: "noon",
    morning: "in the morning",
    afternoon: "in the afternoon",
    evening: "in the evening",
    night: "at night"
  },
  wide: {
    am: "a.m.",
    pm: "p.m.",
    midnight: "midnight",
    noon: "noon",
    morning: "in the morning",
    afternoon: "in the afternoon",
    evening: "in the evening",
    night: "at night"
  }
};
const ordinalNumber = (dirtyNumber, _options) => {
  const number = Number(dirtyNumber);
  const rem100 = number % 100;
  if (rem100 > 20 || rem100 < 10) {
    switch (rem100 % 10) {
      case 1:
        return number + "st";
      case 2:
        return number + "nd";
      case 3:
        return number + "rd";
    }
  }
  return number + "th";
};
const localize = {
  ordinalNumber,
  era: buildLocalizeFn({
    values: eraValues,
    defaultWidth: "wide"
  }),
  quarter: buildLocalizeFn({
    values: quarterValues,
    defaultWidth: "wide",
    argumentCallback: (quarter) => quarter - 1
  }),
  month: buildLocalizeFn({
    values: monthValues,
    defaultWidth: "wide"
  }),
  day: buildLocalizeFn({
    values: dayValues,
    defaultWidth: "wide"
  }),
  dayPeriod: buildLocalizeFn({
    values: dayPeriodValues,
    defaultWidth: "wide",
    formattingValues: formattingDayPeriodValues,
    defaultFormattingWidth: "wide"
  })
};
function buildMatchFn(args) {
  return (string, options = {}) => {
    const width = options.width;
    const matchPattern = width && args.matchPatterns[width] || args.matchPatterns[args.defaultMatchWidth];
    const matchResult = string.match(matchPattern);
    if (!matchResult) {
      return null;
    }
    const matchedString = matchResult[0];
    const parsePatterns = width && args.parsePatterns[width] || args.parsePatterns[args.defaultParseWidth];
    const key = Array.isArray(parsePatterns) ? findIndex(parsePatterns, (pattern) => pattern.test(matchedString)) : (
      // [TODO] -- I challenge you to fix the type
      findKey(parsePatterns, (pattern) => pattern.test(matchedString))
    );
    let value;
    value = args.valueCallback ? args.valueCallback(key) : key;
    value = options.valueCallback ? (
      // [TODO] -- I challenge you to fix the type
      options.valueCallback(value)
    ) : value;
    const rest = string.slice(matchedString.length);
    return { value, rest };
  };
}
function findKey(object, predicate) {
  for (const key in object) {
    if (Object.prototype.hasOwnProperty.call(object, key) && predicate(object[key])) {
      return key;
    }
  }
  return void 0;
}
function findIndex(array, predicate) {
  for (let key = 0; key < array.length; key++) {
    if (predicate(array[key])) {
      return key;
    }
  }
  return void 0;
}
function buildMatchPatternFn(args) {
  return (string, options = {}) => {
    const matchResult = string.match(args.matchPattern);
    if (!matchResult) return null;
    const matchedString = matchResult[0];
    const parseResult = string.match(args.parsePattern);
    if (!parseResult) return null;
    let value = args.valueCallback ? args.valueCallback(parseResult[0]) : parseResult[0];
    value = options.valueCallback ? options.valueCallback(value) : value;
    const rest = string.slice(matchedString.length);
    return { value, rest };
  };
}
const matchOrdinalNumberPattern = /^(\d+)(th|st|nd|rd)?/i;
const parseOrdinalNumberPattern = /\d+/i;
const matchEraPatterns = {
  narrow: /^(b|a)/i,
  abbreviated: /^(b\.?\s?c\.?|b\.?\s?c\.?\s?e\.?|a\.?\s?d\.?|c\.?\s?e\.?)/i,
  wide: /^(before christ|before common era|anno domini|common era)/i
};
const parseEraPatterns = {
  any: [/^b/i, /^(a|c)/i]
};
const matchQuarterPatterns = {
  narrow: /^[1234]/i,
  abbreviated: /^q[1234]/i,
  wide: /^[1234](th|st|nd|rd)? quarter/i
};
const parseQuarterPatterns = {
  any: [/1/i, /2/i, /3/i, /4/i]
};
const matchMonthPatterns = {
  narrow: /^[jfmasond]/i,
  abbreviated: /^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i,
  wide: /^(january|february|march|april|may|june|july|august|september|october|november|december)/i
};
const parseMonthPatterns = {
  narrow: [
    /^j/i,
    /^f/i,
    /^m/i,
    /^a/i,
    /^m/i,
    /^j/i,
    /^j/i,
    /^a/i,
    /^s/i,
    /^o/i,
    /^n/i,
    /^d/i
  ],
  any: [
    /^ja/i,
    /^f/i,
    /^mar/i,
    /^ap/i,
    /^may/i,
    /^jun/i,
    /^jul/i,
    /^au/i,
    /^s/i,
    /^o/i,
    /^n/i,
    /^d/i
  ]
};
const matchDayPatterns = {
  narrow: /^[smtwf]/i,
  short: /^(su|mo|tu|we|th|fr|sa)/i,
  abbreviated: /^(sun|mon|tue|wed|thu|fri|sat)/i,
  wide: /^(sunday|monday|tuesday|wednesday|thursday|friday|saturday)/i
};
const parseDayPatterns = {
  narrow: [/^s/i, /^m/i, /^t/i, /^w/i, /^t/i, /^f/i, /^s/i],
  any: [/^su/i, /^m/i, /^tu/i, /^w/i, /^th/i, /^f/i, /^sa/i]
};
const matchDayPeriodPatterns = {
  narrow: /^(a|p|mi|n|(in the|at) (morning|afternoon|evening|night))/i,
  any: /^([ap]\.?\s?m\.?|midnight|noon|(in the|at) (morning|afternoon|evening|night))/i
};
const parseDayPeriodPatterns = {
  any: {
    am: /^a/i,
    pm: /^p/i,
    midnight: /^mi/i,
    noon: /^no/i,
    morning: /morning/i,
    afternoon: /afternoon/i,
    evening: /evening/i,
    night: /night/i
  }
};
const match = {
  ordinalNumber: buildMatchPatternFn({
    matchPattern: matchOrdinalNumberPattern,
    parsePattern: parseOrdinalNumberPattern,
    valueCallback: (value) => parseInt(value, 10)
  }),
  era: buildMatchFn({
    matchPatterns: matchEraPatterns,
    defaultMatchWidth: "wide",
    parsePatterns: parseEraPatterns,
    defaultParseWidth: "any"
  }),
  quarter: buildMatchFn({
    matchPatterns: matchQuarterPatterns,
    defaultMatchWidth: "wide",
    parsePatterns: parseQuarterPatterns,
    defaultParseWidth: "any",
    valueCallback: (index) => index + 1
  }),
  month: buildMatchFn({
    matchPatterns: matchMonthPatterns,
    defaultMatchWidth: "wide",
    parsePatterns: parseMonthPatterns,
    defaultParseWidth: "any"
  }),
  day: buildMatchFn({
    matchPatterns: matchDayPatterns,
    defaultMatchWidth: "wide",
    parsePatterns: parseDayPatterns,
    defaultParseWidth: "any"
  }),
  dayPeriod: buildMatchFn({
    matchPatterns: matchDayPeriodPatterns,
    defaultMatchWidth: "any",
    parsePatterns: parseDayPeriodPatterns,
    defaultParseWidth: "any"
  })
};
const enUS = {
  code: "en-US",
  formatDistance,
  formatLong,
  formatRelative,
  localize,
  match,
  options: {
    weekStartsOn: 0,
    firstWeekContainsDate: 1
  }
};
function getDayOfYear(date, options) {
  const _date = toDate(date, options?.in);
  const diff = differenceInCalendarDays(_date, startOfYear(_date));
  const dayOfYear = diff + 1;
  return dayOfYear;
}
function getISOWeek(date, options) {
  const _date = toDate(date, options?.in);
  const diff = +startOfISOWeek(_date) - +startOfISOWeekYear(_date);
  return Math.round(diff / millisecondsInWeek) + 1;
}
function getWeekYear(date, options) {
  const _date = toDate(date, options?.in);
  const year = _date.getFullYear();
  const defaultOptions2 = getDefaultOptions$1();
  const firstWeekContainsDate = options?.firstWeekContainsDate ?? options?.locale?.options?.firstWeekContainsDate ?? defaultOptions2.firstWeekContainsDate ?? defaultOptions2.locale?.options?.firstWeekContainsDate ?? 1;
  const firstWeekOfNextYear = constructFrom(options?.in || date, 0);
  firstWeekOfNextYear.setFullYear(year + 1, 0, firstWeekContainsDate);
  firstWeekOfNextYear.setHours(0, 0, 0, 0);
  const startOfNextYear = startOfWeek(firstWeekOfNextYear, options);
  const firstWeekOfThisYear = constructFrom(options?.in || date, 0);
  firstWeekOfThisYear.setFullYear(year, 0, firstWeekContainsDate);
  firstWeekOfThisYear.setHours(0, 0, 0, 0);
  const startOfThisYear = startOfWeek(firstWeekOfThisYear, options);
  if (+_date >= +startOfNextYear) {
    return year + 1;
  } else if (+_date >= +startOfThisYear) {
    return year;
  } else {
    return year - 1;
  }
}
function startOfWeekYear(date, options) {
  const defaultOptions2 = getDefaultOptions$1();
  const firstWeekContainsDate = options?.firstWeekContainsDate ?? options?.locale?.options?.firstWeekContainsDate ?? defaultOptions2.firstWeekContainsDate ?? defaultOptions2.locale?.options?.firstWeekContainsDate ?? 1;
  const year = getWeekYear(date, options);
  const firstWeek = constructFrom(options?.in || date, 0);
  firstWeek.setFullYear(year, 0, firstWeekContainsDate);
  firstWeek.setHours(0, 0, 0, 0);
  const _date = startOfWeek(firstWeek, options);
  return _date;
}
function getWeek(date, options) {
  const _date = toDate(date, options?.in);
  const diff = +startOfWeek(_date, options) - +startOfWeekYear(_date, options);
  return Math.round(diff / millisecondsInWeek) + 1;
}
function addLeadingZeros(number, targetLength) {
  const sign = number < 0 ? "-" : "";
  const output = Math.abs(number).toString().padStart(targetLength, "0");
  return sign + output;
}
const lightFormatters = {
  // Year
  y(date, token) {
    const signedYear = date.getFullYear();
    const year = signedYear > 0 ? signedYear : 1 - signedYear;
    return addLeadingZeros(token === "yy" ? year % 100 : year, token.length);
  },
  // Month
  M(date, token) {
    const month = date.getMonth();
    return token === "M" ? String(month + 1) : addLeadingZeros(month + 1, 2);
  },
  // Day of the month
  d(date, token) {
    return addLeadingZeros(date.getDate(), token.length);
  },
  // AM or PM
  a(date, token) {
    const dayPeriodEnumValue = date.getHours() / 12 >= 1 ? "pm" : "am";
    switch (token) {
      case "a":
      case "aa":
        return dayPeriodEnumValue.toUpperCase();
      case "aaa":
        return dayPeriodEnumValue;
      case "aaaaa":
        return dayPeriodEnumValue[0];
      case "aaaa":
      default:
        return dayPeriodEnumValue === "am" ? "a.m." : "p.m.";
    }
  },
  // Hour [1-12]
  h(date, token) {
    return addLeadingZeros(date.getHours() % 12 || 12, token.length);
  },
  // Hour [0-23]
  H(date, token) {
    return addLeadingZeros(date.getHours(), token.length);
  },
  // Minute
  m(date, token) {
    return addLeadingZeros(date.getMinutes(), token.length);
  },
  // Second
  s(date, token) {
    return addLeadingZeros(date.getSeconds(), token.length);
  },
  // Fraction of second
  S(date, token) {
    const numberOfDigits = token.length;
    const milliseconds = date.getMilliseconds();
    const fractionalSeconds = Math.trunc(
      milliseconds * Math.pow(10, numberOfDigits - 3)
    );
    return addLeadingZeros(fractionalSeconds, token.length);
  }
};
const dayPeriodEnum = {
  midnight: "midnight",
  noon: "noon",
  morning: "morning",
  afternoon: "afternoon",
  evening: "evening",
  night: "night"
};
const formatters = {
  // Era
  G: function(date, token, localize2) {
    const era = date.getFullYear() > 0 ? 1 : 0;
    switch (token) {
      // AD, BC
      case "G":
      case "GG":
      case "GGG":
        return localize2.era(era, { width: "abbreviated" });
      // A, B
      case "GGGGG":
        return localize2.era(era, { width: "narrow" });
      // Anno Domini, Before Christ
      case "GGGG":
      default:
        return localize2.era(era, { width: "wide" });
    }
  },
  // Year
  y: function(date, token, localize2) {
    if (token === "yo") {
      const signedYear = date.getFullYear();
      const year = signedYear > 0 ? signedYear : 1 - signedYear;
      return localize2.ordinalNumber(year, { unit: "year" });
    }
    return lightFormatters.y(date, token);
  },
  // Local week-numbering year
  Y: function(date, token, localize2, options) {
    const signedWeekYear = getWeekYear(date, options);
    const weekYear = signedWeekYear > 0 ? signedWeekYear : 1 - signedWeekYear;
    if (token === "YY") {
      const twoDigitYear = weekYear % 100;
      return addLeadingZeros(twoDigitYear, 2);
    }
    if (token === "Yo") {
      return localize2.ordinalNumber(weekYear, { unit: "year" });
    }
    return addLeadingZeros(weekYear, token.length);
  },
  // ISO week-numbering year
  R: function(date, token) {
    const isoWeekYear = getISOWeekYear(date);
    return addLeadingZeros(isoWeekYear, token.length);
  },
  // Extended year. This is a single number designating the year of this calendar system.
  // The main difference between `y` and `u` localizers are B.C. years:
  // | Year | `y` | `u` |
  // |------|-----|-----|
  // | AC 1 |   1 |   1 |
  // | BC 1 |   1 |   0 |
  // | BC 2 |   2 |  -1 |
  // Also `yy` always returns the last two digits of a year,
  // while `uu` pads single digit years to 2 characters and returns other years unchanged.
  u: function(date, token) {
    const year = date.getFullYear();
    return addLeadingZeros(year, token.length);
  },
  // Quarter
  Q: function(date, token, localize2) {
    const quarter = Math.ceil((date.getMonth() + 1) / 3);
    switch (token) {
      // 1, 2, 3, 4
      case "Q":
        return String(quarter);
      // 01, 02, 03, 04
      case "QQ":
        return addLeadingZeros(quarter, 2);
      // 1st, 2nd, 3rd, 4th
      case "Qo":
        return localize2.ordinalNumber(quarter, { unit: "quarter" });
      // Q1, Q2, Q3, Q4
      case "QQQ":
        return localize2.quarter(quarter, {
          width: "abbreviated",
          context: "formatting"
        });
      // 1, 2, 3, 4 (narrow quarter; could be not numerical)
      case "QQQQQ":
        return localize2.quarter(quarter, {
          width: "narrow",
          context: "formatting"
        });
      // 1st quarter, 2nd quarter, ...
      case "QQQQ":
      default:
        return localize2.quarter(quarter, {
          width: "wide",
          context: "formatting"
        });
    }
  },
  // Stand-alone quarter
  q: function(date, token, localize2) {
    const quarter = Math.ceil((date.getMonth() + 1) / 3);
    switch (token) {
      // 1, 2, 3, 4
      case "q":
        return String(quarter);
      // 01, 02, 03, 04
      case "qq":
        return addLeadingZeros(quarter, 2);
      // 1st, 2nd, 3rd, 4th
      case "qo":
        return localize2.ordinalNumber(quarter, { unit: "quarter" });
      // Q1, Q2, Q3, Q4
      case "qqq":
        return localize2.quarter(quarter, {
          width: "abbreviated",
          context: "standalone"
        });
      // 1, 2, 3, 4 (narrow quarter; could be not numerical)
      case "qqqqq":
        return localize2.quarter(quarter, {
          width: "narrow",
          context: "standalone"
        });
      // 1st quarter, 2nd quarter, ...
      case "qqqq":
      default:
        return localize2.quarter(quarter, {
          width: "wide",
          context: "standalone"
        });
    }
  },
  // Month
  M: function(date, token, localize2) {
    const month = date.getMonth();
    switch (token) {
      case "M":
      case "MM":
        return lightFormatters.M(date, token);
      // 1st, 2nd, ..., 12th
      case "Mo":
        return localize2.ordinalNumber(month + 1, { unit: "month" });
      // Jan, Feb, ..., Dec
      case "MMM":
        return localize2.month(month, {
          width: "abbreviated",
          context: "formatting"
        });
      // J, F, ..., D
      case "MMMMM":
        return localize2.month(month, {
          width: "narrow",
          context: "formatting"
        });
      // January, February, ..., December
      case "MMMM":
      default:
        return localize2.month(month, { width: "wide", context: "formatting" });
    }
  },
  // Stand-alone month
  L: function(date, token, localize2) {
    const month = date.getMonth();
    switch (token) {
      // 1, 2, ..., 12
      case "L":
        return String(month + 1);
      // 01, 02, ..., 12
      case "LL":
        return addLeadingZeros(month + 1, 2);
      // 1st, 2nd, ..., 12th
      case "Lo":
        return localize2.ordinalNumber(month + 1, { unit: "month" });
      // Jan, Feb, ..., Dec
      case "LLL":
        return localize2.month(month, {
          width: "abbreviated",
          context: "standalone"
        });
      // J, F, ..., D
      case "LLLLL":
        return localize2.month(month, {
          width: "narrow",
          context: "standalone"
        });
      // January, February, ..., December
      case "LLLL":
      default:
        return localize2.month(month, { width: "wide", context: "standalone" });
    }
  },
  // Local week of year
  w: function(date, token, localize2, options) {
    const week = getWeek(date, options);
    if (token === "wo") {
      return localize2.ordinalNumber(week, { unit: "week" });
    }
    return addLeadingZeros(week, token.length);
  },
  // ISO week of year
  I: function(date, token, localize2) {
    const isoWeek = getISOWeek(date);
    if (token === "Io") {
      return localize2.ordinalNumber(isoWeek, { unit: "week" });
    }
    return addLeadingZeros(isoWeek, token.length);
  },
  // Day of the month
  d: function(date, token, localize2) {
    if (token === "do") {
      return localize2.ordinalNumber(date.getDate(), { unit: "date" });
    }
    return lightFormatters.d(date, token);
  },
  // Day of year
  D: function(date, token, localize2) {
    const dayOfYear = getDayOfYear(date);
    if (token === "Do") {
      return localize2.ordinalNumber(dayOfYear, { unit: "dayOfYear" });
    }
    return addLeadingZeros(dayOfYear, token.length);
  },
  // Day of week
  E: function(date, token, localize2) {
    const dayOfWeek = date.getDay();
    switch (token) {
      // Tue
      case "E":
      case "EE":
      case "EEE":
        return localize2.day(dayOfWeek, {
          width: "abbreviated",
          context: "formatting"
        });
      // T
      case "EEEEE":
        return localize2.day(dayOfWeek, {
          width: "narrow",
          context: "formatting"
        });
      // Tu
      case "EEEEEE":
        return localize2.day(dayOfWeek, {
          width: "short",
          context: "formatting"
        });
      // Tuesday
      case "EEEE":
      default:
        return localize2.day(dayOfWeek, {
          width: "wide",
          context: "formatting"
        });
    }
  },
  // Local day of week
  e: function(date, token, localize2, options) {
    const dayOfWeek = date.getDay();
    const localDayOfWeek = (dayOfWeek - options.weekStartsOn + 8) % 7 || 7;
    switch (token) {
      // Numerical value (Nth day of week with current locale or weekStartsOn)
      case "e":
        return String(localDayOfWeek);
      // Padded numerical value
      case "ee":
        return addLeadingZeros(localDayOfWeek, 2);
      // 1st, 2nd, ..., 7th
      case "eo":
        return localize2.ordinalNumber(localDayOfWeek, { unit: "day" });
      case "eee":
        return localize2.day(dayOfWeek, {
          width: "abbreviated",
          context: "formatting"
        });
      // T
      case "eeeee":
        return localize2.day(dayOfWeek, {
          width: "narrow",
          context: "formatting"
        });
      // Tu
      case "eeeeee":
        return localize2.day(dayOfWeek, {
          width: "short",
          context: "formatting"
        });
      // Tuesday
      case "eeee":
      default:
        return localize2.day(dayOfWeek, {
          width: "wide",
          context: "formatting"
        });
    }
  },
  // Stand-alone local day of week
  c: function(date, token, localize2, options) {
    const dayOfWeek = date.getDay();
    const localDayOfWeek = (dayOfWeek - options.weekStartsOn + 8) % 7 || 7;
    switch (token) {
      // Numerical value (same as in `e`)
      case "c":
        return String(localDayOfWeek);
      // Padded numerical value
      case "cc":
        return addLeadingZeros(localDayOfWeek, token.length);
      // 1st, 2nd, ..., 7th
      case "co":
        return localize2.ordinalNumber(localDayOfWeek, { unit: "day" });
      case "ccc":
        return localize2.day(dayOfWeek, {
          width: "abbreviated",
          context: "standalone"
        });
      // T
      case "ccccc":
        return localize2.day(dayOfWeek, {
          width: "narrow",
          context: "standalone"
        });
      // Tu
      case "cccccc":
        return localize2.day(dayOfWeek, {
          width: "short",
          context: "standalone"
        });
      // Tuesday
      case "cccc":
      default:
        return localize2.day(dayOfWeek, {
          width: "wide",
          context: "standalone"
        });
    }
  },
  // ISO day of week
  i: function(date, token, localize2) {
    const dayOfWeek = date.getDay();
    const isoDayOfWeek = dayOfWeek === 0 ? 7 : dayOfWeek;
    switch (token) {
      // 2
      case "i":
        return String(isoDayOfWeek);
      // 02
      case "ii":
        return addLeadingZeros(isoDayOfWeek, token.length);
      // 2nd
      case "io":
        return localize2.ordinalNumber(isoDayOfWeek, { unit: "day" });
      // Tue
      case "iii":
        return localize2.day(dayOfWeek, {
          width: "abbreviated",
          context: "formatting"
        });
      // T
      case "iiiii":
        return localize2.day(dayOfWeek, {
          width: "narrow",
          context: "formatting"
        });
      // Tu
      case "iiiiii":
        return localize2.day(dayOfWeek, {
          width: "short",
          context: "formatting"
        });
      // Tuesday
      case "iiii":
      default:
        return localize2.day(dayOfWeek, {
          width: "wide",
          context: "formatting"
        });
    }
  },
  // AM or PM
  a: function(date, token, localize2) {
    const hours = date.getHours();
    const dayPeriodEnumValue = hours / 12 >= 1 ? "pm" : "am";
    switch (token) {
      case "a":
      case "aa":
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "abbreviated",
          context: "formatting"
        });
      case "aaa":
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "abbreviated",
          context: "formatting"
        }).toLowerCase();
      case "aaaaa":
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "narrow",
          context: "formatting"
        });
      case "aaaa":
      default:
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "wide",
          context: "formatting"
        });
    }
  },
  // AM, PM, midnight, noon
  b: function(date, token, localize2) {
    const hours = date.getHours();
    let dayPeriodEnumValue;
    if (hours === 12) {
      dayPeriodEnumValue = dayPeriodEnum.noon;
    } else if (hours === 0) {
      dayPeriodEnumValue = dayPeriodEnum.midnight;
    } else {
      dayPeriodEnumValue = hours / 12 >= 1 ? "pm" : "am";
    }
    switch (token) {
      case "b":
      case "bb":
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "abbreviated",
          context: "formatting"
        });
      case "bbb":
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "abbreviated",
          context: "formatting"
        }).toLowerCase();
      case "bbbbb":
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "narrow",
          context: "formatting"
        });
      case "bbbb":
      default:
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "wide",
          context: "formatting"
        });
    }
  },
  // in the morning, in the afternoon, in the evening, at night
  B: function(date, token, localize2) {
    const hours = date.getHours();
    let dayPeriodEnumValue;
    if (hours >= 17) {
      dayPeriodEnumValue = dayPeriodEnum.evening;
    } else if (hours >= 12) {
      dayPeriodEnumValue = dayPeriodEnum.afternoon;
    } else if (hours >= 4) {
      dayPeriodEnumValue = dayPeriodEnum.morning;
    } else {
      dayPeriodEnumValue = dayPeriodEnum.night;
    }
    switch (token) {
      case "B":
      case "BB":
      case "BBB":
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "abbreviated",
          context: "formatting"
        });
      case "BBBBB":
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "narrow",
          context: "formatting"
        });
      case "BBBB":
      default:
        return localize2.dayPeriod(dayPeriodEnumValue, {
          width: "wide",
          context: "formatting"
        });
    }
  },
  // Hour [1-12]
  h: function(date, token, localize2) {
    if (token === "ho") {
      let hours = date.getHours() % 12;
      if (hours === 0) hours = 12;
      return localize2.ordinalNumber(hours, { unit: "hour" });
    }
    return lightFormatters.h(date, token);
  },
  // Hour [0-23]
  H: function(date, token, localize2) {
    if (token === "Ho") {
      return localize2.ordinalNumber(date.getHours(), { unit: "hour" });
    }
    return lightFormatters.H(date, token);
  },
  // Hour [0-11]
  K: function(date, token, localize2) {
    const hours = date.getHours() % 12;
    if (token === "Ko") {
      return localize2.ordinalNumber(hours, { unit: "hour" });
    }
    return addLeadingZeros(hours, token.length);
  },
  // Hour [1-24]
  k: function(date, token, localize2) {
    let hours = date.getHours();
    if (hours === 0) hours = 24;
    if (token === "ko") {
      return localize2.ordinalNumber(hours, { unit: "hour" });
    }
    return addLeadingZeros(hours, token.length);
  },
  // Minute
  m: function(date, token, localize2) {
    if (token === "mo") {
      return localize2.ordinalNumber(date.getMinutes(), { unit: "minute" });
    }
    return lightFormatters.m(date, token);
  },
  // Second
  s: function(date, token, localize2) {
    if (token === "so") {
      return localize2.ordinalNumber(date.getSeconds(), { unit: "second" });
    }
    return lightFormatters.s(date, token);
  },
  // Fraction of second
  S: function(date, token) {
    return lightFormatters.S(date, token);
  },
  // Timezone (ISO-8601. If offset is 0, output is always `'Z'`)
  X: function(date, token, _localize) {
    const timezoneOffset = date.getTimezoneOffset();
    if (timezoneOffset === 0) {
      return "Z";
    }
    switch (token) {
      // Hours and optional minutes
      case "X":
        return formatTimezoneWithOptionalMinutes(timezoneOffset);
      // Hours, minutes and optional seconds without `:` delimiter
      // Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
      // so this token always has the same output as `XX`
      case "XXXX":
      case "XX":
        return formatTimezone(timezoneOffset);
      // Hours, minutes and optional seconds with `:` delimiter
      // Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
      // so this token always has the same output as `XXX`
      case "XXXXX":
      case "XXX":
      // Hours and minutes with `:` delimiter
      default:
        return formatTimezone(timezoneOffset, ":");
    }
  },
  // Timezone (ISO-8601. If offset is 0, output is `'+00:00'` or equivalent)
  x: function(date, token, _localize) {
    const timezoneOffset = date.getTimezoneOffset();
    switch (token) {
      // Hours and optional minutes
      case "x":
        return formatTimezoneWithOptionalMinutes(timezoneOffset);
      // Hours, minutes and optional seconds without `:` delimiter
      // Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
      // so this token always has the same output as `xx`
      case "xxxx":
      case "xx":
        return formatTimezone(timezoneOffset);
      // Hours, minutes and optional seconds with `:` delimiter
      // Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
      // so this token always has the same output as `xxx`
      case "xxxxx":
      case "xxx":
      // Hours and minutes with `:` delimiter
      default:
        return formatTimezone(timezoneOffset, ":");
    }
  },
  // Timezone (GMT)
  O: function(date, token, _localize) {
    const timezoneOffset = date.getTimezoneOffset();
    switch (token) {
      // Short
      case "O":
      case "OO":
      case "OOO":
        return "GMT" + formatTimezoneShort(timezoneOffset, ":");
      // Long
      case "OOOO":
      default:
        return "GMT" + formatTimezone(timezoneOffset, ":");
    }
  },
  // Timezone (specific non-location)
  z: function(date, token, _localize) {
    const timezoneOffset = date.getTimezoneOffset();
    switch (token) {
      // Short
      case "z":
      case "zz":
      case "zzz":
        return "GMT" + formatTimezoneShort(timezoneOffset, ":");
      // Long
      case "zzzz":
      default:
        return "GMT" + formatTimezone(timezoneOffset, ":");
    }
  },
  // Seconds timestamp
  t: function(date, token, _localize) {
    const timestamp = Math.trunc(+date / 1e3);
    return addLeadingZeros(timestamp, token.length);
  },
  // Milliseconds timestamp
  T: function(date, token, _localize) {
    return addLeadingZeros(+date, token.length);
  }
};
function formatTimezoneShort(offset, delimiter = "") {
  const sign = offset > 0 ? "-" : "+";
  const absOffset = Math.abs(offset);
  const hours = Math.trunc(absOffset / 60);
  const minutes = absOffset % 60;
  if (minutes === 0) {
    return sign + String(hours);
  }
  return sign + String(hours) + delimiter + addLeadingZeros(minutes, 2);
}
function formatTimezoneWithOptionalMinutes(offset, delimiter) {
  if (offset % 60 === 0) {
    const sign = offset > 0 ? "-" : "+";
    return sign + addLeadingZeros(Math.abs(offset) / 60, 2);
  }
  return formatTimezone(offset, delimiter);
}
function formatTimezone(offset, delimiter = "") {
  const sign = offset > 0 ? "-" : "+";
  const absOffset = Math.abs(offset);
  const hours = addLeadingZeros(Math.trunc(absOffset / 60), 2);
  const minutes = addLeadingZeros(absOffset % 60, 2);
  return sign + hours + delimiter + minutes;
}
const dateLongFormatter = (pattern, formatLong2) => {
  switch (pattern) {
    case "P":
      return formatLong2.date({ width: "short" });
    case "PP":
      return formatLong2.date({ width: "medium" });
    case "PPP":
      return formatLong2.date({ width: "long" });
    case "PPPP":
    default:
      return formatLong2.date({ width: "full" });
  }
};
const timeLongFormatter = (pattern, formatLong2) => {
  switch (pattern) {
    case "p":
      return formatLong2.time({ width: "short" });
    case "pp":
      return formatLong2.time({ width: "medium" });
    case "ppp":
      return formatLong2.time({ width: "long" });
    case "pppp":
    default:
      return formatLong2.time({ width: "full" });
  }
};
const dateTimeLongFormatter = (pattern, formatLong2) => {
  const matchResult = pattern.match(/(P+)(p+)?/) || [];
  const datePattern = matchResult[1];
  const timePattern = matchResult[2];
  if (!timePattern) {
    return dateLongFormatter(pattern, formatLong2);
  }
  let dateTimeFormat;
  switch (datePattern) {
    case "P":
      dateTimeFormat = formatLong2.dateTime({ width: "short" });
      break;
    case "PP":
      dateTimeFormat = formatLong2.dateTime({ width: "medium" });
      break;
    case "PPP":
      dateTimeFormat = formatLong2.dateTime({ width: "long" });
      break;
    case "PPPP":
    default:
      dateTimeFormat = formatLong2.dateTime({ width: "full" });
      break;
  }
  return dateTimeFormat.replace("{{date}}", dateLongFormatter(datePattern, formatLong2)).replace("{{time}}", timeLongFormatter(timePattern, formatLong2));
};
const longFormatters = {
  p: timeLongFormatter,
  P: dateTimeLongFormatter
};
const dayOfYearTokenRE = /^D+$/;
const weekYearTokenRE = /^Y+$/;
const throwTokens = ["D", "DD", "YY", "YYYY"];
function isProtectedDayOfYearToken(token) {
  return dayOfYearTokenRE.test(token);
}
function isProtectedWeekYearToken(token) {
  return weekYearTokenRE.test(token);
}
function warnOrThrowProtectedError(token, format2, input) {
  const _message = message(token, format2, input);
  console.warn(_message);
  if (throwTokens.includes(token)) throw new RangeError(_message);
}
function message(token, format2, input) {
  const subject = token[0] === "Y" ? "years" : "days of the month";
  return `Use \`${token.toLowerCase()}\` instead of \`${token}\` (in \`${format2}\`) for formatting ${subject} to the input \`${input}\`; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md`;
}
const formattingTokensRegExp$1 = /[yYQqMLwIdDecihHKkms]o|(\w)\1*|''|'(''|[^'])+('|$)|./g;
const longFormattingTokensRegExp$1 = /P+p+|P+|p+|''|'(''|[^'])+('|$)|./g;
const escapedStringRegExp$1 = /^'([^]*?)'?$/;
const doubleQuoteRegExp$1 = /''/g;
const unescapedLatinCharacterRegExp$1 = /[a-zA-Z]/;
function format(date, formatStr, options) {
  const defaultOptions2 = getDefaultOptions$1();
  const locale = options?.locale ?? defaultOptions2.locale ?? enUS;
  const firstWeekContainsDate = options?.firstWeekContainsDate ?? options?.locale?.options?.firstWeekContainsDate ?? defaultOptions2.firstWeekContainsDate ?? defaultOptions2.locale?.options?.firstWeekContainsDate ?? 1;
  const weekStartsOn = options?.weekStartsOn ?? options?.locale?.options?.weekStartsOn ?? defaultOptions2.weekStartsOn ?? defaultOptions2.locale?.options?.weekStartsOn ?? 0;
  const originalDate = toDate(date, options?.in);
  if (!isValid(originalDate)) {
    throw new RangeError("Invalid time value");
  }
  let parts = formatStr.match(longFormattingTokensRegExp$1).map((substring) => {
    const firstCharacter = substring[0];
    if (firstCharacter === "p" || firstCharacter === "P") {
      const longFormatter = longFormatters[firstCharacter];
      return longFormatter(substring, locale.formatLong);
    }
    return substring;
  }).join("").match(formattingTokensRegExp$1).map((substring) => {
    if (substring === "''") {
      return { isToken: false, value: "'" };
    }
    const firstCharacter = substring[0];
    if (firstCharacter === "'") {
      return { isToken: false, value: cleanEscapedString$1(substring) };
    }
    if (formatters[firstCharacter]) {
      return { isToken: true, value: substring };
    }
    if (firstCharacter.match(unescapedLatinCharacterRegExp$1)) {
      throw new RangeError(
        "Format string contains an unescaped latin alphabet character `" + firstCharacter + "`"
      );
    }
    return { isToken: false, value: substring };
  });
  if (locale.localize.preprocessor) {
    parts = locale.localize.preprocessor(originalDate, parts);
  }
  const formatterOptions = {
    firstWeekContainsDate,
    weekStartsOn,
    locale
  };
  return parts.map((part) => {
    if (!part.isToken) return part.value;
    const token = part.value;
    if (!options?.useAdditionalWeekYearTokens && isProtectedWeekYearToken(token) || !options?.useAdditionalDayOfYearTokens && isProtectedDayOfYearToken(token)) {
      warnOrThrowProtectedError(token, formatStr, String(date));
    }
    const formatter = formatters[token[0]];
    return formatter(originalDate, token, locale.localize, formatterOptions);
  }).join("");
}
function cleanEscapedString$1(input) {
  const matched = input.match(escapedStringRegExp$1);
  if (!matched) {
    return input;
  }
  return matched[1].replace(doubleQuoteRegExp$1, "'");
}
function getDay(date, options) {
  return toDate(date, options?.in).getDay();
}
function getDaysInMonth(date, options) {
  const _date = toDate(date, options?.in);
  const year = _date.getFullYear();
  const monthIndex = _date.getMonth();
  const lastDayOfMonth = constructFrom(_date, 0);
  lastDayOfMonth.setFullYear(year, monthIndex + 1, 0);
  lastDayOfMonth.setHours(0, 0, 0, 0);
  return lastDayOfMonth.getDate();
}
function getDefaultOptions() {
  return Object.assign({}, getDefaultOptions$1());
}
function getHours(date, options) {
  return toDate(date, options?.in).getHours();
}
function getISODay(date, options) {
  const day = toDate(date, options?.in).getDay();
  return day === 0 ? 7 : day;
}
function getMinutes(date, options) {
  return toDate(date, options?.in).getMinutes();
}
function getMonth(date, options) {
  return toDate(date, options?.in).getMonth();
}
function getSeconds(date) {
  return toDate(date).getSeconds();
}
function getYear(date, options) {
  return toDate(date, options?.in).getFullYear();
}
function isAfter(date, dateToCompare) {
  return +toDate(date) > +toDate(dateToCompare);
}
function isBefore(date, dateToCompare) {
  return +toDate(date) < +toDate(dateToCompare);
}
function isEqual(leftDate, rightDate) {
  return +toDate(leftDate) === +toDate(rightDate);
}
function transpose(date, constructor) {
  const date_ = isConstructor(constructor) ? new constructor(0) : constructFrom(constructor, 0);
  date_.setFullYear(date.getFullYear(), date.getMonth(), date.getDate());
  date_.setHours(
    date.getHours(),
    date.getMinutes(),
    date.getSeconds(),
    date.getMilliseconds()
  );
  return date_;
}
function isConstructor(constructor) {
  return typeof constructor === "function" && constructor.prototype?.constructor === constructor;
}
const TIMEZONE_UNIT_PRIORITY = 10;
class Setter {
  subPriority = 0;
  validate(_utcDate, _options) {
    return true;
  }
}
class ValueSetter extends Setter {
  constructor(value, validateValue, setValue, priority, subPriority) {
    super();
    this.value = value;
    this.validateValue = validateValue;
    this.setValue = setValue;
    this.priority = priority;
    if (subPriority) {
      this.subPriority = subPriority;
    }
  }
  validate(date, options) {
    return this.validateValue(date, this.value, options);
  }
  set(date, flags, options) {
    return this.setValue(date, flags, this.value, options);
  }
}
class DateTimezoneSetter extends Setter {
  priority = TIMEZONE_UNIT_PRIORITY;
  subPriority = -1;
  constructor(context, reference) {
    super();
    this.context = context || ((date) => constructFrom(reference, date));
  }
  set(date, flags) {
    if (flags.timestampIsSet) return date;
    return constructFrom(date, transpose(date, this.context));
  }
}
class Parser {
  run(dateString, token, match2, options) {
    const result = this.parse(dateString, token, match2, options);
    if (!result) {
      return null;
    }
    return {
      setter: new ValueSetter(
        result.value,
        this.validate,
        this.set,
        this.priority,
        this.subPriority
      ),
      rest: result.rest
    };
  }
  validate(_utcDate, _value, _options) {
    return true;
  }
}
class EraParser extends Parser {
  priority = 140;
  parse(dateString, token, match2) {
    switch (token) {
      // AD, BC
      case "G":
      case "GG":
      case "GGG":
        return match2.era(dateString, { width: "abbreviated" }) || match2.era(dateString, { width: "narrow" });
      // A, B
      case "GGGGG":
        return match2.era(dateString, { width: "narrow" });
      // Anno Domini, Before Christ
      case "GGGG":
      default:
        return match2.era(dateString, { width: "wide" }) || match2.era(dateString, { width: "abbreviated" }) || match2.era(dateString, { width: "narrow" });
    }
  }
  set(date, flags, value) {
    flags.era = value;
    date.setFullYear(value, 0, 1);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = ["R", "u", "t", "T"];
}
const numericPatterns = {
  month: /^(1[0-2]|0?\d)/,
  // 0 to 12
  date: /^(3[0-1]|[0-2]?\d)/,
  // 0 to 31
  dayOfYear: /^(36[0-6]|3[0-5]\d|[0-2]?\d?\d)/,
  // 0 to 366
  week: /^(5[0-3]|[0-4]?\d)/,
  // 0 to 53
  hour23h: /^(2[0-3]|[0-1]?\d)/,
  // 0 to 23
  hour24h: /^(2[0-4]|[0-1]?\d)/,
  // 0 to 24
  hour11h: /^(1[0-1]|0?\d)/,
  // 0 to 11
  hour12h: /^(1[0-2]|0?\d)/,
  // 0 to 12
  minute: /^[0-5]?\d/,
  // 0 to 59
  second: /^[0-5]?\d/,
  // 0 to 59
  singleDigit: /^\d/,
  // 0 to 9
  twoDigits: /^\d{1,2}/,
  // 0 to 99
  threeDigits: /^\d{1,3}/,
  // 0 to 999
  fourDigits: /^\d{1,4}/,
  // 0 to 9999
  anyDigitsSigned: /^-?\d+/,
  singleDigitSigned: /^-?\d/,
  // 0 to 9, -0 to -9
  twoDigitsSigned: /^-?\d{1,2}/,
  // 0 to 99, -0 to -99
  threeDigitsSigned: /^-?\d{1,3}/,
  // 0 to 999, -0 to -999
  fourDigitsSigned: /^-?\d{1,4}/
  // 0 to 9999, -0 to -9999
};
const timezonePatterns = {
  basicOptionalMinutes: /^([+-])(\d{2})(\d{2})?|Z/,
  basic: /^([+-])(\d{2})(\d{2})|Z/,
  basicOptionalSeconds: /^([+-])(\d{2})(\d{2})((\d{2}))?|Z/,
  extended: /^([+-])(\d{2}):(\d{2})|Z/,
  extendedOptionalSeconds: /^([+-])(\d{2}):(\d{2})(:(\d{2}))?|Z/
};
function mapValue(parseFnResult, mapFn) {
  if (!parseFnResult) {
    return parseFnResult;
  }
  return {
    value: mapFn(parseFnResult.value),
    rest: parseFnResult.rest
  };
}
function parseNumericPattern(pattern, dateString) {
  const matchResult = dateString.match(pattern);
  if (!matchResult) {
    return null;
  }
  return {
    value: parseInt(matchResult[0], 10),
    rest: dateString.slice(matchResult[0].length)
  };
}
function parseTimezonePattern(pattern, dateString) {
  const matchResult = dateString.match(pattern);
  if (!matchResult) {
    return null;
  }
  if (matchResult[0] === "Z") {
    return {
      value: 0,
      rest: dateString.slice(1)
    };
  }
  const sign = matchResult[1] === "+" ? 1 : -1;
  const hours = matchResult[2] ? parseInt(matchResult[2], 10) : 0;
  const minutes = matchResult[3] ? parseInt(matchResult[3], 10) : 0;
  const seconds = matchResult[5] ? parseInt(matchResult[5], 10) : 0;
  return {
    value: sign * (hours * millisecondsInHour + minutes * millisecondsInMinute + seconds * millisecondsInSecond),
    rest: dateString.slice(matchResult[0].length)
  };
}
function parseAnyDigitsSigned(dateString) {
  return parseNumericPattern(numericPatterns.anyDigitsSigned, dateString);
}
function parseNDigits(n, dateString) {
  switch (n) {
    case 1:
      return parseNumericPattern(numericPatterns.singleDigit, dateString);
    case 2:
      return parseNumericPattern(numericPatterns.twoDigits, dateString);
    case 3:
      return parseNumericPattern(numericPatterns.threeDigits, dateString);
    case 4:
      return parseNumericPattern(numericPatterns.fourDigits, dateString);
    default:
      return parseNumericPattern(new RegExp("^\\d{1," + n + "}"), dateString);
  }
}
function parseNDigitsSigned(n, dateString) {
  switch (n) {
    case 1:
      return parseNumericPattern(numericPatterns.singleDigitSigned, dateString);
    case 2:
      return parseNumericPattern(numericPatterns.twoDigitsSigned, dateString);
    case 3:
      return parseNumericPattern(numericPatterns.threeDigitsSigned, dateString);
    case 4:
      return parseNumericPattern(numericPatterns.fourDigitsSigned, dateString);
    default:
      return parseNumericPattern(new RegExp("^-?\\d{1," + n + "}"), dateString);
  }
}
function dayPeriodEnumToHours(dayPeriod) {
  switch (dayPeriod) {
    case "morning":
      return 4;
    case "evening":
      return 17;
    case "pm":
    case "noon":
    case "afternoon":
      return 12;
    case "am":
    case "midnight":
    case "night":
    default:
      return 0;
  }
}
function normalizeTwoDigitYear(twoDigitYear, currentYear) {
  const isCommonEra = currentYear > 0;
  const absCurrentYear = isCommonEra ? currentYear : 1 - currentYear;
  let result;
  if (absCurrentYear <= 50) {
    result = twoDigitYear || 100;
  } else {
    const rangeEnd = absCurrentYear + 50;
    const rangeEndCentury = Math.trunc(rangeEnd / 100) * 100;
    const isPreviousCentury = twoDigitYear >= rangeEnd % 100;
    result = twoDigitYear + rangeEndCentury - (isPreviousCentury ? 100 : 0);
  }
  return isCommonEra ? result : 1 - result;
}
function isLeapYearIndex(year) {
  return year % 400 === 0 || year % 4 === 0 && year % 100 !== 0;
}
class YearParser extends Parser {
  priority = 130;
  incompatibleTokens = ["Y", "R", "u", "w", "I", "i", "e", "c", "t", "T"];
  parse(dateString, token, match2) {
    const valueCallback = (year) => ({
      year,
      isTwoDigitYear: token === "yy"
    });
    switch (token) {
      case "y":
        return mapValue(parseNDigits(4, dateString), valueCallback);
      case "yo":
        return mapValue(
          match2.ordinalNumber(dateString, {
            unit: "year"
          }),
          valueCallback
        );
      default:
        return mapValue(parseNDigits(token.length, dateString), valueCallback);
    }
  }
  validate(_date, value) {
    return value.isTwoDigitYear || value.year > 0;
  }
  set(date, flags, value) {
    const currentYear = date.getFullYear();
    if (value.isTwoDigitYear) {
      const normalizedTwoDigitYear = normalizeTwoDigitYear(
        value.year,
        currentYear
      );
      date.setFullYear(normalizedTwoDigitYear, 0, 1);
      date.setHours(0, 0, 0, 0);
      return date;
    }
    const year = !("era" in flags) || flags.era === 1 ? value.year : 1 - value.year;
    date.setFullYear(year, 0, 1);
    date.setHours(0, 0, 0, 0);
    return date;
  }
}
class LocalWeekYearParser extends Parser {
  priority = 130;
  parse(dateString, token, match2) {
    const valueCallback = (year) => ({
      year,
      isTwoDigitYear: token === "YY"
    });
    switch (token) {
      case "Y":
        return mapValue(parseNDigits(4, dateString), valueCallback);
      case "Yo":
        return mapValue(
          match2.ordinalNumber(dateString, {
            unit: "year"
          }),
          valueCallback
        );
      default:
        return mapValue(parseNDigits(token.length, dateString), valueCallback);
    }
  }
  validate(_date, value) {
    return value.isTwoDigitYear || value.year > 0;
  }
  set(date, flags, value, options) {
    const currentYear = getWeekYear(date, options);
    if (value.isTwoDigitYear) {
      const normalizedTwoDigitYear = normalizeTwoDigitYear(
        value.year,
        currentYear
      );
      date.setFullYear(
        normalizedTwoDigitYear,
        0,
        options.firstWeekContainsDate
      );
      date.setHours(0, 0, 0, 0);
      return startOfWeek(date, options);
    }
    const year = !("era" in flags) || flags.era === 1 ? value.year : 1 - value.year;
    date.setFullYear(year, 0, options.firstWeekContainsDate);
    date.setHours(0, 0, 0, 0);
    return startOfWeek(date, options);
  }
  incompatibleTokens = [
    "y",
    "R",
    "u",
    "Q",
    "q",
    "M",
    "L",
    "I",
    "d",
    "D",
    "i",
    "t",
    "T"
  ];
}
class ISOWeekYearParser extends Parser {
  priority = 130;
  parse(dateString, token) {
    if (token === "R") {
      return parseNDigitsSigned(4, dateString);
    }
    return parseNDigitsSigned(token.length, dateString);
  }
  set(date, _flags, value) {
    const firstWeekOfYear = constructFrom(date, 0);
    firstWeekOfYear.setFullYear(value, 0, 4);
    firstWeekOfYear.setHours(0, 0, 0, 0);
    return startOfISOWeek(firstWeekOfYear);
  }
  incompatibleTokens = [
    "G",
    "y",
    "Y",
    "u",
    "Q",
    "q",
    "M",
    "L",
    "w",
    "d",
    "D",
    "e",
    "c",
    "t",
    "T"
  ];
}
class ExtendedYearParser extends Parser {
  priority = 130;
  parse(dateString, token) {
    if (token === "u") {
      return parseNDigitsSigned(4, dateString);
    }
    return parseNDigitsSigned(token.length, dateString);
  }
  set(date, _flags, value) {
    date.setFullYear(value, 0, 1);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = ["G", "y", "Y", "R", "w", "I", "i", "e", "c", "t", "T"];
}
class QuarterParser extends Parser {
  priority = 120;
  parse(dateString, token, match2) {
    switch (token) {
      // 1, 2, 3, 4
      case "Q":
      case "QQ":
        return parseNDigits(token.length, dateString);
      // 1st, 2nd, 3rd, 4th
      case "Qo":
        return match2.ordinalNumber(dateString, { unit: "quarter" });
      // Q1, Q2, Q3, Q4
      case "QQQ":
        return match2.quarter(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.quarter(dateString, {
          width: "narrow",
          context: "formatting"
        });
      // 1, 2, 3, 4 (narrow quarter; could be not numerical)
      case "QQQQQ":
        return match2.quarter(dateString, {
          width: "narrow",
          context: "formatting"
        });
      // 1st quarter, 2nd quarter, ...
      case "QQQQ":
      default:
        return match2.quarter(dateString, {
          width: "wide",
          context: "formatting"
        }) || match2.quarter(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.quarter(dateString, {
          width: "narrow",
          context: "formatting"
        });
    }
  }
  validate(_date, value) {
    return value >= 1 && value <= 4;
  }
  set(date, _flags, value) {
    date.setMonth((value - 1) * 3, 1);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = [
    "Y",
    "R",
    "q",
    "M",
    "L",
    "w",
    "I",
    "d",
    "D",
    "i",
    "e",
    "c",
    "t",
    "T"
  ];
}
class StandAloneQuarterParser extends Parser {
  priority = 120;
  parse(dateString, token, match2) {
    switch (token) {
      // 1, 2, 3, 4
      case "q":
      case "qq":
        return parseNDigits(token.length, dateString);
      // 1st, 2nd, 3rd, 4th
      case "qo":
        return match2.ordinalNumber(dateString, { unit: "quarter" });
      // Q1, Q2, Q3, Q4
      case "qqq":
        return match2.quarter(dateString, {
          width: "abbreviated",
          context: "standalone"
        }) || match2.quarter(dateString, {
          width: "narrow",
          context: "standalone"
        });
      // 1, 2, 3, 4 (narrow quarter; could be not numerical)
      case "qqqqq":
        return match2.quarter(dateString, {
          width: "narrow",
          context: "standalone"
        });
      // 1st quarter, 2nd quarter, ...
      case "qqqq":
      default:
        return match2.quarter(dateString, {
          width: "wide",
          context: "standalone"
        }) || match2.quarter(dateString, {
          width: "abbreviated",
          context: "standalone"
        }) || match2.quarter(dateString, {
          width: "narrow",
          context: "standalone"
        });
    }
  }
  validate(_date, value) {
    return value >= 1 && value <= 4;
  }
  set(date, _flags, value) {
    date.setMonth((value - 1) * 3, 1);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = [
    "Y",
    "R",
    "Q",
    "M",
    "L",
    "w",
    "I",
    "d",
    "D",
    "i",
    "e",
    "c",
    "t",
    "T"
  ];
}
class MonthParser extends Parser {
  incompatibleTokens = [
    "Y",
    "R",
    "q",
    "Q",
    "L",
    "w",
    "I",
    "D",
    "i",
    "e",
    "c",
    "t",
    "T"
  ];
  priority = 110;
  parse(dateString, token, match2) {
    const valueCallback = (value) => value - 1;
    switch (token) {
      // 1, 2, ..., 12
      case "M":
        return mapValue(
          parseNumericPattern(numericPatterns.month, dateString),
          valueCallback
        );
      // 01, 02, ..., 12
      case "MM":
        return mapValue(parseNDigits(2, dateString), valueCallback);
      // 1st, 2nd, ..., 12th
      case "Mo":
        return mapValue(
          match2.ordinalNumber(dateString, {
            unit: "month"
          }),
          valueCallback
        );
      // Jan, Feb, ..., Dec
      case "MMM":
        return match2.month(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.month(dateString, { width: "narrow", context: "formatting" });
      // J, F, ..., D
      case "MMMMM":
        return match2.month(dateString, {
          width: "narrow",
          context: "formatting"
        });
      // January, February, ..., December
      case "MMMM":
      default:
        return match2.month(dateString, { width: "wide", context: "formatting" }) || match2.month(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.month(dateString, { width: "narrow", context: "formatting" });
    }
  }
  validate(_date, value) {
    return value >= 0 && value <= 11;
  }
  set(date, _flags, value) {
    date.setMonth(value, 1);
    date.setHours(0, 0, 0, 0);
    return date;
  }
}
class StandAloneMonthParser extends Parser {
  priority = 110;
  parse(dateString, token, match2) {
    const valueCallback = (value) => value - 1;
    switch (token) {
      // 1, 2, ..., 12
      case "L":
        return mapValue(
          parseNumericPattern(numericPatterns.month, dateString),
          valueCallback
        );
      // 01, 02, ..., 12
      case "LL":
        return mapValue(parseNDigits(2, dateString), valueCallback);
      // 1st, 2nd, ..., 12th
      case "Lo":
        return mapValue(
          match2.ordinalNumber(dateString, {
            unit: "month"
          }),
          valueCallback
        );
      // Jan, Feb, ..., Dec
      case "LLL":
        return match2.month(dateString, {
          width: "abbreviated",
          context: "standalone"
        }) || match2.month(dateString, { width: "narrow", context: "standalone" });
      // J, F, ..., D
      case "LLLLL":
        return match2.month(dateString, {
          width: "narrow",
          context: "standalone"
        });
      // January, February, ..., December
      case "LLLL":
      default:
        return match2.month(dateString, { width: "wide", context: "standalone" }) || match2.month(dateString, {
          width: "abbreviated",
          context: "standalone"
        }) || match2.month(dateString, { width: "narrow", context: "standalone" });
    }
  }
  validate(_date, value) {
    return value >= 0 && value <= 11;
  }
  set(date, _flags, value) {
    date.setMonth(value, 1);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = [
    "Y",
    "R",
    "q",
    "Q",
    "M",
    "w",
    "I",
    "D",
    "i",
    "e",
    "c",
    "t",
    "T"
  ];
}
function setWeek(date, week, options) {
  const date_ = toDate(date, options?.in);
  const diff = getWeek(date_, options) - week;
  date_.setDate(date_.getDate() - diff * 7);
  return toDate(date_, options?.in);
}
class LocalWeekParser extends Parser {
  priority = 100;
  parse(dateString, token, match2) {
    switch (token) {
      case "w":
        return parseNumericPattern(numericPatterns.week, dateString);
      case "wo":
        return match2.ordinalNumber(dateString, { unit: "week" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(_date, value) {
    return value >= 1 && value <= 53;
  }
  set(date, _flags, value, options) {
    return startOfWeek(setWeek(date, value, options), options);
  }
  incompatibleTokens = [
    "y",
    "R",
    "u",
    "q",
    "Q",
    "M",
    "L",
    "I",
    "d",
    "D",
    "i",
    "t",
    "T"
  ];
}
function setISOWeek(date, week, options) {
  const _date = toDate(date, options?.in);
  const diff = getISOWeek(_date, options) - week;
  _date.setDate(_date.getDate() - diff * 7);
  return _date;
}
class ISOWeekParser extends Parser {
  priority = 100;
  parse(dateString, token, match2) {
    switch (token) {
      case "I":
        return parseNumericPattern(numericPatterns.week, dateString);
      case "Io":
        return match2.ordinalNumber(dateString, { unit: "week" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(_date, value) {
    return value >= 1 && value <= 53;
  }
  set(date, _flags, value) {
    return startOfISOWeek(setISOWeek(date, value));
  }
  incompatibleTokens = [
    "y",
    "Y",
    "u",
    "q",
    "Q",
    "M",
    "L",
    "w",
    "d",
    "D",
    "e",
    "c",
    "t",
    "T"
  ];
}
const DAYS_IN_MONTH = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
const DAYS_IN_MONTH_LEAP_YEAR = [
  31,
  29,
  31,
  30,
  31,
  30,
  31,
  31,
  30,
  31,
  30,
  31
];
class DateParser extends Parser {
  priority = 90;
  subPriority = 1;
  parse(dateString, token, match2) {
    switch (token) {
      case "d":
        return parseNumericPattern(numericPatterns.date, dateString);
      case "do":
        return match2.ordinalNumber(dateString, { unit: "date" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(date, value) {
    const year = date.getFullYear();
    const isLeapYear = isLeapYearIndex(year);
    const month = date.getMonth();
    if (isLeapYear) {
      return value >= 1 && value <= DAYS_IN_MONTH_LEAP_YEAR[month];
    } else {
      return value >= 1 && value <= DAYS_IN_MONTH[month];
    }
  }
  set(date, _flags, value) {
    date.setDate(value);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = [
    "Y",
    "R",
    "q",
    "Q",
    "w",
    "I",
    "D",
    "i",
    "e",
    "c",
    "t",
    "T"
  ];
}
class DayOfYearParser extends Parser {
  priority = 90;
  subpriority = 1;
  parse(dateString, token, match2) {
    switch (token) {
      case "D":
      case "DD":
        return parseNumericPattern(numericPatterns.dayOfYear, dateString);
      case "Do":
        return match2.ordinalNumber(dateString, { unit: "date" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(date, value) {
    const year = date.getFullYear();
    const isLeapYear = isLeapYearIndex(year);
    if (isLeapYear) {
      return value >= 1 && value <= 366;
    } else {
      return value >= 1 && value <= 365;
    }
  }
  set(date, _flags, value) {
    date.setMonth(0, value);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = [
    "Y",
    "R",
    "q",
    "Q",
    "M",
    "L",
    "w",
    "I",
    "d",
    "E",
    "i",
    "e",
    "c",
    "t",
    "T"
  ];
}
function setDay(date, day, options) {
  const defaultOptions2 = getDefaultOptions$1();
  const weekStartsOn = options?.weekStartsOn ?? options?.locale?.options?.weekStartsOn ?? defaultOptions2.weekStartsOn ?? defaultOptions2.locale?.options?.weekStartsOn ?? 0;
  const date_ = toDate(date, options?.in);
  const currentDay = date_.getDay();
  const remainder = day % 7;
  const dayIndex = (remainder + 7) % 7;
  const delta = 7 - weekStartsOn;
  const diff = day < 0 || day > 6 ? day - (currentDay + delta) % 7 : (dayIndex + delta) % 7 - (currentDay + delta) % 7;
  return addDays(date_, diff, options);
}
class DayParser extends Parser {
  priority = 90;
  parse(dateString, token, match2) {
    switch (token) {
      // Tue
      case "E":
      case "EE":
      case "EEE":
        return match2.day(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.day(dateString, { width: "short", context: "formatting" }) || match2.day(dateString, { width: "narrow", context: "formatting" });
      // T
      case "EEEEE":
        return match2.day(dateString, {
          width: "narrow",
          context: "formatting"
        });
      // Tu
      case "EEEEEE":
        return match2.day(dateString, { width: "short", context: "formatting" }) || match2.day(dateString, { width: "narrow", context: "formatting" });
      // Tuesday
      case "EEEE":
      default:
        return match2.day(dateString, { width: "wide", context: "formatting" }) || match2.day(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.day(dateString, { width: "short", context: "formatting" }) || match2.day(dateString, { width: "narrow", context: "formatting" });
    }
  }
  validate(_date, value) {
    return value >= 0 && value <= 6;
  }
  set(date, _flags, value, options) {
    date = setDay(date, value, options);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = ["D", "i", "e", "c", "t", "T"];
}
class LocalDayParser extends Parser {
  priority = 90;
  parse(dateString, token, match2, options) {
    const valueCallback = (value) => {
      const wholeWeekDays = Math.floor((value - 1) / 7) * 7;
      return (value + options.weekStartsOn + 6) % 7 + wholeWeekDays;
    };
    switch (token) {
      // 3
      case "e":
      case "ee":
        return mapValue(parseNDigits(token.length, dateString), valueCallback);
      // 3rd
      case "eo":
        return mapValue(
          match2.ordinalNumber(dateString, {
            unit: "day"
          }),
          valueCallback
        );
      // Tue
      case "eee":
        return match2.day(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.day(dateString, { width: "short", context: "formatting" }) || match2.day(dateString, { width: "narrow", context: "formatting" });
      // T
      case "eeeee":
        return match2.day(dateString, {
          width: "narrow",
          context: "formatting"
        });
      // Tu
      case "eeeeee":
        return match2.day(dateString, { width: "short", context: "formatting" }) || match2.day(dateString, { width: "narrow", context: "formatting" });
      // Tuesday
      case "eeee":
      default:
        return match2.day(dateString, { width: "wide", context: "formatting" }) || match2.day(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.day(dateString, { width: "short", context: "formatting" }) || match2.day(dateString, { width: "narrow", context: "formatting" });
    }
  }
  validate(_date, value) {
    return value >= 0 && value <= 6;
  }
  set(date, _flags, value, options) {
    date = setDay(date, value, options);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = [
    "y",
    "R",
    "u",
    "q",
    "Q",
    "M",
    "L",
    "I",
    "d",
    "D",
    "E",
    "i",
    "c",
    "t",
    "T"
  ];
}
class StandAloneLocalDayParser extends Parser {
  priority = 90;
  parse(dateString, token, match2, options) {
    const valueCallback = (value) => {
      const wholeWeekDays = Math.floor((value - 1) / 7) * 7;
      return (value + options.weekStartsOn + 6) % 7 + wholeWeekDays;
    };
    switch (token) {
      // 3
      case "c":
      case "cc":
        return mapValue(parseNDigits(token.length, dateString), valueCallback);
      // 3rd
      case "co":
        return mapValue(
          match2.ordinalNumber(dateString, {
            unit: "day"
          }),
          valueCallback
        );
      // Tue
      case "ccc":
        return match2.day(dateString, {
          width: "abbreviated",
          context: "standalone"
        }) || match2.day(dateString, { width: "short", context: "standalone" }) || match2.day(dateString, { width: "narrow", context: "standalone" });
      // T
      case "ccccc":
        return match2.day(dateString, {
          width: "narrow",
          context: "standalone"
        });
      // Tu
      case "cccccc":
        return match2.day(dateString, { width: "short", context: "standalone" }) || match2.day(dateString, { width: "narrow", context: "standalone" });
      // Tuesday
      case "cccc":
      default:
        return match2.day(dateString, { width: "wide", context: "standalone" }) || match2.day(dateString, {
          width: "abbreviated",
          context: "standalone"
        }) || match2.day(dateString, { width: "short", context: "standalone" }) || match2.day(dateString, { width: "narrow", context: "standalone" });
    }
  }
  validate(_date, value) {
    return value >= 0 && value <= 6;
  }
  set(date, _flags, value, options) {
    date = setDay(date, value, options);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = [
    "y",
    "R",
    "u",
    "q",
    "Q",
    "M",
    "L",
    "I",
    "d",
    "D",
    "E",
    "i",
    "e",
    "t",
    "T"
  ];
}
function setISODay(date, day, options) {
  const date_ = toDate(date, options?.in);
  const currentDay = getISODay(date_, options);
  const diff = day - currentDay;
  return addDays(date_, diff, options);
}
class ISODayParser extends Parser {
  priority = 90;
  parse(dateString, token, match2) {
    const valueCallback = (value) => {
      if (value === 0) {
        return 7;
      }
      return value;
    };
    switch (token) {
      // 2
      case "i":
      case "ii":
        return parseNDigits(token.length, dateString);
      // 2nd
      case "io":
        return match2.ordinalNumber(dateString, { unit: "day" });
      // Tue
      case "iii":
        return mapValue(
          match2.day(dateString, {
            width: "abbreviated",
            context: "formatting"
          }) || match2.day(dateString, {
            width: "short",
            context: "formatting"
          }) || match2.day(dateString, {
            width: "narrow",
            context: "formatting"
          }),
          valueCallback
        );
      // T
      case "iiiii":
        return mapValue(
          match2.day(dateString, {
            width: "narrow",
            context: "formatting"
          }),
          valueCallback
        );
      // Tu
      case "iiiiii":
        return mapValue(
          match2.day(dateString, {
            width: "short",
            context: "formatting"
          }) || match2.day(dateString, {
            width: "narrow",
            context: "formatting"
          }),
          valueCallback
        );
      // Tuesday
      case "iiii":
      default:
        return mapValue(
          match2.day(dateString, {
            width: "wide",
            context: "formatting"
          }) || match2.day(dateString, {
            width: "abbreviated",
            context: "formatting"
          }) || match2.day(dateString, {
            width: "short",
            context: "formatting"
          }) || match2.day(dateString, {
            width: "narrow",
            context: "formatting"
          }),
          valueCallback
        );
    }
  }
  validate(_date, value) {
    return value >= 1 && value <= 7;
  }
  set(date, _flags, value) {
    date = setISODay(date, value);
    date.setHours(0, 0, 0, 0);
    return date;
  }
  incompatibleTokens = [
    "y",
    "Y",
    "u",
    "q",
    "Q",
    "M",
    "L",
    "w",
    "d",
    "D",
    "E",
    "e",
    "c",
    "t",
    "T"
  ];
}
class AMPMParser extends Parser {
  priority = 80;
  parse(dateString, token, match2) {
    switch (token) {
      case "a":
      case "aa":
      case "aaa":
        return match2.dayPeriod(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.dayPeriod(dateString, {
          width: "narrow",
          context: "formatting"
        });
      case "aaaaa":
        return match2.dayPeriod(dateString, {
          width: "narrow",
          context: "formatting"
        });
      case "aaaa":
      default:
        return match2.dayPeriod(dateString, {
          width: "wide",
          context: "formatting"
        }) || match2.dayPeriod(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.dayPeriod(dateString, {
          width: "narrow",
          context: "formatting"
        });
    }
  }
  set(date, _flags, value) {
    date.setHours(dayPeriodEnumToHours(value), 0, 0, 0);
    return date;
  }
  incompatibleTokens = ["b", "B", "H", "k", "t", "T"];
}
class AMPMMidnightParser extends Parser {
  priority = 80;
  parse(dateString, token, match2) {
    switch (token) {
      case "b":
      case "bb":
      case "bbb":
        return match2.dayPeriod(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.dayPeriod(dateString, {
          width: "narrow",
          context: "formatting"
        });
      case "bbbbb":
        return match2.dayPeriod(dateString, {
          width: "narrow",
          context: "formatting"
        });
      case "bbbb":
      default:
        return match2.dayPeriod(dateString, {
          width: "wide",
          context: "formatting"
        }) || match2.dayPeriod(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.dayPeriod(dateString, {
          width: "narrow",
          context: "formatting"
        });
    }
  }
  set(date, _flags, value) {
    date.setHours(dayPeriodEnumToHours(value), 0, 0, 0);
    return date;
  }
  incompatibleTokens = ["a", "B", "H", "k", "t", "T"];
}
class DayPeriodParser extends Parser {
  priority = 80;
  parse(dateString, token, match2) {
    switch (token) {
      case "B":
      case "BB":
      case "BBB":
        return match2.dayPeriod(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.dayPeriod(dateString, {
          width: "narrow",
          context: "formatting"
        });
      case "BBBBB":
        return match2.dayPeriod(dateString, {
          width: "narrow",
          context: "formatting"
        });
      case "BBBB":
      default:
        return match2.dayPeriod(dateString, {
          width: "wide",
          context: "formatting"
        }) || match2.dayPeriod(dateString, {
          width: "abbreviated",
          context: "formatting"
        }) || match2.dayPeriod(dateString, {
          width: "narrow",
          context: "formatting"
        });
    }
  }
  set(date, _flags, value) {
    date.setHours(dayPeriodEnumToHours(value), 0, 0, 0);
    return date;
  }
  incompatibleTokens = ["a", "b", "t", "T"];
}
class Hour1to12Parser extends Parser {
  priority = 70;
  parse(dateString, token, match2) {
    switch (token) {
      case "h":
        return parseNumericPattern(numericPatterns.hour12h, dateString);
      case "ho":
        return match2.ordinalNumber(dateString, { unit: "hour" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(_date, value) {
    return value >= 1 && value <= 12;
  }
  set(date, _flags, value) {
    const isPM = date.getHours() >= 12;
    if (isPM && value < 12) {
      date.setHours(value + 12, 0, 0, 0);
    } else if (!isPM && value === 12) {
      date.setHours(0, 0, 0, 0);
    } else {
      date.setHours(value, 0, 0, 0);
    }
    return date;
  }
  incompatibleTokens = ["H", "K", "k", "t", "T"];
}
class Hour0to23Parser extends Parser {
  priority = 70;
  parse(dateString, token, match2) {
    switch (token) {
      case "H":
        return parseNumericPattern(numericPatterns.hour23h, dateString);
      case "Ho":
        return match2.ordinalNumber(dateString, { unit: "hour" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(_date, value) {
    return value >= 0 && value <= 23;
  }
  set(date, _flags, value) {
    date.setHours(value, 0, 0, 0);
    return date;
  }
  incompatibleTokens = ["a", "b", "h", "K", "k", "t", "T"];
}
class Hour0To11Parser extends Parser {
  priority = 70;
  parse(dateString, token, match2) {
    switch (token) {
      case "K":
        return parseNumericPattern(numericPatterns.hour11h, dateString);
      case "Ko":
        return match2.ordinalNumber(dateString, { unit: "hour" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(_date, value) {
    return value >= 0 && value <= 11;
  }
  set(date, _flags, value) {
    const isPM = date.getHours() >= 12;
    if (isPM && value < 12) {
      date.setHours(value + 12, 0, 0, 0);
    } else {
      date.setHours(value, 0, 0, 0);
    }
    return date;
  }
  incompatibleTokens = ["h", "H", "k", "t", "T"];
}
class Hour1To24Parser extends Parser {
  priority = 70;
  parse(dateString, token, match2) {
    switch (token) {
      case "k":
        return parseNumericPattern(numericPatterns.hour24h, dateString);
      case "ko":
        return match2.ordinalNumber(dateString, { unit: "hour" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(_date, value) {
    return value >= 1 && value <= 24;
  }
  set(date, _flags, value) {
    const hours = value <= 24 ? value % 24 : value;
    date.setHours(hours, 0, 0, 0);
    return date;
  }
  incompatibleTokens = ["a", "b", "h", "H", "K", "t", "T"];
}
class MinuteParser extends Parser {
  priority = 60;
  parse(dateString, token, match2) {
    switch (token) {
      case "m":
        return parseNumericPattern(numericPatterns.minute, dateString);
      case "mo":
        return match2.ordinalNumber(dateString, { unit: "minute" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(_date, value) {
    return value >= 0 && value <= 59;
  }
  set(date, _flags, value) {
    date.setMinutes(value, 0, 0);
    return date;
  }
  incompatibleTokens = ["t", "T"];
}
class SecondParser extends Parser {
  priority = 50;
  parse(dateString, token, match2) {
    switch (token) {
      case "s":
        return parseNumericPattern(numericPatterns.second, dateString);
      case "so":
        return match2.ordinalNumber(dateString, { unit: "second" });
      default:
        return parseNDigits(token.length, dateString);
    }
  }
  validate(_date, value) {
    return value >= 0 && value <= 59;
  }
  set(date, _flags, value) {
    date.setSeconds(value, 0);
    return date;
  }
  incompatibleTokens = ["t", "T"];
}
class FractionOfSecondParser extends Parser {
  priority = 30;
  parse(dateString, token) {
    const valueCallback = (value) => Math.trunc(value * Math.pow(10, -token.length + 3));
    return mapValue(parseNDigits(token.length, dateString), valueCallback);
  }
  set(date, _flags, value) {
    date.setMilliseconds(value);
    return date;
  }
  incompatibleTokens = ["t", "T"];
}
class ISOTimezoneWithZParser extends Parser {
  priority = 10;
  parse(dateString, token) {
    switch (token) {
      case "X":
        return parseTimezonePattern(
          timezonePatterns.basicOptionalMinutes,
          dateString
        );
      case "XX":
        return parseTimezonePattern(timezonePatterns.basic, dateString);
      case "XXXX":
        return parseTimezonePattern(
          timezonePatterns.basicOptionalSeconds,
          dateString
        );
      case "XXXXX":
        return parseTimezonePattern(
          timezonePatterns.extendedOptionalSeconds,
          dateString
        );
      case "XXX":
      default:
        return parseTimezonePattern(timezonePatterns.extended, dateString);
    }
  }
  set(date, flags, value) {
    if (flags.timestampIsSet) return date;
    return constructFrom(
      date,
      date.getTime() - getTimezoneOffsetInMilliseconds(date) - value
    );
  }
  incompatibleTokens = ["t", "T", "x"];
}
class ISOTimezoneParser extends Parser {
  priority = 10;
  parse(dateString, token) {
    switch (token) {
      case "x":
        return parseTimezonePattern(
          timezonePatterns.basicOptionalMinutes,
          dateString
        );
      case "xx":
        return parseTimezonePattern(timezonePatterns.basic, dateString);
      case "xxxx":
        return parseTimezonePattern(
          timezonePatterns.basicOptionalSeconds,
          dateString
        );
      case "xxxxx":
        return parseTimezonePattern(
          timezonePatterns.extendedOptionalSeconds,
          dateString
        );
      case "xxx":
      default:
        return parseTimezonePattern(timezonePatterns.extended, dateString);
    }
  }
  set(date, flags, value) {
    if (flags.timestampIsSet) return date;
    return constructFrom(
      date,
      date.getTime() - getTimezoneOffsetInMilliseconds(date) - value
    );
  }
  incompatibleTokens = ["t", "T", "X"];
}
class TimestampSecondsParser extends Parser {
  priority = 40;
  parse(dateString) {
    return parseAnyDigitsSigned(dateString);
  }
  set(date, _flags, value) {
    return [constructFrom(date, value * 1e3), { timestampIsSet: true }];
  }
  incompatibleTokens = "*";
}
class TimestampMillisecondsParser extends Parser {
  priority = 20;
  parse(dateString) {
    return parseAnyDigitsSigned(dateString);
  }
  set(date, _flags, value) {
    return [constructFrom(date, value), { timestampIsSet: true }];
  }
  incompatibleTokens = "*";
}
const parsers = {
  G: new EraParser(),
  y: new YearParser(),
  Y: new LocalWeekYearParser(),
  R: new ISOWeekYearParser(),
  u: new ExtendedYearParser(),
  Q: new QuarterParser(),
  q: new StandAloneQuarterParser(),
  M: new MonthParser(),
  L: new StandAloneMonthParser(),
  w: new LocalWeekParser(),
  I: new ISOWeekParser(),
  d: new DateParser(),
  D: new DayOfYearParser(),
  E: new DayParser(),
  e: new LocalDayParser(),
  c: new StandAloneLocalDayParser(),
  i: new ISODayParser(),
  a: new AMPMParser(),
  b: new AMPMMidnightParser(),
  B: new DayPeriodParser(),
  h: new Hour1to12Parser(),
  H: new Hour0to23Parser(),
  K: new Hour0To11Parser(),
  k: new Hour1To24Parser(),
  m: new MinuteParser(),
  s: new SecondParser(),
  S: new FractionOfSecondParser(),
  X: new ISOTimezoneWithZParser(),
  x: new ISOTimezoneParser(),
  t: new TimestampSecondsParser(),
  T: new TimestampMillisecondsParser()
};
const formattingTokensRegExp = /[yYQqMLwIdDecihHKkms]o|(\w)\1*|''|'(''|[^'])+('|$)|./g;
const longFormattingTokensRegExp = /P+p+|P+|p+|''|'(''|[^'])+('|$)|./g;
const escapedStringRegExp = /^'([^]*?)'?$/;
const doubleQuoteRegExp = /''/g;
const notWhitespaceRegExp = /\S/;
const unescapedLatinCharacterRegExp = /[a-zA-Z]/;
function parse(dateStr, formatStr, referenceDate, options) {
  const invalidDate = () => constructFrom(options?.in || referenceDate, NaN);
  const defaultOptions2 = getDefaultOptions();
  const locale = options?.locale ?? defaultOptions2.locale ?? enUS;
  const firstWeekContainsDate = options?.firstWeekContainsDate ?? options?.locale?.options?.firstWeekContainsDate ?? defaultOptions2.firstWeekContainsDate ?? defaultOptions2.locale?.options?.firstWeekContainsDate ?? 1;
  const weekStartsOn = options?.weekStartsOn ?? options?.locale?.options?.weekStartsOn ?? defaultOptions2.weekStartsOn ?? defaultOptions2.locale?.options?.weekStartsOn ?? 0;
  if (!formatStr)
    return dateStr ? invalidDate() : toDate(referenceDate, options?.in);
  const subFnOptions = {
    firstWeekContainsDate,
    weekStartsOn,
    locale
  };
  const setters = [new DateTimezoneSetter(options?.in, referenceDate)];
  const tokens = formatStr.match(longFormattingTokensRegExp).map((substring) => {
    const firstCharacter = substring[0];
    if (firstCharacter in longFormatters) {
      const longFormatter = longFormatters[firstCharacter];
      return longFormatter(substring, locale.formatLong);
    }
    return substring;
  }).join("").match(formattingTokensRegExp);
  const usedTokens = [];
  for (let token of tokens) {
    if (!options?.useAdditionalWeekYearTokens && isProtectedWeekYearToken(token)) {
      warnOrThrowProtectedError(token, formatStr, dateStr);
    }
    if (!options?.useAdditionalDayOfYearTokens && isProtectedDayOfYearToken(token)) {
      warnOrThrowProtectedError(token, formatStr, dateStr);
    }
    const firstCharacter = token[0];
    const parser = parsers[firstCharacter];
    if (parser) {
      const { incompatibleTokens } = parser;
      if (Array.isArray(incompatibleTokens)) {
        const incompatibleToken = usedTokens.find(
          (usedToken) => incompatibleTokens.includes(usedToken.token) || usedToken.token === firstCharacter
        );
        if (incompatibleToken) {
          throw new RangeError(
            `The format string mustn't contain \`${incompatibleToken.fullToken}\` and \`${token}\` at the same time`
          );
        }
      } else if (parser.incompatibleTokens === "*" && usedTokens.length > 0) {
        throw new RangeError(
          `The format string mustn't contain \`${token}\` and any other token at the same time`
        );
      }
      usedTokens.push({ token: firstCharacter, fullToken: token });
      const parseResult = parser.run(
        dateStr,
        token,
        locale.match,
        subFnOptions
      );
      if (!parseResult) {
        return invalidDate();
      }
      setters.push(parseResult.setter);
      dateStr = parseResult.rest;
    } else {
      if (firstCharacter.match(unescapedLatinCharacterRegExp)) {
        throw new RangeError(
          "Format string contains an unescaped latin alphabet character `" + firstCharacter + "`"
        );
      }
      if (token === "''") {
        token = "'";
      } else if (firstCharacter === "'") {
        token = cleanEscapedString(token);
      }
      if (dateStr.indexOf(token) === 0) {
        dateStr = dateStr.slice(token.length);
      } else {
        return invalidDate();
      }
    }
  }
  if (dateStr.length > 0 && notWhitespaceRegExp.test(dateStr)) {
    return invalidDate();
  }
  const uniquePrioritySetters = setters.map((setter) => setter.priority).sort((a, b) => b - a).filter((priority, index, array) => array.indexOf(priority) === index).map(
    (priority) => setters.filter((setter) => setter.priority === priority).sort((a, b) => b.subPriority - a.subPriority)
  ).map((setterArray) => setterArray[0]);
  let date = toDate(referenceDate, options?.in);
  if (isNaN(+date)) return invalidDate();
  const flags = {};
  for (const setter of uniquePrioritySetters) {
    if (!setter.validate(date, subFnOptions)) {
      return invalidDate();
    }
    const result = setter.set(date, flags, subFnOptions);
    if (Array.isArray(result)) {
      date = result[0];
      Object.assign(flags, result[1]);
    } else {
      date = result;
    }
  }
  return date;
}
function cleanEscapedString(input) {
  return input.match(escapedStringRegExp)[1].replace(doubleQuoteRegExp, "'");
}
function isSameQuarter(laterDate, earlierDate, options) {
  const [dateLeft_, dateRight_] = normalizeDates(
    options?.in,
    laterDate,
    earlierDate
  );
  return +startOfQuarter(dateLeft_) === +startOfQuarter(dateRight_);
}
function subDays(date, amount, options) {
  return addDays(date, -amount, options);
}
function setMonth(date, month, options) {
  const _date = toDate(date, options?.in);
  const year = _date.getFullYear();
  const day = _date.getDate();
  const midMonth = constructFrom(date, 0);
  midMonth.setFullYear(year, month, 15);
  midMonth.setHours(0, 0, 0, 0);
  const daysInMonth = getDaysInMonth(midMonth);
  _date.setMonth(month, Math.min(day, daysInMonth));
  return _date;
}
function set(date, values, options) {
  let _date = toDate(date, options?.in);
  if (isNaN(+_date)) return constructFrom(date, NaN);
  if (values.year != null) _date.setFullYear(values.year);
  if (values.month != null) _date = setMonth(_date, values.month);
  if (values.date != null) _date.setDate(values.date);
  if (values.hours != null) _date.setHours(values.hours);
  if (values.minutes != null) _date.setMinutes(values.minutes);
  if (values.seconds != null) _date.setSeconds(values.seconds);
  if (values.milliseconds != null) _date.setMilliseconds(values.milliseconds);
  return _date;
}
function setHours(date, hours, options) {
  const _date = toDate(date, options?.in);
  _date.setHours(hours);
  return _date;
}
function setMilliseconds(date, milliseconds, options) {
  const _date = toDate(date, options?.in);
  _date.setMilliseconds(milliseconds);
  return _date;
}
function setMinutes(date, minutes, options) {
  const date_ = toDate(date, options?.in);
  date_.setMinutes(minutes);
  return date_;
}
function setSeconds(date, seconds, options) {
  const _date = toDate(date, options?.in);
  _date.setSeconds(seconds);
  return _date;
}
function setYear(date, year, options) {
  const date_ = toDate(date, options?.in);
  if (isNaN(+date_)) return constructFrom(date, NaN);
  date_.setFullYear(year);
  return date_;
}
function subMonths(date, amount, options) {
  return addMonths(date, -amount, options);
}
function sub(date, duration, options) {
  const {
    years = 0,
    months = 0,
    weeks = 0,
    days = 0,
    hours = 0,
    minutes = 0,
    seconds = 0
  } = duration;
  const withoutMonths = subMonths(date, months + years * 12, options);
  const withoutDays = subDays(withoutMonths, days + weeks * 7, options);
  const minutesToSub = minutes + hours * 60;
  const secondsToSub = seconds + minutesToSub * 60;
  const msToSub = secondsToSub * 1e3;
  return constructFrom(date, +withoutDays - msToSub);
}
function subYears(date, amount, options) {
  return addYears(date, -amount, options);
}
function jt() {
  const e = useAttrs();
  return openBlock(), createElementBlock(
    "svg",
    {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 32 32",
      fill: "currentColor",
      "aria-hidden": "true",
      class: "dp__icon",
      role: "img",
      ...e
    },
    [
      createBaseVNode("path", {
        d: "M29.333 8c0-2.208-1.792-4-4-4h-18.667c-2.208 0-4 1.792-4 4v18.667c0 2.208 1.792 4 4 4h18.667c2.208 0 4-1.792 4-4v-18.667zM26.667 8v18.667c0 0.736-0.597 1.333-1.333 1.333 0 0-18.667 0-18.667 0-0.736 0-1.333-0.597-1.333-1.333 0 0 0-18.667 0-18.667 0-0.736 0.597-1.333 1.333-1.333 0 0 18.667 0 18.667 0 0.736 0 1.333 0.597 1.333 1.333z"
      }),
      createBaseVNode("path", {
        d: "M20 2.667v5.333c0 0.736 0.597 1.333 1.333 1.333s1.333-0.597 1.333-1.333v-5.333c0-0.736-0.597-1.333-1.333-1.333s-1.333 0.597-1.333 1.333z"
      }),
      createBaseVNode("path", {
        d: "M9.333 2.667v5.333c0 0.736 0.597 1.333 1.333 1.333s1.333-0.597 1.333-1.333v-5.333c0-0.736-0.597-1.333-1.333-1.333s-1.333 0.597-1.333 1.333z"
      }),
      createBaseVNode("path", {
        d: "M4 14.667h24c0.736 0 1.333-0.597 1.333-1.333s-0.597-1.333-1.333-1.333h-24c-0.736 0-1.333 0.597-1.333 1.333s0.597 1.333 1.333 1.333z"
      })
    ]
  );
}
jt.compatConfig = {
  MODE: 3
};
function Sn() {
  return openBlock(), createElementBlock(
    "svg",
    {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 32 32",
      fill: "currentColor",
      "aria-hidden": "true",
      class: "dp__icon",
      role: "img"
    },
    [
      createBaseVNode("path", {
        d: "M23.057 7.057l-16 16c-0.52 0.52-0.52 1.365 0 1.885s1.365 0.52 1.885 0l16-16c0.52-0.52 0.52-1.365 0-1.885s-1.365-0.52-1.885 0z"
      }),
      createBaseVNode("path", {
        d: "M7.057 8.943l16 16c0.52 0.52 1.365 0.52 1.885 0s0.52-1.365 0-1.885l-16-16c-0.52-0.52-1.365-0.52-1.885 0s-0.52 1.365 0 1.885z"
      })
    ]
  );
}
Sn.compatConfig = {
  MODE: 3
};
function Ua() {
  return openBlock(), createElementBlock(
    "svg",
    {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 32 32",
      fill: "currentColor",
      "aria-hidden": "true",
      class: "dp__icon",
      role: "img"
    },
    [
      createBaseVNode("path", {
        d: "M20.943 23.057l-7.057-7.057c0 0 7.057-7.057 7.057-7.057 0.52-0.52 0.52-1.365 0-1.885s-1.365-0.52-1.885 0l-8 8c-0.521 0.521-0.521 1.365 0 1.885l8 8c0.52 0.52 1.365 0.52 1.885 0s0.52-1.365 0-1.885z"
      })
    ]
  );
}
Ua.compatConfig = {
  MODE: 3
};
function Va() {
  return openBlock(), createElementBlock(
    "svg",
    {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 32 32",
      fill: "currentColor",
      "aria-hidden": "true",
      class: "dp__icon",
      role: "img"
    },
    [
      createBaseVNode("path", {
        d: "M12.943 24.943l8-8c0.521-0.521 0.521-1.365 0-1.885l-8-8c-0.52-0.52-1.365-0.52-1.885 0s-0.52 1.365 0 1.885l7.057 7.057c0 0-7.057 7.057-7.057 7.057-0.52 0.52-0.52 1.365 0 1.885s1.365 0.52 1.885 0z"
      })
    ]
  );
}
Va.compatConfig = {
  MODE: 3
};
function ja() {
  return openBlock(), createElementBlock(
    "svg",
    {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 32 32",
      fill: "currentColor",
      "aria-hidden": "true",
      class: "dp__icon",
      role: "img"
    },
    [
      createBaseVNode("path", {
        d: "M16 1.333c-8.095 0-14.667 6.572-14.667 14.667s6.572 14.667 14.667 14.667c8.095 0 14.667-6.572 14.667-14.667s-6.572-14.667-14.667-14.667zM16 4c6.623 0 12 5.377 12 12s-5.377 12-12 12c-6.623 0-12-5.377-12-12s5.377-12 12-12z"
      }),
      createBaseVNode("path", {
        d: "M14.667 8v8c0 0.505 0.285 0.967 0.737 1.193l5.333 2.667c0.658 0.329 1.46 0.062 1.789-0.596s0.062-1.46-0.596-1.789l-4.596-2.298c0 0 0-7.176 0-7.176 0-0.736-0.597-1.333-1.333-1.333s-1.333 0.597-1.333 1.333z"
      })
    ]
  );
}
ja.compatConfig = {
  MODE: 3
};
function Ka() {
  return openBlock(), createElementBlock(
    "svg",
    {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 32 32",
      fill: "currentColor",
      "aria-hidden": "true",
      class: "dp__icon",
      role: "img"
    },
    [
      createBaseVNode("path", {
        d: "M24.943 19.057l-8-8c-0.521-0.521-1.365-0.521-1.885 0l-8 8c-0.52 0.52-0.52 1.365 0 1.885s1.365 0.52 1.885 0l7.057-7.057c0 0 7.057 7.057 7.057 7.057 0.52 0.52 1.365 0.52 1.885 0s0.52-1.365 0-1.885z"
      })
    ]
  );
}
Ka.compatConfig = {
  MODE: 3
};
function Ga() {
  return openBlock(), createElementBlock(
    "svg",
    {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 32 32",
      fill: "currentColor",
      "aria-hidden": "true",
      class: "dp__icon",
      role: "img"
    },
    [
      createBaseVNode("path", {
        d: "M7.057 12.943l8 8c0.521 0.521 1.365 0.521 1.885 0l8-8c0.52-0.52 0.52-1.365 0-1.885s-1.365-0.52-1.885 0l-7.057 7.057c0 0-7.057-7.057-7.057-7.057-0.52-0.52-1.365-0.52-1.885 0s-0.52 1.365 0 1.885z"
      })
    ]
  );
}
Ga.compatConfig = {
  MODE: 3
};
const Ze = (e, t2) => t2 ? new Date(e.toLocaleString("en-US", { timeZone: t2 })) : new Date(e), Qa = (e, t2, l) => {
  const n = La(e, t2, l);
  return n || H();
}, wl = (e, t2, l) => {
  const n = t2.dateInTz ? Ze(new Date(e), t2.dateInTz) : H(e);
  return l ? Fe(n, true) : n;
}, La = (e, t2, l) => {
  if (!e) return null;
  const n = l ? Fe(H(e), true) : H(e);
  return t2 ? t2.exactMatch ? wl(e, t2, l) : Ze(n, t2.timezone) : n;
}, Dl = (e) => {
  const l = new Date(e.getFullYear(), 0, 1).getTimezoneOffset();
  return e.getTimezoneOffset() < l;
}, Ml = (e, t2) => {
  if (!e) return 0;
  const l = /* @__PURE__ */ new Date(), n = new Date(l.toLocaleString("en-US", { timeZone: "UTC" })), a = new Date(l.toLocaleString("en-US", { timeZone: e })), i = (Dl(t2 ?? a) ? a : t2 ?? a).getTimezoneOffset() / 60;
  return (+n - +a) / (1e3 * 60 * 60) - i;
};
var ot = /* @__PURE__ */ ((e) => (e.month = "month", e.year = "year", e))(ot || {}), st = /* @__PURE__ */ ((e) => (e.top = "top", e.bottom = "bottom", e))(st || {}), Ot = /* @__PURE__ */ ((e) => (e.header = "header", e.calendar = "calendar", e.timePicker = "timePicker", e))(Ot || {}), je = /* @__PURE__ */ ((e) => (e.month = "month", e.year = "year", e.calendar = "calendar", e.time = "time", e.minutes = "minutes", e.hours = "hours", e.seconds = "seconds", e))(je || {});
const $l = ["timestamp", "date", "iso"];
var Qe = /* @__PURE__ */ ((e) => (e.up = "up", e.down = "down", e.left = "left", e.right = "right", e))(Qe || {}), Re = /* @__PURE__ */ ((e) => (e.arrowUp = "ArrowUp", e.arrowDown = "ArrowDown", e.arrowLeft = "ArrowLeft", e.arrowRight = "ArrowRight", e.enter = "Enter", e.space = " ", e.esc = "Escape", e.tab = "Tab", e.home = "Home", e.end = "End", e.pageUp = "PageUp", e.pageDown = "PageDown", e))(Re || {}), Lt = /* @__PURE__ */ ((e) => (e.MONTH_AND_YEAR = "MM-yyyy", e.YEAR = "yyyy", e.DATE = "dd-MM-yyyy", e))(Lt || {});
function un(e) {
  return (t2) => {
    const l = new Intl.DateTimeFormat(e, {
      weekday: "short",
      timeZone: "UTC"
    }).format(/* @__PURE__ */ new Date(`2017-01-0${t2}T00:00:00+00:00`));
    return e === "ar" ? l.slice(2, 5) : l.slice(0, 2);
  };
}
function Al(e) {
  return (t2) => format(Ze(/* @__PURE__ */ new Date(`2017-01-0${t2}T00:00:00+00:00`), "UTC"), "EEEEEE", { locale: e });
}
const Tl = (e, t2, l) => {
  const n = [1, 2, 3, 4, 5, 6, 7];
  let a;
  if (e !== null)
    try {
      a = n.map(Al(e));
    } catch {
      a = n.map(un(t2));
    }
  else
    a = n.map(un(t2));
  const f = a.slice(0, l), i = a.slice(l + 1, a.length);
  return [a[l]].concat(...i).concat(...f);
}, qa = (e, t2, l) => {
  const n = [];
  for (let a = +e[0]; a <= +e[1]; a++)
    n.push({ value: +a, text: Ja(a, t2) });
  return l ? n.reverse() : n;
}, Pn = (e, t2, l) => {
  const n = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12].map((f) => {
    const i = f < 10 ? `0${f}` : f;
    return /* @__PURE__ */ new Date(`2017-${i}-01T00:00:00+00:00`);
  });
  if (e !== null)
    try {
      const f = l === "long" ? "LLLL" : "LLL";
      return n.map((i, g) => {
        const d = format(Ze(i, "UTC"), f, { locale: e });
        return {
          text: d.charAt(0).toUpperCase() + d.substring(1),
          value: g
        };
      });
    } catch {
    }
  const a = new Intl.DateTimeFormat(t2, { month: l, timeZone: "UTC" });
  return n.map((f, i) => {
    const g = a.format(f);
    return {
      text: g.charAt(0).toUpperCase() + g.substring(1),
      value: i
    };
  });
}, Sl = (e) => [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11][e], Le = (e) => {
  const t2 = unref(e);
  return t2?.$el ? t2?.$el : t2;
}, Pl = (e) => ({ type: "dot", ...e ?? {} }), Rn = (e) => Array.isArray(e) ? !!e[0] && !!e[1] : false, Xa = {
  prop: (e) => `"${e}" prop must be enabled!`,
  dateArr: (e) => `You need to use array as "model-value" binding in order to support "${e}"`
}, Ne = (e) => e, dn = (e) => e === 0 ? e : !e || isNaN(+e) ? null : +e, cn = (e) => e === null, Cn = (e) => {
  if (e)
    return [...e.querySelectorAll("input, button, select, textarea, a[href]")][0];
}, Rl = (e) => {
  const t2 = [], l = (n) => n.filter((a) => a);
  for (let n = 0; n < e.length; n += 3) {
    const a = [e[n], e[n + 1], e[n + 2]];
    t2.push(l(a));
  }
  return t2;
}, Zt = (e, t2, l) => {
  const n = l != null, a = t2 != null;
  if (!n && !a) return false;
  const f = +l, i = +t2;
  return n && a ? +e > f || +e < i : n ? +e > f : a ? +e < i : false;
}, Ht = (e, t2) => Rl(e).map((l) => l.map((n) => {
  const { active: a, disabled: f, isBetween: i, highlighted: g } = t2(n);
  return {
    ...n,
    active: a,
    disabled: f,
    className: {
      dp__overlay_cell_active: a,
      dp__overlay_cell: !a,
      dp__overlay_cell_disabled: f,
      dp__overlay_cell_pad: true,
      dp__overlay_cell_active_disabled: f && a,
      dp__cell_in_between: i,
      "dp--highlighted": g
    }
  };
})), Dt = (e, t2, l = false) => {
  e && t2.allowStopPropagation && (l && e.stopImmediatePropagation(), e.stopPropagation());
}, Cl = () => [
  "a[href]",
  "area[href]",
  "input:not([disabled]):not([type='hidden'])",
  "select:not([disabled])",
  "textarea:not([disabled])",
  "button:not([disabled])",
  "[tabindex]:not([tabindex='-1'])",
  "[data-datepicker-instance]"
].join(", ");
function Ol(e, t2) {
  let l = [...document.querySelectorAll(Cl())];
  l = l.filter((a) => !e.contains(a) || a.hasAttribute("data-datepicker-instance"));
  const n = l.indexOf(e);
  if (n >= 0 && (t2 ? n - 1 >= 0 : n + 1 <= l.length))
    return l[n + (t2 ? -1 : 1)];
}
const Fa = (e, t2) => e?.querySelector(`[data-dp-element="${t2}"]`), Ja = (e, t2) => new Intl.NumberFormat(t2, { useGrouping: false, style: "decimal" }).format(e), Za = (e, t2) => format(e, t2 ?? Lt.DATE), Ta = (e) => Array.isArray(e), ca = (e, t2, l) => t2.get(Za(e, l)), Bl = (e, t2) => e ? t2 ? t2 instanceof Map ? !!ca(e, t2) : t2(H(e)) : false : true, qe = (e, t2, l = false, n) => {
  if (e.key === Re.enter || e.key === Re.space)
    return l && e.preventDefault(), t2();
  if (n) return n(e);
}, _l = () => "ontouchstart" in window || navigator.maxTouchPoints > 0, On = (e, t2) => e ? Lt.MONTH_AND_YEAR : t2 ? Lt.YEAR : Lt.DATE, Bn = (e) => e < 10 ? `0${e}` : e, fn = (e, t2, l, n, a, f) => {
  const i = parse(e, t2.slice(0, e.length), /* @__PURE__ */ new Date(), { locale: f });
  return isValid(i) && isDate(i) ? n || a ? i : set(i, {
    hours: +l.hours,
    minutes: +l?.minutes,
    seconds: +l?.seconds,
    milliseconds: 0
  }) : null;
}, Yl = (e, t2, l, n, a, f) => {
  const i = Array.isArray(l) ? l[0] : l;
  if (typeof t2 == "string")
    return fn(e, t2, i, n, a, f);
  if (Array.isArray(t2)) {
    let g = null;
    for (const d of t2)
      if (g = fn(e, d, i, n, a, f), g)
        break;
    return g;
  }
  return typeof t2 == "function" ? t2(e) : null;
}, H = (e) => e ? new Date(e) : /* @__PURE__ */ new Date(), Il = (e, t2, l) => {
  if (t2) {
    const a = (e.getMonth() + 1).toString().padStart(2, "0"), f = e.getDate().toString().padStart(2, "0"), i = e.getHours().toString().padStart(2, "0"), g = e.getMinutes().toString().padStart(2, "0"), d = l ? e.getSeconds().toString().padStart(2, "0") : "00";
    return `${e.getFullYear()}-${a}-${f}T${i}:${g}:${d}.000Z`;
  }
  const n = Date.UTC(
    e.getUTCFullYear(),
    e.getUTCMonth(),
    e.getUTCDate(),
    e.getUTCHours(),
    e.getUTCMinutes(),
    e.getUTCSeconds()
  );
  return new Date(n).toISOString();
}, Fe = (e, t2) => {
  const l = H(JSON.parse(JSON.stringify(e))), n = set(l, { hours: 0, minutes: 0, seconds: 0, milliseconds: 0 });
  return t2 ? startOfMonth(n) : n;
}, Mt = (e, t2, l, n) => {
  let a = e ? H(e) : H();
  return (t2 || t2 === 0) && (a = setHours(a, +t2)), (l || l === 0) && (a = setMinutes(a, +l)), (n || n === 0) && (a = setSeconds(a, +n)), setMilliseconds(a, 0);
}, Be = (e, t2) => !e || !t2 ? false : isBefore(Fe(e), Fe(t2)), $e = (e, t2) => !e || !t2 ? false : isEqual(Fe(e), Fe(t2)), Ee = (e, t2) => !e || !t2 ? false : isAfter(Fe(e), Fe(t2)), xt = (e, t2, l) => e?.[0] && e?.[1] ? Ee(l, e[0]) && Be(l, e[1]) : e?.[0] && t2 ? Ee(l, e[0]) && Be(l, t2) || Be(l, e[0]) && Ee(l, t2) : false, it = (e) => {
  const t2 = set(new Date(e), { date: 1 });
  return Fe(t2);
}, Sa = (e, t2, l) => t2 && (l || l === 0) ? Object.fromEntries(
  ["hours", "minutes", "seconds"].map((n) => n === t2 ? [n, l] : [n, isNaN(+e[n]) ? void 0 : +e[n]])
) : {
  hours: isNaN(+e.hours) ? void 0 : +e.hours,
  minutes: isNaN(+e.minutes) ? void 0 : +e.minutes,
  seconds: isNaN(+e.seconds) ? void 0 : +e.seconds
}, Bt = (e) => ({
  hours: getHours(e),
  minutes: getMinutes(e),
  seconds: getSeconds(e)
}), _n = (e, t2) => {
  if (t2) {
    const l = getYear(H(t2));
    if (l > e) return 12;
    if (l === e) return getMonth(H(t2));
  }
}, Yn = (e, t2) => {
  if (t2) {
    const l = getYear(H(t2));
    return l < e ? -1 : l === e ? getMonth(H(t2)) : void 0;
  }
}, Wt = (e) => {
  if (e) return getYear(H(e));
}, In = (e, t2) => {
  const l = Ee(e, t2) ? t2 : e, n = Ee(t2, e) ? t2 : e;
  return eachDayOfInterval({ start: l, end: n });
}, El = (e) => {
  const t2 = addMonths(e, 1);
  return { month: getMonth(t2), year: getYear(t2) };
}, mt = (e, t2) => {
  const l = startOfWeek(e, { weekStartsOn: +t2 }), n = endOfWeek(e, { weekStartsOn: +t2 });
  return [l, n];
}, En = (e, t2) => {
  const l = {
    hours: getHours(H()),
    minutes: getMinutes(H()),
    seconds: t2 ? getSeconds(H()) : 0
  };
  return Object.assign(l, e);
}, wt = (e, t2, l) => [set(H(e), { date: 1 }), set(H(), { month: t2, year: l, date: 1 })], pt = (e, t2, l) => {
  let n = e ? H(e) : H();
  return (t2 || t2 === 0) && (n = setMonth(n, t2)), l && (n = setYear(n, l)), n;
}, Nn = (e, t2, l, n, a) => {
  if (!n || a && !t2 || !a && !l) return false;
  const f = a ? addMonths(e, 1) : subMonths(e, 1), i = [getMonth(f), getYear(f)];
  return a ? !Ll(...i, t2) : !Nl(...i, l);
}, Nl = (e, t2, l) => Be(...wt(l, e, t2)) || $e(...wt(l, e, t2)), Ll = (e, t2, l) => Ee(...wt(l, e, t2)) || $e(...wt(l, e, t2)), Ln = (e, t2, l, n, a, f, i) => {
  if (typeof t2 == "function" && !i) return t2(e);
  const g = l ? { locale: l } : void 0;
  return Array.isArray(e) ? `${format(e[0], f, g)}${a && !e[1] ? "" : n}${e[1] ? format(e[1], f, g) : ""}` : format(e, f, g);
}, It = (e) => {
  if (e) return null;
  throw new Error(Xa.prop("partial-range"));
}, oa = (e, t2) => {
  if (t2) return e();
  throw new Error(Xa.prop("range"));
}, za = (e) => Array.isArray(e) ? isValid(e[0]) && (e[1] ? isValid(e[1]) : true) : e ? isValid(e) : false, Fl = (e, t2) => set(t2 ?? H(), {
  hours: +e.hours || 0,
  minutes: +e.minutes || 0,
  seconds: +e.seconds || 0
}), Pa = (e, t2, l, n) => {
  if (!e) return true;
  if (n) {
    const a = l === "max" ? isBefore(e, t2) : isAfter(e, t2), f = { seconds: 0, milliseconds: 0 };
    return a || isEqual(set(e, f), set(t2, f));
  }
  return l === "max" ? e.getTime() <= t2.getTime() : e.getTime() >= t2.getTime();
}, Ra = (e, t2, l) => e ? Fl(e, t2) : H(l ?? t2), vn = (e, t2, l, n, a) => {
  if (Array.isArray(n)) {
    const i = Ra(e, n[0], t2), g = Ra(e, n[1], t2);
    return Pa(n[0], i, l, !!t2) && Pa(n[1], g, l, !!t2) && a;
  }
  const f = Ra(e, n, t2);
  return Pa(n, f, l, !!t2) && a;
}, Ca = (e) => set(H(), Bt(e)), zl = (e, t2, l) => {
  if (e instanceof Map) {
    const n = `${Bn(l + 1)}-${t2}`;
    return e.size ? e.has(n) : false;
  }
  return typeof e == "function" ? e(Fe(set(H(), { month: l, year: t2 }), true)) : false;
}, Hl = (e, t2, l) => {
  if (e instanceof Map) {
    const n = `${Bn(l + 1)}-${t2}`;
    return e.size ? e.has(n) : true;
  }
  return true;
}, Fn = (e, t2, l) => typeof e == "function" ? e({ month: t2, year: l }) : !!e.months.find((n) => n.month === t2 && n.year === l), xa = (e, t2) => typeof e == "function" ? e(t2) : e.years.includes(t2), Ha = (e) => `dp-${format(e, "yyyy-MM-dd")}`, mn = (e, t2) => {
  const l = subDays(Fe(t2), e), n = addDays(Fe(t2), e);
  return { before: l, after: n };
}, zn = (e, t2) => t2 < +e[0] || t2 > +e[1], Kt = reactive({
  menuFocused: false,
  shiftKeyInMenu: false
}), Hn = () => {
  const e = (n) => {
    Kt.menuFocused = n;
  }, t2 = (n) => {
    Kt.shiftKeyInMenu !== n && (Kt.shiftKeyInMenu = n);
  };
  return {
    control: computed(() => ({ shiftKeyInMenu: Kt.shiftKeyInMenu, menuFocused: Kt.menuFocused })),
    setMenuFocused: e,
    setShiftKey: t2
  };
}, Pe = reactive({
  monthYear: [],
  calendar: [],
  time: [],
  actionRow: [],
  selectionGrid: [],
  timePicker: {
    0: [],
    1: []
  },
  monthPicker: []
}), Oa = ref(null), sa = ref(false), Ba = ref(false), _a = ref(false), Ya = ref(false), Ve = ref(0), Ie = ref(0), At = () => {
  const e = computed(() => sa.value ? [...Pe.selectionGrid, Pe.actionRow].filter((M) => M.length) : Ba.value ? [
    ...Pe.timePicker[0],
    ...Pe.timePicker[1],
    Ya.value ? [] : [Oa.value],
    Pe.actionRow
  ].filter((M) => M.length) : _a.value ? [...Pe.monthPicker, Pe.actionRow] : [Pe.monthYear, ...Pe.calendar, Pe.time, Pe.actionRow].filter((M) => M.length)), t2 = (M) => {
    Ve.value = M ? Ve.value + 1 : Ve.value - 1;
    let A = null;
    e.value[Ie.value] && (A = e.value[Ie.value][Ve.value]), !A && e.value[Ie.value + (M ? 1 : -1)] ? (Ie.value = Ie.value + (M ? 1 : -1), Ve.value = M ? 0 : e.value[Ie.value].length - 1) : A || (Ve.value = M ? Ve.value - 1 : Ve.value + 1);
  }, l = (M) => {
    if (Ie.value === 0 && !M || Ie.value === e.value.length && M) return;
    Ie.value = M ? Ie.value + 1 : Ie.value - 1, e.value[Ie.value] ? e.value[Ie.value] && !e.value[Ie.value][Ve.value] && Ve.value !== 0 && (Ve.value = e.value[Ie.value].length - 1) : Ie.value = M ? Ie.value - 1 : Ie.value + 1;
  }, n = (M) => {
    let A = null;
    e.value[Ie.value] && (A = e.value[Ie.value][Ve.value]), A ? A.focus({ preventScroll: !sa.value }) : Ve.value = M ? Ve.value - 1 : Ve.value + 1;
  }, a = () => {
    t2(true), n(true);
  }, f = () => {
    t2(false), n(false);
  }, i = () => {
    l(false), n(true);
  }, g = () => {
    l(true), n(true);
  }, d = (M, A) => {
    Pe[A] = M;
  }, P = (M, A) => {
    Pe[A] = M;
  }, v = () => {
    Ve.value = 0, Ie.value = 0;
  };
  return {
    buildMatrix: d,
    buildMultiLevelMatrix: P,
    setTimePickerBackRef: (M) => {
      Oa.value = M;
    },
    setSelectionGrid: (M) => {
      sa.value = M, v(), M || (Pe.selectionGrid = []);
    },
    setTimePicker: (M, A = false) => {
      Ba.value = M, Ya.value = A, v(), M || (Pe.timePicker[0] = [], Pe.timePicker[1] = []);
    },
    setTimePickerElements: (M, A = 0) => {
      Pe.timePicker[A] = M;
    },
    arrowRight: a,
    arrowLeft: f,
    arrowUp: i,
    arrowDown: g,
    clearArrowNav: () => {
      Pe.monthYear = [], Pe.calendar = [], Pe.time = [], Pe.actionRow = [], Pe.selectionGrid = [], Pe.timePicker[0] = [], Pe.timePicker[1] = [], sa.value = false, Ba.value = false, Ya.value = false, _a.value = false, v(), Oa.value = null;
    },
    setMonthPicker: (M) => {
      _a.value = M, v();
    },
    refSets: Pe
    // exposed for testing
  };
}, pn = (e) => ({
  menuAppearTop: "dp-menu-appear-top",
  menuAppearBottom: "dp-menu-appear-bottom",
  open: "dp-slide-down",
  close: "dp-slide-up",
  next: "calendar-next",
  previous: "calendar-prev",
  vNext: "dp-slide-up",
  vPrevious: "dp-slide-down",
  ...e ?? {}
}), Wl = (e) => ({
  toggleOverlay: "Toggle overlay",
  menu: "Datepicker menu",
  input: "Datepicker input",
  openTimePicker: "Open time picker",
  closeTimePicker: "Close time Picker",
  incrementValue: (t2) => `Increment ${t2}`,
  decrementValue: (t2) => `Decrement ${t2}`,
  openTpOverlay: (t2) => `Open ${t2} overlay`,
  amPmButton: "Switch AM/PM mode",
  openYearsOverlay: "Open years overlay",
  openMonthsOverlay: "Open months overlay",
  nextMonth: "Next month",
  prevMonth: "Previous month",
  nextYear: "Next year",
  prevYear: "Previous year",
  day: void 0,
  weekDay: void 0,
  clearInput: "Clear value",
  calendarIcon: "Calendar icon",
  timePicker: "Time picker",
  monthPicker: (t2) => `Month picker${t2 ? " overlay" : ""}`,
  yearPicker: (t2) => `Year picker${t2 ? " overlay" : ""}`,
  timeOverlay: (t2) => `${t2} overlay`,
  ...e ?? {}
}), gn = (e) => e ? typeof e == "boolean" ? e ? 2 : 0 : +e >= 2 ? +e : 2 : 0, Ul = (e) => {
  const t2 = typeof e == "object" && e, l = {
    static: true,
    solo: false
  };
  if (!e) return { ...l, count: gn(false) };
  const n = t2 ? e : {}, a = t2 ? n.count ?? true : e, f = gn(a);
  return Object.assign(l, n, { count: f });
}, Vl = (e, t2, l) => e || (typeof l == "string" ? l : t2), jl = (e) => typeof e == "boolean" ? e ? pn({}) : false : pn(e), Kl = (e) => {
  const t2 = {
    enterSubmit: true,
    tabSubmit: true,
    openMenu: "open",
    selectOnFocus: false,
    rangeSeparator: " - ",
    escClose: true
  };
  return typeof e == "object" ? { ...t2, ...e ?? {}, enabled: true } : { ...t2, enabled: e };
}, Gl = (e) => ({
  months: [],
  years: [],
  times: { hours: [], minutes: [], seconds: [] },
  ...e ?? {}
}), Ql = (e) => ({
  showSelect: true,
  showCancel: true,
  showNow: false,
  showPreview: true,
  ...e ?? {}
}), ql = (e) => {
  const t2 = { input: false };
  return typeof e == "object" ? { ...t2, ...e ?? {}, enabled: true } : {
    enabled: e,
    ...t2
  };
}, Xl = (e) => ({ ...{
  allowStopPropagation: true,
  closeOnScroll: false,
  modeHeight: 255,
  allowPreventDefault: false,
  closeOnClearValue: true,
  closeOnAutoApply: true,
  noSwipe: false,
  keepActionRow: false,
  onClickOutside: void 0,
  tabOutClosesMenu: true,
  arrowLeft: void 0,
  keepViewOnOffsetClick: false,
  timeArrowHoldThreshold: 0,
  shadowDom: false,
  mobileBreakpoint: 600,
  setDateOnMenuClose: false
}, ...e ?? {} }), Jl = (e) => {
  const t2 = {
    dates: Array.isArray(e) ? e.map((l) => H(l)) : [],
    years: [],
    months: [],
    quarters: [],
    weeks: [],
    weekdays: [],
    options: { highlightDisabled: false }
  };
  return typeof e == "function" ? e : { ...t2, ...e ?? {} };
}, Zl = (e) => typeof e == "object" ? {
  type: e?.type ?? "local",
  hideOnOffsetDates: e?.hideOnOffsetDates ?? false
} : {
  type: e,
  hideOnOffsetDates: false
}, xl = (e) => {
  const t2 = {
    noDisabledRange: false,
    showLastInRange: true,
    minMaxRawRange: false,
    partialRange: true,
    disableTimeRangeValidation: false,
    maxRange: void 0,
    minRange: void 0,
    autoRange: void 0,
    fixedStart: false,
    fixedEnd: false
  };
  return typeof e == "object" ? { enabled: true, ...t2, ...e } : {
    enabled: e,
    ...t2
  };
}, er = (e) => e ? typeof e == "string" ? {
  timezone: e,
  exactMatch: false,
  dateInTz: void 0,
  emitTimezone: void 0,
  convertModel: true
} : {
  timezone: e.timezone,
  exactMatch: e.exactMatch ?? false,
  dateInTz: e.dateInTz ?? void 0,
  emitTimezone: e.emitTimezone ?? void 0,
  convertModel: e.convertModel ?? true
} : { timezone: void 0, exactMatch: false, emitTimezone: void 0 }, Ia = (e, t2, l, n) => new Map(
  e.map((a) => {
    const f = Qa(a, t2, n);
    return [Za(f, l), f];
  })
), tr = (e, t2) => e.length ? new Map(
  e.map((l) => {
    const n = Qa(l.date, t2);
    return [Za(n, Lt.DATE), l];
  })
) : null, ar = (e) => {
  const t2 = On(e.isMonthPicker, e.isYearPicker);
  return {
    minDate: La(e.minDate, e.timezone, e.isSpecific),
    maxDate: La(e.maxDate, e.timezone, e.isSpecific),
    disabledDates: Ta(e.disabledDates) ? Ia(e.disabledDates, e.timezone, t2, e.isSpecific) : e.disabledDates,
    allowedDates: Ta(e.allowedDates) ? Ia(e.allowedDates, e.timezone, t2, e.isSpecific) : null,
    highlight: typeof e.highlight == "object" && Ta(e.highlight?.dates) ? Ia(e.highlight.dates, e.timezone, t2) : e.highlight,
    markers: tr(e.markers, e.timezone)
  };
}, nr = (e) => typeof e == "boolean" ? { enabled: e, dragSelect: true, limit: null } : {
  enabled: !!e,
  limit: e.limit ? +e.limit : null,
  dragSelect: e.dragSelect ?? true
}, lr = (e) => ({
  ...Object.fromEntries(
    Object.keys(e).map((l) => {
      const n = l, a = e[n], f = typeof e[n] == "string" ? { [a]: true } : Object.fromEntries(a.map((i) => [i, true]));
      return [l, f];
    })
  )
}), Oe = (e) => {
  const t2 = () => {
    const U = e.enableSeconds ? ":ss" : "", $ = e.enableMinutes ? ":mm" : "";
    return e.is24 ? `HH${$}${U}` : `hh${$}${U} aa`;
  }, l = () => e.format ? e.format : e.monthPicker ? "MM/yyyy" : e.timePicker ? t2() : e.weekPicker ? `${C.value?.type === "iso" ? "II" : "ww"}-RR` : e.yearPicker ? "yyyy" : e.quarterPicker ? "QQQ/yyyy" : e.enableTimePicker ? `MM/dd/yyyy, ${t2()}` : "MM/dd/yyyy", n = (U) => En(U, e.enableSeconds), a = () => z.value.enabled ? e.startTime && Array.isArray(e.startTime) ? [n(e.startTime[0]), n(e.startTime[1])] : null : e.startTime && !Array.isArray(e.startTime) ? n(e.startTime) : null, f = computed(() => Ul(e.multiCalendars)), i = computed(() => a()), g = computed(() => Wl(e.ariaLabels)), d = computed(() => Gl(e.filters)), P = computed(() => jl(e.transitions)), v = computed(() => Ql(e.actionRow)), R = computed(
    () => Vl(e.previewFormat, e.format, l())
  ), h2 = computed(() => Kl(e.textInput)), T = computed(() => ql(e.inline)), F = computed(() => Xl(e.config)), _ = computed(() => Jl(e.highlight)), C = computed(() => Zl(e.weekNumbers)), M = computed(() => er(e.timezone)), A = computed(() => nr(e.multiDates)), m = computed(
    () => ar({
      minDate: e.minDate,
      maxDate: e.maxDate,
      disabledDates: e.disabledDates,
      allowedDates: e.allowedDates,
      highlight: _.value,
      markers: e.markers,
      timezone: M.value,
      isSpecific: e.monthPicker || e.yearPicker || e.quarterPicker,
      isMonthPicker: e.monthPicker,
      isYearPicker: e.yearPicker
    })
  ), z = computed(() => xl(e.range)), L = computed(() => lr(e.ui));
  return {
    defaultedTransitions: P,
    defaultedMultiCalendars: f,
    defaultedStartTime: i,
    defaultedAriaLabels: g,
    defaultedFilters: d,
    defaultedActionRow: v,
    defaultedPreviewFormat: R,
    defaultedTextInput: h2,
    defaultedInline: T,
    defaultedConfig: F,
    defaultedHighlight: _,
    defaultedWeekNumbers: C,
    defaultedRange: z,
    propDates: m,
    defaultedTz: M,
    defaultedMultiDates: A,
    defaultedUI: L,
    getDefaultPattern: l,
    getDefaultStartTime: a,
    handleEventPropagation: (U) => {
      F.value.allowStopPropagation && U.stopPropagation(), F.value.allowPreventDefault && U.preventDefault();
    }
  };
}, rr = (e, t2, { isInputFocused: l, isTextInputDate: n }) => {
  const a = ref(), { defaultedTextInput: f, defaultedRange: i, defaultedTz: g, defaultedMultiDates: d, getDefaultPattern: P } = Oe(t2), v = ref(""), R = toRef(t2, "format"), h2 = toRef(t2, "formatLocale");
  watch(
    a,
    () => {
      typeof t2.onInternalModelChange == "function" && e("internal-model-change", a.value, x(true));
    },
    { deep: true }
  ), watch(i, (o, X) => {
    o.enabled !== X.enabled && (a.value = null);
  }), watch(R, () => {
    S();
  });
  const T = (o) => g.value.timezone && g.value.convertModel ? Ze(o, g.value.timezone) : o, F = (o) => {
    if (g.value.timezone && g.value.convertModel) {
      const X = Ml(g.value.timezone, o);
      return addHours(o, X);
    }
    return o;
  }, _ = (o, X, B = false) => Ln(
    o,
    t2.format,
    t2.formatLocale,
    f.value.rangeSeparator,
    t2.modelAuto,
    X ?? P(),
    B
  ), C = (o) => o ? t2.modelType ? k(o) : {
    hours: getHours(o),
    minutes: getMinutes(o),
    seconds: t2.enableSeconds ? getSeconds(o) : 0
  } : null, M = (o) => t2.modelType ? k(o) : { month: getMonth(o), year: getYear(o) }, A = (o) => Array.isArray(o) ? d.value.enabled ? o.map((X) => m(X, setYear(H(), X))) : oa(
    () => [
      setYear(H(), o[0]),
      o[1] ? setYear(H(), o[1]) : It(i.value.partialRange)
    ],
    i.value.enabled
  ) : setYear(H(), +o), m = (o, X) => (typeof o == "string" || typeof o == "number") && t2.modelType ? q(o) : X, z = (o) => Array.isArray(o) ? [
    m(
      o[0],
      Mt(null, +o[0].hours, +o[0].minutes, o[0].seconds)
    ),
    m(
      o[1],
      Mt(null, +o[1].hours, +o[1].minutes, o[1].seconds)
    )
  ] : m(o, Mt(null, o.hours, o.minutes, o.seconds)), L = (o) => {
    const X = set(H(), { date: 1 });
    return Array.isArray(o) ? d.value.enabled ? o.map((B) => m(B, pt(X, +B.month, +B.year))) : oa(
      () => [
        m(o[0], pt(X, +o[0].month, +o[0].year)),
        m(
          o[1],
          o[1] ? pt(X, +o[1].month, +o[1].year) : It(i.value.partialRange)
        )
      ],
      i.value.enabled
    ) : m(o, pt(X, +o.month, +o.year));
  }, le = (o) => {
    if (Array.isArray(o))
      return o.map((X) => q(X));
    throw new Error(Xa.dateArr("multi-dates"));
  }, U = (o) => {
    if (Array.isArray(o) && i.value.enabled) {
      const X = o[0], B = o[1];
      return [
        H(Array.isArray(X) ? X[0] : null),
        Array.isArray(B) && B.length ? H(B[0]) : null
      ];
    }
    return H(o[0]);
  }, $ = (o) => t2.modelAuto ? Array.isArray(o) ? [q(o[0]), q(o[1])] : t2.autoApply ? [q(o)] : [q(o), null] : Array.isArray(o) ? oa(
    () => o[1] ? [
      q(o[0]),
      o[1] ? q(o[1]) : It(i.value.partialRange)
    ] : [q(o[0])],
    i.value.enabled
  ) : q(o), ee = () => {
    Array.isArray(a.value) && i.value.enabled && a.value.length === 1 && a.value.push(It(i.value.partialRange));
  }, O = () => {
    const o = a.value;
    return [
      k(o[0]),
      o[1] ? k(o[1]) : It(i.value.partialRange)
    ];
  }, J = () => Array.isArray(a.value) ? a.value[1] ? O() : k(Ne(a.value[0])) : [], ce = () => (a.value || []).map((o) => k(o)), pe = (o = false) => (o || ee(), t2.modelAuto ? J() : d.value.enabled ? ce() : Array.isArray(a.value) ? oa(() => O(), i.value.enabled) : k(Ne(a.value))), p = (o) => !o || Array.isArray(o) && !o.length ? null : t2.timePicker ? z(Ne(o)) : t2.monthPicker ? L(Ne(o)) : t2.yearPicker ? A(Ne(o)) : d.value.enabled ? le(Ne(o)) : t2.weekPicker ? U(Ne(o)) : $(Ne(o)), Y = (o) => {
    if (n.value) return;
    const X = p(o);
    za(Ne(X)) ? (a.value = Ne(X), S()) : (a.value = null, v.value = "");
  }, te = () => {
    const o = (X) => format(X, f.value.format);
    return `${o(a.value[0])} ${f.value.rangeSeparator} ${a.value[1] ? o(a.value[1]) : ""}`;
  }, y = () => l.value && a.value ? Array.isArray(a.value) ? te() : format(a.value, f.value.format) : _(a.value), V = () => a.value ? d.value.enabled ? a.value.map((o) => _(o)).join("; ") : f.value.enabled && typeof f.value.format == "string" ? y() : _(a.value) : "", S = () => {
    !t2.format || typeof t2.format == "string" || f.value.enabled && typeof f.value.format == "string" ? v.value = V() : v.value = t2.format(a.value);
  }, q = (o) => {
    if (t2.utc) {
      const X = new Date(o);
      return t2.utc === "preserve" ? new Date(X.getTime() + X.getTimezoneOffset() * 6e4) : X;
    }
    return t2.modelType ? $l.includes(t2.modelType) ? T(new Date(o)) : t2.modelType === "format" && (typeof t2.format == "string" || !t2.format) ? T(
      parse(o, P(), /* @__PURE__ */ new Date(), { locale: h2.value })
    ) : T(
      parse(o, t2.modelType, /* @__PURE__ */ new Date(), { locale: h2.value })
    ) : T(new Date(o));
  }, k = (o) => o ? t2.utc ? Il(o, t2.utc === "preserve", t2.enableSeconds) : t2.modelType ? t2.modelType === "timestamp" ? +F(o) : t2.modelType === "iso" ? F(o).toISOString() : t2.modelType === "format" && (typeof t2.format == "string" || !t2.format) ? _(F(o)) : _(F(o), t2.modelType, true) : F(o) : "", se = (o, X = false, B = false) => {
    if (B) return o;
    if (e("update:model-value", o), g.value.emitTimezone && X) {
      const be = Array.isArray(o) ? o.map((Ae) => Ze(Ne(Ae), g.value.emitTimezone)) : Ze(Ne(o), g.value.emitTimezone);
      e("update:model-timezone-value", be);
    }
  }, u = (o) => Array.isArray(a.value) ? d.value.enabled ? a.value.map((X) => o(X)) : [
    o(a.value[0]),
    a.value[1] ? o(a.value[1]) : It(i.value.partialRange)
  ] : o(Ne(a.value)), re = () => {
    if (Array.isArray(a.value)) {
      const o = mt(a.value[0], t2.weekStart), X = a.value[1] ? mt(a.value[1], t2.weekStart) : [];
      return [o.map((B) => H(B)), X.map((B) => H(B))];
    }
    return mt(a.value, t2.weekStart).map((o) => H(o));
  }, G = (o, X) => se(Ne(u(o)), false, X), I = (o) => {
    const X = re();
    return o ? X : e("update:model-value", re());
  }, x = (o = false) => (o || S(), t2.monthPicker ? G(M, o) : t2.timePicker ? G(C, o) : t2.yearPicker ? G(getYear, o) : t2.weekPicker ? I(o) : se(pe(o), true, o));
  return {
    inputValue: v,
    internalModelValue: a,
    checkBeforeEmit: () => a.value ? i.value.enabled ? i.value.partialRange ? a.value.length >= 1 : a.value.length === 2 : !!a.value : false,
    parseExternalModelValue: Y,
    formatInputValue: S,
    emitModelValue: x
  };
}, or = (e, t2) => {
  const { defaultedFilters: l, propDates: n } = Oe(e), { validateMonthYearInRange: a } = Tt(e), f = (v, R) => {
    let h2 = v;
    return l.value.months.includes(getMonth(h2)) ? (h2 = R ? addMonths(v, 1) : subMonths(v, 1), f(h2, R)) : h2;
  }, i = (v, R) => {
    let h2 = v;
    return l.value.years.includes(getYear(h2)) ? (h2 = R ? addYears(v, 1) : subYears(v, 1), i(h2, R)) : h2;
  }, g = (v, R = false) => {
    const h2 = set(H(), { month: e.month, year: e.year });
    let T = v ? addMonths(h2, 1) : subMonths(h2, 1);
    e.disableYearSelect && (T = setYear(T, e.year));
    let F = getMonth(T), _ = getYear(T);
    l.value.months.includes(F) && (T = f(T, v), F = getMonth(T), _ = getYear(T)), l.value.years.includes(_) && (T = i(T, v), _ = getYear(T)), a(F, _, v, e.preventMinMaxNavigation) && d(F, _, R);
  }, d = (v, R, h2) => {
    t2("update-month-year", { month: v, year: R, fromNav: h2 });
  }, P = computed(() => (v) => Nn(
    set(H(), { month: e.month, year: e.year }),
    n.value.maxDate,
    n.value.minDate,
    e.preventMinMaxNavigation,
    v
  ));
  return { handleMonthYearChange: g, isDisabled: P, updateMonthYear: d };
}, va = {
  multiCalendars: { type: [Boolean, Number, String, Object], default: void 0 },
  modelValue: { type: [String, Date, Array, Object, Number], default: null },
  modelType: { type: String, default: null },
  position: { type: String, default: "center" },
  dark: { type: Boolean, default: false },
  format: {
    type: [String, Function],
    default: () => null
  },
  autoPosition: { type: [Boolean, String], default: true },
  altPosition: { type: Function, default: null },
  transitions: { type: [Boolean, Object], default: true },
  formatLocale: { type: Object, default: null },
  utc: { type: [Boolean, String], default: false },
  ariaLabels: { type: Object, default: () => ({}) },
  offset: { type: [Number, String], default: 10 },
  hideNavigation: { type: Array, default: () => [] },
  timezone: { type: [String, Object], default: null },
  vertical: { type: Boolean, default: false },
  disableMonthYearSelect: { type: Boolean, default: false },
  disableYearSelect: { type: Boolean, default: false },
  dayClass: {
    type: Function,
    default: null
  },
  yearRange: { type: Array, default: () => [1900, 2100] },
  enableTimePicker: { type: Boolean, default: true },
  autoApply: { type: Boolean, default: false },
  disabledDates: { type: [Array, Function], default: () => [] },
  monthNameFormat: { type: String, default: "short" },
  startDate: { type: [Date, String], default: null },
  startTime: { type: [Object, Array], default: null },
  hideOffsetDates: { type: Boolean, default: false },
  noToday: { type: Boolean, default: false },
  disabledWeekDays: { type: Array, default: () => [] },
  allowedDates: { type: Array, default: null },
  nowButtonLabel: { type: String, default: "Now" },
  markers: { type: Array, default: () => [] },
  escClose: { type: Boolean, default: true },
  spaceConfirm: { type: Boolean, default: true },
  monthChangeOnArrows: { type: Boolean, default: true },
  presetDates: { type: Array, default: () => [] },
  flow: { type: Array, default: () => [] },
  partialFlow: { type: Boolean, default: false },
  preventMinMaxNavigation: { type: Boolean, default: false },
  reverseYears: { type: Boolean, default: false },
  weekPicker: { type: Boolean, default: false },
  filters: { type: Object, default: () => ({}) },
  arrowNavigation: { type: Boolean, default: false },
  highlight: {
    type: [Function, Object],
    default: null
  },
  teleport: { type: [Boolean, String, Object], default: null },
  teleportCenter: { type: Boolean, default: false },
  locale: { type: String, default: "en-Us" },
  weekNumName: { type: String, default: "W" },
  weekStart: { type: [Number, String], default: 1 },
  weekNumbers: {
    type: [String, Function, Object],
    default: null
  },
  monthChangeOnScroll: { type: [Boolean, String], default: true },
  dayNames: {
    type: [Function, Array],
    default: null
  },
  monthPicker: { type: Boolean, default: false },
  customProps: { type: Object, default: null },
  yearPicker: { type: Boolean, default: false },
  modelAuto: { type: Boolean, default: false },
  selectText: { type: String, default: "Select" },
  cancelText: { type: String, default: "Cancel" },
  previewFormat: {
    type: [String, Function],
    default: () => ""
  },
  multiDates: { type: [Object, Boolean], default: false },
  ignoreTimeValidation: { type: Boolean, default: false },
  minDate: { type: [Date, String], default: null },
  maxDate: { type: [Date, String], default: null },
  minTime: { type: Object, default: null },
  maxTime: { type: Object, default: null },
  name: { type: String, default: null },
  placeholder: { type: String, default: "" },
  hideInputIcon: { type: Boolean, default: false },
  clearable: { type: Boolean, default: true },
  alwaysClearable: { type: Boolean, default: false },
  state: { type: Boolean, default: null },
  required: { type: Boolean, default: false },
  autocomplete: { type: String, default: "off" },
  timePicker: { type: Boolean, default: false },
  enableSeconds: { type: Boolean, default: false },
  is24: { type: Boolean, default: true },
  noHoursOverlay: { type: Boolean, default: false },
  noMinutesOverlay: { type: Boolean, default: false },
  noSecondsOverlay: { type: Boolean, default: false },
  hoursGridIncrement: { type: [String, Number], default: 1 },
  minutesGridIncrement: { type: [String, Number], default: 5 },
  secondsGridIncrement: { type: [String, Number], default: 5 },
  hoursIncrement: { type: [Number, String], default: 1 },
  minutesIncrement: { type: [Number, String], default: 1 },
  secondsIncrement: { type: [Number, String], default: 1 },
  range: { type: [Boolean, Object], default: false },
  uid: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  inline: { type: [Boolean, Object], default: false },
  textInput: { type: [Boolean, Object], default: false },
  sixWeeks: { type: [Boolean, String], default: false },
  actionRow: { type: Object, default: () => ({}) },
  focusStartDate: { type: Boolean, default: false },
  disabledTimes: { type: [Function, Array], default: void 0 },
  timePickerInline: { type: Boolean, default: false },
  calendar: { type: Function, default: null },
  config: { type: Object, default: void 0 },
  quarterPicker: { type: Boolean, default: false },
  yearFirst: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  onInternalModelChange: { type: [Function, Object], default: null },
  enableMinutes: { type: Boolean, default: true },
  ui: { type: Object, default: () => ({}) }
}, dt = {
  ...va,
  shadow: { type: Boolean, default: false },
  flowStep: { type: Number, default: 0 },
  internalModelValue: { type: [Date, Array], default: null },
  noOverlayFocus: { type: Boolean, default: false },
  collapse: { type: Boolean, default: false },
  menuWrapRef: { type: Object, default: null },
  getInputRect: { type: Function, default: () => ({}) },
  isTextInputDate: { type: Boolean, default: false },
  isMobile: { type: Boolean, default: void 0 }
}, sr = ["title"], ur = ["disabled"], ir = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "ActionRow",
  props: {
    menuMount: { type: Boolean, default: false },
    calendarWidth: { type: Number, default: 0 },
    ...dt
  },
  emits: ["close-picker", "select-date", "select-now", "invalid-select"],
  setup(e, { emit: t2 }) {
    const l = t2, n = e, {
      defaultedActionRow: a,
      defaultedPreviewFormat: f,
      defaultedMultiCalendars: i,
      defaultedTextInput: g,
      defaultedInline: d,
      defaultedRange: P,
      defaultedMultiDates: v
    } = Oe(n), { isTimeValid: R, isMonthValid: h2 } = Tt(n), { buildMatrix: T } = At(), F = ref(null), _ = ref(null), C = ref(false), M = ref({}), A = ref(null), m = ref(null);
    onMounted(() => {
      n.arrowNavigation && T([Le(F), Le(_)], "actionRow"), z(), window.addEventListener("resize", z);
    }), onUnmounted(() => {
      window.removeEventListener("resize", z);
    });
    const z = () => {
      C.value = false, setTimeout(() => {
        const p = A.value?.getBoundingClientRect(), Y = m.value?.getBoundingClientRect();
        p && Y && (M.value.maxWidth = `${Y.width - p.width - 20}px`), C.value = true;
      }, 0);
    }, L = computed(() => P.value.enabled && !P.value.partialRange && n.internalModelValue ? n.internalModelValue.length === 2 : true), le = computed(
      () => !R.value(n.internalModelValue) || !h2.value(n.internalModelValue) || !L.value
    ), U = () => {
      const p = f.value;
      return n.timePicker || n.monthPicker, p(Ne(n.internalModelValue));
    }, $ = () => {
      const p = n.internalModelValue;
      return i.value.count > 0 ? `${ee(p[0])} - ${ee(p[1])}` : [ee(p[0]), ee(p[1])];
    }, ee = (p) => Ln(
      p,
      f.value,
      n.formatLocale,
      g.value.rangeSeparator,
      n.modelAuto,
      f.value
    ), O = computed(() => !n.internalModelValue || !n.menuMount ? "" : typeof f.value == "string" ? Array.isArray(n.internalModelValue) ? n.internalModelValue.length === 2 && n.internalModelValue[1] ? $() : v.value.enabled ? n.internalModelValue.map((p) => `${ee(p)}`) : n.modelAuto ? `${ee(n.internalModelValue[0])}` : `${ee(n.internalModelValue[0])} -` : ee(n.internalModelValue) : U()), J = () => v.value.enabled ? "; " : " - ", ce = computed(
      () => Array.isArray(O.value) ? O.value.join(J()) : O.value
    ), pe = () => {
      R.value(n.internalModelValue) && h2.value(n.internalModelValue) && L.value ? l("select-date") : l("invalid-select");
    };
    return (p, Y) => (openBlock(), createElementBlock("div", {
      ref_key: "actionRowRef",
      ref: m,
      class: "dp__action_row"
    }, [
      p.$slots["action-row"] ? renderSlot(p.$slots, "action-row", normalizeProps(mergeProps({ key: 0 }, {
        internalModelValue: p.internalModelValue,
        disabled: le.value,
        selectDate: () => p.$emit("select-date"),
        closePicker: () => p.$emit("close-picker")
      }))) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
        unref(a).showPreview ? (openBlock(), createElementBlock("div", {
          key: 0,
          class: "dp__selection_preview",
          title: ce.value,
          style: normalizeStyle(M.value)
        }, [
          p.$slots["action-preview"] && C.value ? renderSlot(p.$slots, "action-preview", {
            key: 0,
            value: p.internalModelValue
          }) : createCommentVNode("", true),
          !p.$slots["action-preview"] && C.value ? (openBlock(), createElementBlock(Fragment, { key: 1 }, [
            createTextVNode(toDisplayString(ce.value), 1)
          ], 64)) : createCommentVNode("", true)
        ], 12, sr)) : createCommentVNode("", true),
        createBaseVNode("div", {
          ref_key: "actionBtnContainer",
          ref: A,
          class: "dp__action_buttons",
          "data-dp-element": "action-row"
        }, [
          p.$slots["action-buttons"] ? renderSlot(p.$slots, "action-buttons", {
            key: 0,
            value: p.internalModelValue
          }) : createCommentVNode("", true),
          p.$slots["action-buttons"] ? createCommentVNode("", true) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
            !unref(d).enabled && unref(a).showCancel ? (openBlock(), createElementBlock("button", {
              key: 0,
              ref_key: "cancelButtonRef",
              ref: F,
              type: "button",
              class: "dp__action_button dp__action_cancel",
              onClick: Y[0] || (Y[0] = (te) => p.$emit("close-picker")),
              onKeydown: Y[1] || (Y[1] = (te) => unref(qe)(te, () => p.$emit("close-picker")))
            }, toDisplayString(p.cancelText), 545)) : createCommentVNode("", true),
            unref(a).showNow ? (openBlock(), createElementBlock("button", {
              key: 1,
              type: "button",
              class: "dp__action_button dp__action_cancel",
              onClick: Y[2] || (Y[2] = (te) => p.$emit("select-now")),
              onKeydown: Y[3] || (Y[3] = (te) => unref(qe)(te, () => p.$emit("select-now")))
            }, toDisplayString(p.nowButtonLabel), 33)) : createCommentVNode("", true),
            unref(a).showSelect ? (openBlock(), createElementBlock("button", {
              key: 2,
              ref_key: "selectButtonRef",
              ref: _,
              type: "button",
              class: "dp__action_button dp__action_select",
              disabled: le.value,
              "data-test-id": "select-button",
              onKeydown: Y[4] || (Y[4] = (te) => unref(qe)(te, () => pe())),
              onClick: pe
            }, toDisplayString(p.selectText), 41, ur)) : createCommentVNode("", true)
          ], 64))
        ], 512)
      ], 64))
    ], 512));
  }
}), dr = ["role", "aria-label", "tabindex"], cr = { class: "dp__selection_grid_header" }, fr = ["aria-selected", "aria-disabled", "data-test-id", "onClick", "onKeydown", "onMouseover"], vr = ["aria-label"], ta = /* @__PURE__ */ defineComponent({
  __name: "SelectionOverlay",
  props: {
    items: {},
    type: {},
    isLast: { type: Boolean },
    arrowNavigation: { type: Boolean },
    skipButtonRef: { type: Boolean },
    headerRefs: {},
    hideNavigation: {},
    escClose: { type: Boolean },
    useRelative: { type: Boolean },
    height: {},
    textInput: { type: [Boolean, Object] },
    config: {},
    noOverlayFocus: { type: Boolean },
    focusValue: {},
    menuWrapRef: {},
    ariaLabels: {},
    overlayLabel: {}
  },
  emits: ["selected", "toggle", "reset-flow", "hover-value"],
  setup(e, { expose: t2, emit: l }) {
    const { setSelectionGrid: n, buildMultiLevelMatrix: a, setMonthPicker: f } = At(), i = l, g = e, { defaultedAriaLabels: d, defaultedTextInput: P, defaultedConfig: v, handleEventPropagation: R } = Oe(
      g
    ), { hideNavigationButtons: h2 } = ga(), T = ref(false), F = ref(null), _ = ref(null), C = ref([]), M = ref(), A = ref(null), m = ref(0), z = ref(null);
    onBeforeUpdate(() => {
      F.value = null;
    }), onMounted(() => {
      nextTick().then(() => ce()), g.noOverlayFocus || le(), L(true);
    }), onUnmounted(() => L(false));
    const L = (u) => {
      g.arrowNavigation && (g.headerRefs?.length ? f(u) : n(u));
    }, le = () => {
      const u = Le(_);
      u && (P.value.enabled || (F.value ? F.value?.focus({ preventScroll: true }) : u.focus({ preventScroll: true })), T.value = u.clientHeight < u.scrollHeight);
    }, U = computed(
      () => ({
        dp__overlay: true,
        "dp--overlay-absolute": !g.useRelative,
        "dp--overlay-relative": g.useRelative
      })
    ), $ = computed(
      () => g.useRelative ? { height: `${g.height}px`, width: "var(--dp-menu-min-width)" } : void 0
    ), ee = computed(() => ({
      dp__overlay_col: true
    })), O = computed(
      () => ({
        dp__btn: true,
        dp__button: true,
        dp__overlay_action: true,
        dp__over_action_scroll: T.value,
        dp__button_bottom: g.isLast
      })
    ), J = computed(() => ({
      dp__overlay_container: true,
      dp__container_flex: g.items?.length <= 6,
      dp__container_block: g.items?.length > 6
    }));
    watch(
      () => g.items,
      () => ce(false),
      { deep: true }
    );
    const ce = (u = true) => {
      nextTick().then(() => {
        const re = Le(F), G = Le(_), I = Le(A), x = Le(z), c = I ? I.getBoundingClientRect().height : 0;
        G && (G.getBoundingClientRect().height ? m.value = G.getBoundingClientRect().height - c : m.value = v.value.modeHeight - c), re && x && u && (x.scrollTop = re.offsetTop - x.offsetTop - (m.value / 2 - re.getBoundingClientRect().height) - c);
      });
    }, pe = (u) => {
      u.disabled || i("selected", u.value);
    }, p = () => {
      i("toggle"), i("reset-flow");
    }, Y = (u) => {
      g.escClose && (p(), R(u));
    }, te = (u, re, G, I) => {
      u && ((re.active || re.value === g.focusValue) && (F.value = u), g.arrowNavigation && (Array.isArray(C.value[G]) ? C.value[G][I] = u : C.value[G] = [u], y()));
    }, y = () => {
      const u = g.headerRefs?.length ? [g.headerRefs].concat(C.value) : C.value.concat([g.skipButtonRef ? [] : [A.value]]);
      a(Ne(u), g.headerRefs?.length ? "monthPicker" : "selectionGrid");
    }, V = (u) => {
      g.arrowNavigation || Dt(u, v.value, true);
    }, S = (u) => {
      M.value = u, i("hover-value", u);
    }, q = () => {
      if (p(), !g.isLast) {
        const u = Fa(g.menuWrapRef ?? null, "action-row");
        u && Cn(u)?.focus();
      }
    }, k = (u) => {
      switch (u.key) {
        case Re.esc:
          return Y(u);
        case Re.arrowLeft:
          return V(u);
        case Re.arrowRight:
          return V(u);
        case Re.arrowUp:
          return V(u);
        case Re.arrowDown:
          return V(u);
        default:
          return;
      }
    }, se = (u) => {
      if (u.key === Re.enter) return p();
      if (u.key === Re.tab) return q();
    };
    return t2({ focusGrid: le }), (u, re) => (openBlock(), createElementBlock("div", {
      ref_key: "gridWrapRef",
      ref: _,
      class: normalizeClass(U.value),
      style: normalizeStyle($.value),
      role: e.useRelative ? void 0 : "dialog",
      "aria-label": e.overlayLabel,
      tabindex: e.useRelative ? void 0 : "0",
      onKeydown: k,
      onClick: re[0] || (re[0] = withModifiers(() => {
      }, ["prevent"]))
    }, [
      createBaseVNode("div", {
        ref_key: "containerRef",
        ref: z,
        class: normalizeClass(J.value),
        style: normalizeStyle({ "--dp-overlay-height": `${m.value}px` }),
        role: "grid"
      }, [
        createBaseVNode("div", cr, [
          renderSlot(u.$slots, "header")
        ]),
        u.$slots.overlay ? renderSlot(u.$slots, "overlay", { key: 0 }) : (openBlock(true), createElementBlock(Fragment, { key: 1 }, renderList(e.items, (G, I) => (openBlock(), createElementBlock("div", {
          key: I,
          class: normalizeClass(["dp__overlay_row", { dp__flex_row: e.items.length >= 3 }]),
          role: "row"
        }, [
          (openBlock(true), createElementBlock(Fragment, null, renderList(G, (x, c) => (openBlock(), createElementBlock("div", {
            key: x.value,
            ref_for: true,
            ref: (o) => te(o, x, I, c),
            role: "gridcell",
            class: normalizeClass(ee.value),
            "aria-selected": x.active || void 0,
            "aria-disabled": x.disabled || void 0,
            tabindex: "0",
            "data-test-id": x.text,
            onClick: withModifiers((o) => pe(x), ["prevent"]),
            onKeydown: (o) => unref(qe)(o, () => pe(x), true),
            onMouseover: (o) => S(x.value)
          }, [
            createBaseVNode("div", {
              class: normalizeClass(x.className)
            }, [
              u.$slots.item ? renderSlot(u.$slots, "item", {
                key: 0,
                item: x
              }) : createCommentVNode("", true),
              u.$slots.item ? createCommentVNode("", true) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
                createTextVNode(toDisplayString(x.text), 1)
              ], 64))
            ], 2)
          ], 42, fr))), 128))
        ], 2))), 128))
      ], 6),
      u.$slots["button-icon"] ? withDirectives((openBlock(), createElementBlock("button", {
        key: 0,
        ref_key: "toggleButton",
        ref: A,
        type: "button",
        "aria-label": unref(d)?.toggleOverlay,
        class: normalizeClass(O.value),
        tabindex: "0",
        onClick: p,
        onKeydown: se
      }, [
        renderSlot(u.$slots, "button-icon")
      ], 42, vr)), [
        [vShow, !unref(h2)(e.hideNavigation, e.type)]
      ]) : createCommentVNode("", true)
    ], 46, dr));
  }
}), mr = ["data-dp-mobile"], ma = /* @__PURE__ */ defineComponent({
  __name: "InstanceWrap",
  props: {
    multiCalendars: {},
    stretch: { type: Boolean },
    collapse: { type: Boolean },
    isMobile: { type: Boolean }
  },
  setup(e) {
    const t2 = e, l = computed(
      () => t2.multiCalendars > 0 ? [...Array(t2.multiCalendars).keys()] : [0]
    ), n = computed(() => ({
      dp__instance_calendar: t2.multiCalendars > 0
    }));
    return (a, f) => (openBlock(), createElementBlock("div", {
      class: normalizeClass({
        dp__menu_inner: !e.stretch,
        "dp--menu--inner-stretched": e.stretch,
        dp__flex_display: e.multiCalendars > 0,
        "dp--flex-display-collapsed": e.collapse
      }),
      "data-dp-mobile": e.isMobile
    }, [
      (openBlock(true), createElementBlock(Fragment, null, renderList(l.value, (i, g) => (openBlock(), createElementBlock("div", {
        key: i,
        class: normalizeClass(n.value)
      }, [
        renderSlot(a.$slots, "default", {
          instance: i,
          index: g
        })
      ], 2))), 128))
    ], 10, mr));
  }
}), pr = ["data-dp-element", "aria-label", "aria-disabled"], Qt = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "ArrowBtn",
  props: {
    ariaLabel: {},
    elName: {},
    disabled: { type: Boolean }
  },
  emits: ["activate", "set-ref"],
  setup(e, { emit: t2 }) {
    const l = t2, n = ref(null);
    return onMounted(() => l("set-ref", n)), (a, f) => (openBlock(), createElementBlock("button", {
      ref_key: "elRef",
      ref: n,
      type: "button",
      "data-dp-element": e.elName,
      class: "dp__btn dp--arrow-btn-nav",
      tabindex: "0",
      "aria-label": e.ariaLabel,
      "aria-disabled": e.disabled || void 0,
      onClick: f[0] || (f[0] = (i) => l("activate")),
      onKeydown: f[1] || (f[1] = (i) => unref(qe)(i, () => l("activate"), true))
    }, [
      createBaseVNode("span", {
        class: normalizeClass(["dp__inner_nav", { dp__inner_nav_disabled: e.disabled }])
      }, [
        renderSlot(a.$slots, "default")
      ], 2)
    ], 40, pr));
  }
}), gr = ["aria-label", "data-test-id"], Wn = /* @__PURE__ */ defineComponent({
  __name: "YearModePicker",
  props: {
    ...dt,
    showYearPicker: { type: Boolean, default: false },
    items: { type: Array, default: () => [] },
    instance: { type: Number, default: 0 },
    year: { type: Number, default: 0 },
    isDisabled: { type: Function, default: () => false }
  },
  emits: ["toggle-year-picker", "year-select", "handle-year"],
  setup(e, { emit: t2 }) {
    const l = t2, n = e, { showRightIcon: a, showLeftIcon: f } = ga(), { defaultedConfig: i, defaultedMultiCalendars: g, defaultedAriaLabels: d, defaultedTransitions: P, defaultedUI: v } = Oe(n), { showTransition: R, transitionName: h2 } = aa(P), T = ref(false), F = computed(() => Ja(n.year, n.locale)), _ = (A = false, m) => {
      T.value = !T.value, l("toggle-year-picker", { flow: A, show: m });
    }, C = (A) => {
      T.value = false, l("year-select", A);
    }, M = (A = false) => {
      l("handle-year", A);
    };
    return (A, m) => (openBlock(), createElementBlock(Fragment, null, [
      createBaseVNode("div", {
        class: normalizeClass(["dp--year-mode-picker", { "dp--hidden-el": T.value }])
      }, [
        unref(f)(unref(g), e.instance) ? (openBlock(), createBlock(Qt, {
          key: 0,
          ref: "mpPrevIconRef",
          "aria-label": unref(d)?.prevYear,
          disabled: e.isDisabled(false),
          class: normalizeClass(unref(v)?.navBtnPrev),
          onActivate: m[0] || (m[0] = (z) => M(false))
        }, {
          default: withCtx(() => [
            A.$slots["arrow-left"] ? renderSlot(A.$slots, "arrow-left", { key: 0 }) : createCommentVNode("", true),
            A.$slots["arrow-left"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(Ua), { key: 1 }))
          ]),
          _: 3
        }, 8, ["aria-label", "disabled", "class"])) : createCommentVNode("", true),
        createBaseVNode("button", {
          ref: "mpYearButtonRef",
          class: "dp__btn dp--year-select",
          type: "button",
          "aria-label": `${e.year}-${unref(d)?.openYearsOverlay}`,
          "data-test-id": `year-mode-btn-${e.instance}`,
          onClick: m[1] || (m[1] = () => _(false)),
          onKeydown: m[2] || (m[2] = withKeys(() => _(false), ["enter"]))
        }, [
          A.$slots.year ? renderSlot(A.$slots, "year", {
            key: 0,
            year: e.year,
            text: F.value,
            value: e.year
          }) : createCommentVNode("", true),
          A.$slots.year ? createCommentVNode("", true) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
            createTextVNode(toDisplayString(F.value), 1)
          ], 64))
        ], 40, gr),
        unref(a)(unref(g), e.instance) ? (openBlock(), createBlock(Qt, {
          key: 1,
          ref: "mpNextIconRef",
          "aria-label": unref(d)?.nextYear,
          disabled: e.isDisabled(true),
          class: normalizeClass(unref(v)?.navBtnNext),
          onActivate: m[3] || (m[3] = (z) => M(true))
        }, {
          default: withCtx(() => [
            A.$slots["arrow-right"] ? renderSlot(A.$slots, "arrow-right", { key: 0 }) : createCommentVNode("", true),
            A.$slots["arrow-right"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(Va), { key: 1 }))
          ]),
          _: 3
        }, 8, ["aria-label", "disabled", "class"])) : createCommentVNode("", true)
      ], 2),
      createVNode(Transition, {
        name: unref(h2)(e.showYearPicker),
        css: unref(R)
      }, {
        default: withCtx(() => [
          e.showYearPicker ? (openBlock(), createBlock(ta, {
            key: 0,
            items: e.items,
            "text-input": A.textInput,
            "esc-close": A.escClose,
            config: A.config,
            "is-last": A.autoApply && !unref(i).keepActionRow,
            "hide-navigation": A.hideNavigation,
            "aria-labels": A.ariaLabels,
            "overlay-label": unref(d)?.yearPicker?.(true),
            type: "year",
            onToggle: _,
            onSelected: m[4] || (m[4] = (z) => C(z))
          }, createSlots({
            "button-icon": withCtx(() => [
              A.$slots["calendar-icon"] ? renderSlot(A.$slots, "calendar-icon", { key: 0 }) : createCommentVNode("", true),
              A.$slots["calendar-icon"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(jt), { key: 1 }))
            ]),
            _: 2
          }, [
            A.$slots["year-overlay-value"] ? {
              name: "item",
              fn: withCtx(({ item: z }) => [
                renderSlot(A.$slots, "year-overlay-value", {
                  text: z.text,
                  value: z.value
                })
              ]),
              key: "0"
            } : void 0
          ]), 1032, ["items", "text-input", "esc-close", "config", "is-last", "hide-navigation", "aria-labels", "overlay-label"])) : createCommentVNode("", true)
        ]),
        _: 3
      }, 8, ["name", "css"])
    ], 64));
  }
}), en = (e, t2, l) => {
  if (t2.value && Array.isArray(t2.value))
    if (t2.value.some((n) => $e(e, n))) {
      const n = t2.value.filter((a) => !$e(a, e));
      t2.value = n.length ? n : null;
    } else (l && +l > t2.value.length || !l) && t2.value.push(e);
  else
    t2.value = [e];
}, tn = (e, t2, l) => {
  let n = e.value ? e.value.slice() : [];
  return n.length === 2 && n[1] !== null && (n = []), n.length ? (Be(t2, n[0]) ? n.unshift(t2) : n[1] = t2, l("range-end", t2)) : (n = [t2], l("range-start", t2)), n;
}, pa = (e, t2, l, n) => {
  e && (e[0] && e[1] && l && t2("auto-apply"), e[0] && !e[1] && n && l && t2("auto-apply"));
}, Un = (e) => {
  Array.isArray(e.value) && e.value.length <= 2 && e.range ? e.modelValue.value = e.value.map((t2) => Ze(H(t2), e.timezone)) : Array.isArray(e.value) || (e.modelValue.value = Ze(H(e.value), e.timezone));
}, Vn = (e, t2, l, n) => Array.isArray(t2.value) && (t2.value.length === 2 || t2.value.length === 1 && n.value.partialRange) ? n.value.fixedStart && (Ee(e, t2.value[0]) || $e(e, t2.value[0])) ? [t2.value[0], e] : n.value.fixedEnd && (Be(e, t2.value[1]) || $e(e, t2.value[1])) ? [e, t2.value[1]] : (l("invalid-fixed-range", e), t2.value) : [], jn = ({
  multiCalendars: e,
  range: t2,
  highlight: l,
  propDates: n,
  calendars: a,
  modelValue: f,
  props: i,
  filters: g,
  year: d,
  month: P,
  emit: v
}) => {
  const R = computed(() => qa(i.yearRange, i.locale, i.reverseYears)), h2 = ref([false]), T = computed(() => (O, J) => {
    const ce = set(it(/* @__PURE__ */ new Date()), {
      month: P.value(O),
      year: d.value(O)
    }), pe = J ? endOfYear(ce) : startOfYear(ce);
    return Nn(
      pe,
      n.value.maxDate,
      n.value.minDate,
      i.preventMinMaxNavigation,
      J
    );
  }), F = () => Array.isArray(f.value) && e.value.solo && f.value[1], _ = () => {
    for (let O = 0; O < e.value.count; O++)
      if (O === 0)
        a.value[O] = a.value[0];
      else if (O === e.value.count - 1 && F())
        a.value[O] = {
          month: getMonth(f.value[1]),
          year: getYear(f.value[1])
        };
      else {
        const J = set(H(), a.value[O - 1]);
        a.value[O] = { month: getMonth(J), year: getYear(addYears(J, 1)) };
      }
  }, C = (O) => {
    if (!O) return _();
    const J = set(H(), a.value[O]);
    return a.value[0].year = getYear(subYears(J, e.value.count - 1)), _();
  }, M = (O, J) => {
    const ce = differenceInYears(J, O);
    return t2.value.showLastInRange && ce > 1 ? J : O;
  }, A = (O) => i.focusStartDate || e.value.solo ? O[0] : O[1] ? M(O[0], O[1]) : O[0], m = () => {
    if (f.value) {
      const O = Array.isArray(f.value) ? A(f.value) : f.value;
      a.value[0] = { month: getMonth(O), year: getYear(O) };
    }
  }, z = () => {
    m(), e.value.count && _();
  };
  watch(f, (O, J) => {
    i.isTextInputDate && JSON.stringify(O ?? {}) !== JSON.stringify(J ?? {}) && z();
  }), onMounted(() => {
    z();
  });
  const L = (O, J) => {
    a.value[J].year = O, v("update-month-year", { instance: J, year: O, month: a.value[J].month }), e.value.count && !e.value.solo && C(J);
  }, le = computed(() => (O) => Ht(R.value, (J) => {
    const ce = d.value(O) === J.value, pe = Zt(
      J.value,
      Wt(n.value.minDate),
      Wt(n.value.maxDate)
    ) || g.value.years?.includes(d.value(O)), p = xa(l.value, J.value);
    return { active: ce, disabled: pe, highlighted: p };
  })), U = (O, J) => {
    L(O, J), ee(J);
  }, $ = (O, J = false) => {
    if (!T.value(O, J)) {
      const ce = J ? d.value(O) + 1 : d.value(O) - 1;
      L(ce, O);
    }
  }, ee = (O, J = false, ce) => {
    J || v("reset-flow"), ce !== void 0 ? h2.value[O] = ce : h2.value[O] = !h2.value[O], h2.value[O] ? v("overlay-toggle", { open: true, overlay: je.year }) : (v("overlay-closed"), v("overlay-toggle", { open: false, overlay: je.year }));
  };
  return {
    isDisabled: T,
    groupedYears: le,
    showYearPicker: h2,
    selectYear: L,
    toggleYearPicker: ee,
    handleYearSelect: U,
    handleYear: $
  };
}, yr = (e, t2) => {
  const {
    defaultedMultiCalendars: l,
    defaultedAriaLabels: n,
    defaultedTransitions: a,
    defaultedConfig: f,
    defaultedRange: i,
    defaultedHighlight: g,
    propDates: d,
    defaultedTz: P,
    defaultedFilters: v,
    defaultedMultiDates: R
  } = Oe(e), h2 = () => {
    e.isTextInputDate && z(getYear(H(e.startDate)), 0);
  }, { modelValue: T, year: F, month: _, calendars: C } = na(e, t2, h2), M = computed(() => Pn(e.formatLocale, e.locale, e.monthNameFormat)), A = ref(null), { checkMinMaxRange: m } = Tt(e), {
    selectYear: z,
    groupedYears: L,
    showYearPicker: le,
    toggleYearPicker: U,
    handleYearSelect: $,
    handleYear: ee,
    isDisabled: O
  } = jn({
    modelValue: T,
    multiCalendars: l,
    range: i,
    highlight: g,
    calendars: C,
    year: F,
    propDates: d,
    month: _,
    filters: v,
    props: e,
    emit: t2
  });
  onMounted(() => {
    e.startDate && (T.value && e.focusStartDate || !T.value) && z(getYear(H(e.startDate)), 0);
  });
  const J = (I) => I ? { month: getMonth(I), year: getYear(I) } : { month: null, year: null }, ce = () => T.value ? Array.isArray(T.value) ? T.value.map((I) => J(I)) : J(T.value) : J(), pe = (I, x) => {
    const c = C.value[I], o = ce();
    return Array.isArray(o) ? o.some((X) => X.year === c?.year && X.month === x) : c?.year === o.year && x === o.month;
  }, p = (I, x, c) => {
    const o = ce();
    return Array.isArray(o) ? F.value(x) === o[c]?.year && I === o[c]?.month : false;
  }, Y = (I, x) => {
    if (i.value.enabled) {
      const c = ce();
      if (Array.isArray(T.value) && Array.isArray(c)) {
        const o = p(I, x, 0) || p(I, x, 1), X = pt(it(H()), I, F.value(x));
        return xt(T.value, A.value, X) && !o;
      }
      return false;
    }
    return false;
  }, te = computed(() => (I) => Ht(M.value, (x) => {
    const c = pe(I, x.value), o = Zt(
      x.value,
      _n(F.value(I), d.value.minDate),
      Yn(F.value(I), d.value.maxDate)
    ) || zl(d.value.disabledDates, F.value(I), x.value) || v.value.months?.includes(x.value) || !Hl(d.value.allowedDates, F.value(I), x.value) || zn(e.yearRange, F.value(I)), X = Y(x.value, I), B = Fn(g.value, x.value, F.value(I));
    return { active: c, disabled: o, isBetween: X, highlighted: B };
  })), y = (I, x) => pt(it(H()), I, F.value(x)), V = (I, x) => {
    const c = T.value ? T.value : it(/* @__PURE__ */ new Date());
    T.value = pt(c, I, F.value(x)), t2("auto-apply"), t2("update-flow-step");
  }, S = (I, x) => {
    const c = y(I, x);
    i.value.fixedEnd || i.value.fixedStart ? T.value = Vn(c, T, t2, i) : T.value ? m(c, T.value) && (T.value = tn(T, y(I, x), t2)) : T.value = [y(I, x)], nextTick().then(() => {
      pa(T.value, t2, e.autoApply, e.modelAuto);
    });
  }, q = (I, x) => {
    en(y(I, x), T, R.value.limit), t2("auto-apply", true);
  }, k = (I, x) => (C.value[x].month = I, u(x, C.value[x].year, I), R.value.enabled ? q(I, x) : i.value.enabled ? S(I, x) : V(I, x)), se = (I, x) => {
    z(I, x), u(x, I, null);
  }, u = (I, x, c) => {
    let o = c;
    if (!o && o !== 0) {
      const X = ce();
      o = Array.isArray(X) ? X[I].month : X.month;
    }
    t2("update-month-year", { instance: I, year: x, month: o });
  };
  return {
    groupedMonths: te,
    groupedYears: L,
    year: F,
    isDisabled: O,
    defaultedMultiCalendars: l,
    defaultedAriaLabels: n,
    defaultedTransitions: a,
    defaultedConfig: f,
    showYearPicker: le,
    modelValue: T,
    presetDate: (I, x) => {
      Un({
        value: I,
        modelValue: T,
        range: i.value.enabled,
        timezone: x ? void 0 : P.value.timezone
      }), t2("auto-apply");
    },
    setHoverDate: (I, x) => {
      A.value = y(I, x);
    },
    selectMonth: k,
    selectYear: se,
    toggleYearPicker: U,
    handleYearSelect: $,
    handleYear: ee,
    getModelMonthYear: ce
  };
}, hr = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "MonthPicker",
  props: {
    ...dt
  },
  emits: [
    "update:internal-model-value",
    "overlay-closed",
    "reset-flow",
    "range-start",
    "range-end",
    "auto-apply",
    "update-month-year",
    "update-flow-step",
    "mount",
    "invalid-fixed-range",
    "overlay-toggle"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = useSlots(), f = et(a, "yearMode"), i = e;
    onMounted(() => {
      i.shadow || n("mount", null);
    });
    const {
      groupedMonths: g,
      groupedYears: d,
      year: P,
      isDisabled: v,
      defaultedMultiCalendars: R,
      defaultedConfig: h2,
      showYearPicker: T,
      modelValue: F,
      presetDate: _,
      setHoverDate: C,
      selectMonth: M,
      selectYear: A,
      toggleYearPicker: m,
      handleYearSelect: z,
      handleYear: L,
      getModelMonthYear: le
    } = yr(i, n);
    return t2({ getSidebarProps: () => ({
      modelValue: F,
      year: P,
      getModelMonthYear: le,
      selectMonth: M,
      selectYear: A,
      handleYear: L
    }), presetDate: _, toggleYearPicker: ($) => m(0, $) }), ($, ee) => (openBlock(), createBlock(ma, {
      "multi-calendars": unref(R).count,
      collapse: $.collapse,
      stretch: "",
      "is-mobile": $.isMobile
    }, {
      default: withCtx(({ instance: O }) => [
        $.$slots["top-extra"] ? renderSlot($.$slots, "top-extra", {
          key: 0,
          value: $.internalModelValue
        }) : createCommentVNode("", true),
        $.$slots["month-year"] ? renderSlot($.$slots, "month-year", normalizeProps(mergeProps({ key: 1 }, {
          year: unref(P),
          months: unref(g)(O),
          years: unref(d)(O),
          selectMonth: unref(M),
          selectYear: unref(A),
          instance: O
        }))) : (openBlock(), createBlock(ta, {
          key: 2,
          items: unref(g)(O),
          "arrow-navigation": $.arrowNavigation,
          "is-last": $.autoApply && !unref(h2).keepActionRow,
          "esc-close": $.escClose,
          height: unref(h2).modeHeight,
          config: $.config,
          "no-overlay-focus": !!($.noOverlayFocus || $.textInput),
          "use-relative": "",
          type: "month",
          onSelected: (J) => unref(M)(J, O),
          onHoverValue: (J) => unref(C)(J, O)
        }, createSlots({
          header: withCtx(() => [
            createVNode(Wn, mergeProps($.$props, {
              items: unref(d)(O),
              instance: O,
              "show-year-picker": unref(T)[O],
              year: unref(P)(O),
              "is-disabled": (J) => unref(v)(O, J),
              onHandleYear: (J) => unref(L)(O, J),
              onYearSelect: (J) => unref(z)(J, O),
              onToggleYearPicker: (J) => unref(m)(O, J?.flow, J?.show)
            }), createSlots({ _: 2 }, [
              renderList(unref(f), (J, ce) => ({
                name: J,
                fn: withCtx((pe) => [
                  renderSlot($.$slots, J, normalizeProps(guardReactiveProps(pe)))
                ])
              }))
            ]), 1040, ["items", "instance", "show-year-picker", "year", "is-disabled", "onHandleYear", "onYearSelect", "onToggleYearPicker"])
          ]),
          _: 2
        }, [
          $.$slots["month-overlay-value"] ? {
            name: "item",
            fn: withCtx(({ item: J }) => [
              renderSlot($.$slots, "month-overlay-value", {
                text: J.text,
                value: J.value
              })
            ]),
            key: "0"
          } : void 0
        ]), 1032, ["items", "arrow-navigation", "is-last", "esc-close", "height", "config", "no-overlay-focus", "onSelected", "onHoverValue"]))
      ]),
      _: 3
    }, 8, ["multi-calendars", "collapse", "is-mobile"]));
  }
}), br = (e, t2) => {
  const l = () => {
    e.isTextInputDate && (v.value = getYear(H(e.startDate)));
  }, { modelValue: n } = na(e, t2, l), a = ref(null), { defaultedHighlight: f, defaultedMultiDates: i, defaultedFilters: g, defaultedRange: d, propDates: P } = Oe(e), v = ref();
  onMounted(() => {
    e.startDate && (n.value && e.focusStartDate || !n.value) && (v.value = getYear(H(e.startDate)));
  });
  const R = (m) => Array.isArray(n.value) ? n.value.some((z) => getYear(z) === m) : n.value ? getYear(n.value) === m : false, h2 = (m) => d.value.enabled && Array.isArray(n.value) ? xt(n.value, a.value, C(m)) : false, T = (m) => P.value.allowedDates instanceof Map ? P.value.allowedDates.size ? P.value.allowedDates.has(`${m}`) : false : true, F = (m) => P.value.disabledDates instanceof Map ? P.value.disabledDates.size ? P.value.disabledDates.has(`${m}`) : false : typeof P.value.disabledDates == "function" ? P.value.disabledDates(setYear(Fe(startOfYear(H())), m)) : true, _ = computed(() => Ht(qa(e.yearRange, e.locale, e.reverseYears), (m) => {
    const z = R(m.value), L = Zt(
      m.value,
      Wt(P.value.minDate),
      Wt(P.value.maxDate)
    ) || g.value.years.includes(m.value) || !T(m.value) || F(m.value), le = h2(m.value) && !z, U = xa(f.value, m.value);
    return { active: z, disabled: L, isBetween: le, highlighted: U };
  })), C = (m) => setYear(it(startOfYear(/* @__PURE__ */ new Date())), m);
  return {
    groupedYears: _,
    modelValue: n,
    focusYear: v,
    setHoverValue: (m) => {
      a.value = setYear(it(/* @__PURE__ */ new Date()), m);
    },
    selectYear: (m) => {
      if (t2("update-month-year", { instance: 0, year: m }), i.value.enabled)
        return n.value ? Array.isArray(n.value) && ((n.value?.map((L) => getYear(L))).includes(m) ? n.value = n.value.filter((L) => getYear(L) !== m) : n.value.push(setYear(Fe(H()), m))) : n.value = [setYear(Fe(startOfYear(H())), m)], t2("auto-apply", true);
      d.value.enabled ? (n.value = tn(n, C(m), t2), nextTick().then(() => {
        pa(n.value, t2, e.autoApply, e.modelAuto);
      })) : (n.value = C(m), t2("auto-apply"));
    }
  };
}, kr = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "YearPicker",
  props: {
    ...dt
  },
  emits: [
    "update:internal-model-value",
    "reset-flow",
    "range-start",
    "range-end",
    "auto-apply",
    "update-month-year"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, { groupedYears: f, modelValue: i, focusYear: g, selectYear: d, setHoverValue: P } = br(a, n), { defaultedConfig: v } = Oe(a);
    return t2({ getSidebarProps: () => ({
      modelValue: i,
      selectYear: d
    }) }), (h2, T) => (openBlock(), createElementBlock("div", null, [
      h2.$slots["top-extra"] ? renderSlot(h2.$slots, "top-extra", {
        key: 0,
        value: h2.internalModelValue
      }) : createCommentVNode("", true),
      h2.$slots["month-year"] ? renderSlot(h2.$slots, "month-year", normalizeProps(mergeProps({ key: 1 }, {
        years: unref(f),
        selectYear: unref(d)
      }))) : (openBlock(), createBlock(ta, {
        key: 2,
        items: unref(f),
        "is-last": h2.autoApply && !unref(v).keepActionRow,
        height: unref(v).modeHeight,
        config: h2.config,
        "no-overlay-focus": !!(h2.noOverlayFocus || h2.textInput),
        "focus-value": unref(g),
        type: "year",
        "use-relative": "",
        onSelected: unref(d),
        onHoverValue: unref(P)
      }, createSlots({ _: 2 }, [
        h2.$slots["year-overlay-value"] ? {
          name: "item",
          fn: withCtx(({ item: F }) => [
            renderSlot(h2.$slots, "year-overlay-value", {
              text: F.text,
              value: F.value
            })
          ]),
          key: "0"
        } : void 0
      ]), 1032, ["items", "is-last", "height", "config", "no-overlay-focus", "focus-value", "onSelected", "onHoverValue"]))
    ]));
  }
}), wr = {
  key: 0,
  class: "dp__time_input"
}, Dr = ["data-compact", "data-collapsed"], Mr = ["data-test-id", "aria-label", "onKeydown", "onClick", "onMousedown"], $r = ["aria-label", "disabled", "data-test-id", "onKeydown", "onClick"], Ar = ["data-test-id", "aria-label", "onKeydown", "onClick", "onMousedown"], Tr = { key: 0 }, Sr = ["aria-label", "data-compact"], Pr = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "TimeInput",
  props: {
    hours: { type: Number, default: 0 },
    minutes: { type: Number, default: 0 },
    seconds: { type: Number, default: 0 },
    closeTimePickerBtn: { type: Object, default: null },
    order: { type: Number, default: 0 },
    disabledTimesConfig: { type: Function, default: null },
    validateTime: { type: Function, default: () => false },
    ...dt
  },
  emits: [
    "set-hours",
    "set-minutes",
    "update:hours",
    "update:minutes",
    "update:seconds",
    "reset-flow",
    "mounted",
    "overlay-closed",
    "overlay-opened",
    "am-pm-change"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, { setTimePickerElements: f, setTimePickerBackRef: i } = At(), {
      defaultedAriaLabels: g,
      defaultedTransitions: d,
      defaultedFilters: P,
      defaultedConfig: v,
      defaultedRange: R,
      defaultedMultiCalendars: h2
    } = Oe(a), { transitionName: T, showTransition: F } = aa(d), _ = reactive({
      hours: false,
      minutes: false,
      seconds: false
    }), C = ref("AM"), M = ref(null), A = ref([]), m = ref(), z = ref(false);
    onMounted(() => {
      n("mounted");
    });
    const L = (r) => set(/* @__PURE__ */ new Date(), {
      hours: r.hours,
      minutes: r.minutes,
      seconds: a.enableSeconds ? r.seconds : 0,
      milliseconds: 0
    }), le = computed(
      () => (r) => S(r, a[r]) || $(r, a[r])
    ), U = computed(() => ({ hours: a.hours, minutes: a.minutes, seconds: a.seconds })), $ = (r, E) => R.value.enabled && !R.value.disableTimeRangeValidation ? !a.validateTime(r, E) : false, ee = (r, E) => {
      if (R.value.enabled && !R.value.disableTimeRangeValidation) {
        const K = E ? +a[`${r}Increment`] : -+a[`${r}Increment`], oe = a[r] + K;
        return !a.validateTime(r, oe);
      }
      return false;
    }, O = computed(() => (r) => !re(+a[r] + +a[`${r}Increment`], r) || ee(r, true)), J = computed(() => (r) => !re(+a[r] - +a[`${r}Increment`], r) || ee(r, false)), ce = (r, E) => add(set(H(), r), E), pe = (r, E) => sub(set(H(), r), E), p = computed(
      () => ({
        dp__time_col: true,
        dp__time_col_block: !a.timePickerInline,
        dp__time_col_reg_block: !a.enableSeconds && a.is24 && !a.timePickerInline,
        dp__time_col_reg_inline: !a.enableSeconds && a.is24 && a.timePickerInline,
        dp__time_col_reg_with_button: !a.enableSeconds && !a.is24,
        dp__time_col_sec: a.enableSeconds && a.is24,
        dp__time_col_sec_with_button: a.enableSeconds && !a.is24
      })
    ), Y = computed(
      () => a.timePickerInline && R.value.enabled && !h2.value.count
    ), te = computed(() => {
      const r = [{ type: "hours" }];
      return a.enableMinutes && r.push({ type: "", separator: true }, {
        type: "minutes"
      }), a.enableSeconds && r.push({ type: "", separator: true }, {
        type: "seconds"
      }), r;
    }), y = computed(() => te.value.filter((r) => !r.separator)), V = computed(() => (r) => {
      if (r === "hours") {
        const E = X(+a.hours);
        return { text: E < 10 ? `0${E}` : `${E}`, value: E };
      }
      return { text: a[r] < 10 ? `0${a[r]}` : `${a[r]}`, value: a[r] };
    }), S = (r, E) => {
      if (!a.disabledTimesConfig) return false;
      const K = a.disabledTimesConfig(a.order, r === "hours" ? E : void 0);
      return K[r] ? !!K[r]?.includes(E) : true;
    }, q = (r, E) => E !== "hours" || C.value === "AM" ? r : r + 12, k = (r) => {
      const E = a.is24 ? 24 : 12, K = r === "hours" ? E : 60, oe = +a[`${r}GridIncrement`], ge = r === "hours" && !a.is24 ? oe : 0, _e = [];
      for (let Ye = ge; Ye < K; Ye += oe)
        _e.push({ value: a.is24 ? Ye : q(Ye, r), text: Ye < 10 ? `0${Ye}` : `${Ye}` });
      return r === "hours" && !a.is24 && _e.unshift({ value: C.value === "PM" ? 12 : 0, text: "12" }), Ht(_e, (Ye) => ({ active: false, disabled: P.value.times[r].includes(Ye.value) || !re(Ye.value, r) || S(r, Ye.value) || $(r, Ye.value) }));
    }, se = (r) => r >= 0 ? r : 59, u = (r) => r >= 0 ? r : 23, re = (r, E) => {
      const K = a.minTime ? L(Sa(a.minTime)) : null, oe = a.maxTime ? L(Sa(a.maxTime)) : null, ge = L(
        Sa(
          U.value,
          E,
          E === "minutes" || E === "seconds" ? se(r) : u(r)
        )
      );
      return K && oe ? (isBefore(ge, oe) || isEqual(ge, oe)) && (isAfter(ge, K) || isEqual(ge, K)) : K ? isAfter(ge, K) || isEqual(ge, K) : oe ? isBefore(ge, oe) || isEqual(ge, oe) : true;
    }, G = (r) => a[`no${r[0].toUpperCase() + r.slice(1)}Overlay`], I = (r) => {
      G(r) || (_[r] = !_[r], _[r] ? (z.value = true, n("overlay-opened", r)) : (z.value = false, n("overlay-closed", r)));
    }, x = (r) => r === "hours" ? getHours : r === "minutes" ? getMinutes : getSeconds, c = () => {
      m.value && clearTimeout(m.value);
    }, o = (r, E = true, K) => {
      const oe = E ? ce : pe, ge = E ? +a[`${r}Increment`] : -+a[`${r}Increment`];
      re(+a[r] + ge, r) && n(
        `update:${r}`,
        x(r)(oe({ [r]: +a[r] }, { [r]: +a[`${r}Increment`] }))
      ), !K?.keyboard && v.value.timeArrowHoldThreshold && (m.value = setTimeout(() => {
        o(r, E);
      }, v.value.timeArrowHoldThreshold));
    }, X = (r) => a.is24 ? r : (r >= 12 ? C.value = "PM" : C.value = "AM", Sl(r)), B = () => {
      C.value === "PM" ? (C.value = "AM", n("update:hours", a.hours - 12)) : (C.value = "PM", n("update:hours", a.hours + 12)), n("am-pm-change", C.value);
    }, be = (r) => {
      _[r] = true;
    }, Ae = (r, E, K) => {
      if (r && a.arrowNavigation) {
        Array.isArray(A.value[E]) ? A.value[E][K] = r : A.value[E] = [r];
        const oe = A.value.reduce(
          (ge, _e) => _e.map((Ye, nt) => [...ge[nt] || [], _e[nt]]),
          []
        );
        i(a.closeTimePickerBtn), M.value && (oe[1] = oe[1].concat(M.value)), f(oe, a.order);
      }
    }, ne = (r, E) => (I(r), n(`update:${r}`, E));
    return t2({ openChildCmp: be }), (r, E) => r.disabled ? createCommentVNode("", true) : (openBlock(), createElementBlock("div", wr, [
      (openBlock(true), createElementBlock(Fragment, null, renderList(te.value, (K, oe) => (openBlock(), createElementBlock("div", {
        key: oe,
        class: normalizeClass(p.value),
        "data-compact": Y.value && !r.enableSeconds,
        "data-collapsed": Y.value && r.enableSeconds
      }, [
        K.separator ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
          z.value ? createCommentVNode("", true) : (openBlock(), createElementBlock(Fragment, { key: 0 }, [
            createTextVNode(":")
          ], 64))
        ], 64)) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
          createBaseVNode("button", {
            ref_for: true,
            ref: (ge) => Ae(ge, oe, 0),
            type: "button",
            class: normalizeClass({
              dp__btn: true,
              dp__inc_dec_button: !r.timePickerInline,
              dp__inc_dec_button_inline: r.timePickerInline,
              dp__tp_inline_btn_top: r.timePickerInline,
              dp__inc_dec_button_disabled: O.value(K.type),
              "dp--hidden-el": z.value
            }),
            "data-test-id": `${K.type}-time-inc-btn-${a.order}`,
            "aria-label": unref(g)?.incrementValue(K.type),
            tabindex: "0",
            onKeydown: (ge) => unref(qe)(ge, () => o(K.type, true, { keyboard: true }), true),
            onClick: (ge) => unref(v).timeArrowHoldThreshold ? void 0 : o(K.type, true),
            onMousedown: (ge) => unref(v).timeArrowHoldThreshold ? o(K.type, true) : void 0,
            onMouseup: c
          }, [
            a.timePickerInline ? (openBlock(), createElementBlock(Fragment, { key: 1 }, [
              r.$slots["tp-inline-arrow-up"] ? renderSlot(r.$slots, "tp-inline-arrow-up", { key: 0 }) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
                E[2] || (E[2] = createBaseVNode("span", { class: "dp__tp_inline_btn_bar dp__tp_btn_in_l" }, null, -1)),
                E[3] || (E[3] = createBaseVNode("span", { class: "dp__tp_inline_btn_bar dp__tp_btn_in_r" }, null, -1))
              ], 64))
            ], 64)) : (openBlock(), createElementBlock(Fragment, { key: 0 }, [
              r.$slots["arrow-up"] ? renderSlot(r.$slots, "arrow-up", { key: 0 }) : createCommentVNode("", true),
              r.$slots["arrow-up"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(Ka), { key: 1 }))
            ], 64))
          ], 42, Mr),
          createBaseVNode("button", {
            ref_for: true,
            ref: (ge) => Ae(ge, oe, 1),
            type: "button",
            "aria-label": `${V.value(K.type).text}-${unref(g)?.openTpOverlay(K.type)}`,
            class: normalizeClass({
              dp__time_display: true,
              dp__time_display_block: !r.timePickerInline,
              dp__time_display_inline: r.timePickerInline,
              "dp--time-invalid": le.value(K.type),
              "dp--time-overlay-btn": !le.value(K.type),
              "dp--hidden-el": z.value
            }),
            disabled: G(K.type),
            tabindex: "0",
            "data-test-id": `${K.type}-toggle-overlay-btn-${a.order}`,
            onKeydown: (ge) => unref(qe)(ge, () => I(K.type), true),
            onClick: (ge) => I(K.type)
          }, [
            r.$slots[K.type] ? renderSlot(r.$slots, K.type, {
              key: 0,
              text: V.value(K.type).text,
              value: V.value(K.type).value
            }) : createCommentVNode("", true),
            r.$slots[K.type] ? createCommentVNode("", true) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
              createTextVNode(toDisplayString(V.value(K.type).text), 1)
            ], 64))
          ], 42, $r),
          createBaseVNode("button", {
            ref_for: true,
            ref: (ge) => Ae(ge, oe, 2),
            type: "button",
            class: normalizeClass({
              dp__btn: true,
              dp__inc_dec_button: !r.timePickerInline,
              dp__inc_dec_button_inline: r.timePickerInline,
              dp__tp_inline_btn_bottom: r.timePickerInline,
              dp__inc_dec_button_disabled: J.value(K.type),
              "dp--hidden-el": z.value
            }),
            "data-test-id": `${K.type}-time-dec-btn-${a.order}`,
            "aria-label": unref(g)?.decrementValue(K.type),
            tabindex: "0",
            onKeydown: (ge) => unref(qe)(ge, () => o(K.type, false, { keyboard: true }), true),
            onClick: (ge) => unref(v).timeArrowHoldThreshold ? void 0 : o(K.type, false),
            onMousedown: (ge) => unref(v).timeArrowHoldThreshold ? o(K.type, false) : void 0,
            onMouseup: c
          }, [
            a.timePickerInline ? (openBlock(), createElementBlock(Fragment, { key: 1 }, [
              r.$slots["tp-inline-arrow-down"] ? renderSlot(r.$slots, "tp-inline-arrow-down", { key: 0 }) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
                E[4] || (E[4] = createBaseVNode("span", { class: "dp__tp_inline_btn_bar dp__tp_btn_in_l" }, null, -1)),
                E[5] || (E[5] = createBaseVNode("span", { class: "dp__tp_inline_btn_bar dp__tp_btn_in_r" }, null, -1))
              ], 64))
            ], 64)) : (openBlock(), createElementBlock(Fragment, { key: 0 }, [
              r.$slots["arrow-down"] ? renderSlot(r.$slots, "arrow-down", { key: 0 }) : createCommentVNode("", true),
              r.$slots["arrow-down"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(Ga), { key: 1 }))
            ], 64))
          ], 42, Ar)
        ], 64))
      ], 10, Dr))), 128)),
      r.is24 ? createCommentVNode("", true) : (openBlock(), createElementBlock("div", Tr, [
        r.$slots["am-pm-button"] ? renderSlot(r.$slots, "am-pm-button", {
          key: 0,
          toggle: B,
          value: C.value
        }) : createCommentVNode("", true),
        r.$slots["am-pm-button"] ? createCommentVNode("", true) : (openBlock(), createElementBlock("button", {
          key: 1,
          ref_key: "amPmButton",
          ref: M,
          type: "button",
          class: "dp__pm_am_button",
          role: "button",
          "aria-label": unref(g)?.amPmButton,
          tabindex: "0",
          "data-compact": Y.value,
          onClick: B,
          onKeydown: E[0] || (E[0] = (K) => unref(qe)(K, () => B(), true))
        }, toDisplayString(C.value), 41, Sr))
      ])),
      (openBlock(true), createElementBlock(Fragment, null, renderList(y.value, (K, oe) => (openBlock(), createBlock(Transition, {
        key: oe,
        name: unref(T)(_[K.type]),
        css: unref(F)
      }, {
        default: withCtx(() => [
          _[K.type] ? (openBlock(), createBlock(ta, {
            key: 0,
            items: k(K.type),
            "is-last": r.autoApply && !unref(v).keepActionRow,
            "esc-close": r.escClose,
            type: K.type,
            "text-input": r.textInput,
            config: r.config,
            "arrow-navigation": r.arrowNavigation,
            "aria-labels": r.ariaLabels,
            "overlay-label": unref(g).timeOverlay?.(K.type),
            onSelected: (ge) => ne(K.type, ge),
            onToggle: (ge) => I(K.type),
            onResetFlow: E[1] || (E[1] = (ge) => r.$emit("reset-flow"))
          }, createSlots({
            "button-icon": withCtx(() => [
              r.$slots["clock-icon"] ? renderSlot(r.$slots, "clock-icon", { key: 0 }) : createCommentVNode("", true),
              r.$slots["clock-icon"] ? createCommentVNode("", true) : (openBlock(), createBlock(resolveDynamicComponent(r.timePickerInline ? unref(jt) : unref(ja)), { key: 1 }))
            ]),
            _: 2
          }, [
            r.$slots[`${K.type}-overlay-value`] ? {
              name: "item",
              fn: withCtx(({ item: ge }) => [
                renderSlot(r.$slots, `${K.type}-overlay-value`, {
                  text: ge.text,
                  value: ge.value
                })
              ]),
              key: "0"
            } : void 0,
            r.$slots[`${K.type}-overlay-header`] ? {
              name: "header",
              fn: withCtx(() => [
                renderSlot(r.$slots, `${K.type}-overlay-header`, {
                  toggle: () => I(K.type)
                })
              ]),
              key: "1"
            } : void 0
          ]), 1032, ["items", "is-last", "esc-close", "type", "text-input", "config", "arrow-navigation", "aria-labels", "overlay-label", "onSelected", "onToggle"])) : createCommentVNode("", true)
        ]),
        _: 2
      }, 1032, ["name", "css"]))), 128))
    ]));
  }
}), Rr = ["data-dp-mobile"], Cr = ["aria-label", "tabindex"], Or = ["role", "aria-label", "tabindex"], Br = ["aria-label"], Kn = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "TimePicker",
  props: {
    hours: { type: [Number, Array], default: 0 },
    minutes: { type: [Number, Array], default: 0 },
    seconds: { type: [Number, Array], default: 0 },
    disabledTimesConfig: { type: Function, default: null },
    validateTime: {
      type: Function,
      default: () => false
    },
    ...dt
  },
  emits: [
    "update:hours",
    "update:minutes",
    "update:seconds",
    "mount",
    "reset-flow",
    "overlay-opened",
    "overlay-closed",
    "am-pm-change"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, { buildMatrix: f, setTimePicker: i } = At(), g = useSlots(), { defaultedTransitions: d, defaultedAriaLabels: P, defaultedTextInput: v, defaultedConfig: R, defaultedRange: h2 } = Oe(a), { transitionName: T, showTransition: F } = aa(d), { hideNavigationButtons: _ } = ga(), C = ref(null), M = ref(null), A = ref([]), m = ref(null), z = ref(false);
    onMounted(() => {
      n("mount"), !a.timePicker && a.arrowNavigation ? f([Le(C.value)], "time") : i(true, a.timePicker);
    });
    const L = computed(() => h2.value.enabled && a.modelAuto ? Rn(a.internalModelValue) : true), le = ref(false), U = (S) => ({
      hours: Array.isArray(a.hours) ? a.hours[S] : a.hours,
      minutes: Array.isArray(a.minutes) ? a.minutes[S] : a.minutes,
      seconds: Array.isArray(a.seconds) ? a.seconds[S] : a.seconds
    }), $ = computed(() => {
      const S = [];
      if (h2.value.enabled)
        for (let q = 0; q < 2; q++)
          S.push(U(q));
      else
        S.push(U(0));
      return S;
    }), ee = (S, q = false, k = "") => {
      q || n("reset-flow"), le.value = S, n(S ? "overlay-opened" : "overlay-closed", je.time), a.arrowNavigation && i(S), nextTick(() => {
        k !== "" && A.value[0] && A.value[0].openChildCmp(k);
      });
    }, O = computed(() => ({
      dp__btn: true,
      dp__button: true,
      dp__button_bottom: a.autoApply && !R.value.keepActionRow
    })), J = et(g, "timePicker"), ce = (S, q, k) => h2.value.enabled ? q === 0 ? [S, $.value[1][k]] : [$.value[0][k], S] : S, pe = (S) => {
      n("update:hours", S);
    }, p = (S) => {
      n("update:minutes", S);
    }, Y = (S) => {
      n("update:seconds", S);
    }, te = () => {
      if (m.value && !v.value.enabled && !a.noOverlayFocus) {
        const S = Cn(m.value);
        S && S.focus({ preventScroll: true });
      }
    }, y = (S) => {
      z.value = false, n("overlay-closed", S);
    }, V = (S) => {
      z.value = true, n("overlay-opened", S);
    };
    return t2({ toggleTimePicker: ee }), (S, q) => (openBlock(), createElementBlock("div", {
      class: "dp--tp-wrap",
      "data-dp-mobile": S.isMobile
    }, [
      !S.timePicker && !S.timePickerInline ? withDirectives((openBlock(), createElementBlock("button", {
        key: 0,
        ref_key: "openTimePickerBtn",
        ref: C,
        type: "button",
        class: normalizeClass({ ...O.value, "dp--hidden-el": le.value }),
        "aria-label": unref(P)?.openTimePicker,
        tabindex: S.noOverlayFocus ? void 0 : 0,
        "data-test-id": "open-time-picker-btn",
        onKeydown: q[0] || (q[0] = (k) => unref(qe)(k, () => ee(true))),
        onClick: q[1] || (q[1] = (k) => ee(true))
      }, [
        S.$slots["clock-icon"] ? renderSlot(S.$slots, "clock-icon", { key: 0 }) : createCommentVNode("", true),
        S.$slots["clock-icon"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(ja), { key: 1 }))
      ], 42, Cr)), [
        [vShow, !unref(_)(S.hideNavigation, "time")]
      ]) : createCommentVNode("", true),
      createVNode(Transition, {
        name: unref(T)(le.value),
        css: unref(F) && !S.timePickerInline
      }, {
        default: withCtx(() => [
          le.value || S.timePicker || S.timePickerInline ? (openBlock(), createElementBlock("div", {
            key: 0,
            ref_key: "overlayRef",
            ref: m,
            role: S.timePickerInline ? void 0 : "dialog",
            class: normalizeClass({
              dp__overlay: !S.timePickerInline,
              "dp--overlay-absolute": !a.timePicker && !S.timePickerInline,
              "dp--overlay-relative": a.timePicker
            }),
            style: normalizeStyle(S.timePicker ? { height: `${unref(R).modeHeight}px` } : void 0),
            "aria-label": unref(P)?.timePicker,
            tabindex: S.timePickerInline ? void 0 : 0
          }, [
            createBaseVNode("div", {
              class: normalizeClass(
                S.timePickerInline ? "dp__time_picker_inline_container" : "dp__overlay_container dp__container_flex dp__time_picker_overlay_container"
              ),
              style: { display: "flex" }
            }, [
              S.$slots["time-picker-overlay"] ? renderSlot(S.$slots, "time-picker-overlay", {
                key: 0,
                hours: e.hours,
                minutes: e.minutes,
                seconds: e.seconds,
                setHours: pe,
                setMinutes: p,
                setSeconds: Y
              }) : createCommentVNode("", true),
              S.$slots["time-picker-overlay"] ? createCommentVNode("", true) : (openBlock(), createElementBlock("div", {
                key: 1,
                class: normalizeClass(S.timePickerInline ? "dp__flex" : "dp__overlay_row dp__flex_row")
              }, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($.value, (k, se) => withDirectives((openBlock(), createBlock(Pr, mergeProps({ key: se }, { ref_for: true }, {
                  ...S.$props,
                  order: se,
                  hours: k.hours,
                  minutes: k.minutes,
                  seconds: k.seconds,
                  closeTimePickerBtn: M.value,
                  disabledTimesConfig: e.disabledTimesConfig,
                  disabled: se === 0 ? unref(h2).fixedStart : unref(h2).fixedEnd
                }, {
                  ref_for: true,
                  ref_key: "timeInputRefs",
                  ref: A,
                  "validate-time": (u, re) => e.validateTime(u, ce(re, se, u)),
                  "onUpdate:hours": (u) => pe(ce(u, se, "hours")),
                  "onUpdate:minutes": (u) => p(ce(u, se, "minutes")),
                  "onUpdate:seconds": (u) => Y(ce(u, se, "seconds")),
                  onMounted: te,
                  onOverlayClosed: y,
                  onOverlayOpened: V,
                  onAmPmChange: q[2] || (q[2] = (u) => S.$emit("am-pm-change", u))
                }), createSlots({ _: 2 }, [
                  renderList(unref(J), (u, re) => ({
                    name: u,
                    fn: withCtx((G) => [
                      renderSlot(S.$slots, u, mergeProps({ ref_for: true }, G))
                    ])
                  }))
                ]), 1040, ["validate-time", "onUpdate:hours", "onUpdate:minutes", "onUpdate:seconds"])), [
                  [vShow, se === 0 ? true : L.value]
                ])), 128))
              ], 2)),
              !S.timePicker && !S.timePickerInline ? withDirectives((openBlock(), createElementBlock("button", {
                key: 2,
                ref_key: "closeTimePickerBtn",
                ref: M,
                type: "button",
                class: normalizeClass({ ...O.value, "dp--hidden-el": z.value }),
                "aria-label": unref(P)?.closeTimePicker,
                tabindex: "0",
                onKeydown: q[3] || (q[3] = (k) => unref(qe)(k, () => ee(false))),
                onClick: q[4] || (q[4] = (k) => ee(false))
              }, [
                S.$slots["calendar-icon"] ? renderSlot(S.$slots, "calendar-icon", { key: 0 }) : createCommentVNode("", true),
                S.$slots["calendar-icon"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(jt), { key: 1 }))
              ], 42, Br)), [
                [vShow, !unref(_)(S.hideNavigation, "time")]
              ]) : createCommentVNode("", true)
            ], 2)
          ], 14, Or)) : createCommentVNode("", true)
        ]),
        _: 3
      }, 8, ["name", "css"])
    ], 8, Rr));
  }
}), Gn = (e, t2, l, n) => {
  const { defaultedRange: a } = Oe(e), f = (m, z) => Array.isArray(t2[m]) ? t2[m][z] : t2[m], i = (m) => e.enableSeconds ? Array.isArray(t2.seconds) ? t2.seconds[m] : t2.seconds : 0, g = (m, z) => m ? z !== void 0 ? Mt(m, f("hours", z), f("minutes", z), i(z)) : Mt(m, t2.hours, t2.minutes, i()) : setSeconds(H(), i(z)), d = (m, z) => {
    t2[m] = z;
  }, P = computed(() => e.modelAuto && a.value.enabled ? Array.isArray(l.value) ? l.value.length > 1 : false : a.value.enabled), v = (m, z) => {
    const L = Object.fromEntries(
      Object.keys(t2).map((le) => le === m ? [le, z] : [le, t2[le]].slice())
    );
    if (P.value && !a.value.disableTimeRangeValidation) {
      const le = ($) => l.value ? Mt(
        l.value[$],
        L.hours[$],
        L.minutes[$],
        L.seconds[$]
      ) : null, U = ($) => setMilliseconds(l.value[$], 0);
      return !($e(le(0), le(1)) && (isAfter(le(0), U(1)) || isBefore(le(1), U(0))));
    }
    return true;
  }, R = (m, z) => {
    v(m, z) && (d(m, z), n && n());
  }, h2 = (m) => {
    R("hours", m);
  }, T = (m) => {
    R("minutes", m);
  }, F = (m) => {
    R("seconds", m);
  }, _ = (m, z, L, le) => {
    z && h2(m), !z && !L && T(m), L && F(m), l.value && le(l.value);
  }, C = (m) => {
    if (m) {
      const z = Array.isArray(m), L = z ? [+m[0].hours, +m[1].hours] : +m.hours, le = z ? [+m[0].minutes, +m[1].minutes] : +m.minutes, U = z ? [+m[0].seconds, +m[1].seconds] : +m.seconds;
      d("hours", L), d("minutes", le), e.enableSeconds && d("seconds", U);
    }
  }, M = (m, z) => {
    const L = {
      hours: Array.isArray(t2.hours) ? t2.hours[m] : t2.hours,
      disabledArr: []
    };
    return (z || z === 0) && (L.hours = z), Array.isArray(e.disabledTimes) && (L.disabledArr = a.value.enabled && Array.isArray(e.disabledTimes[m]) ? e.disabledTimes[m] : e.disabledTimes), L;
  }, A = computed(() => (m, z) => {
    if (Array.isArray(e.disabledTimes)) {
      const { disabledArr: L, hours: le } = M(m, z), U = L.filter(($) => +$.hours === le);
      return U[0]?.minutes === "*" ? { hours: [le], minutes: void 0, seconds: void 0 } : {
        hours: [],
        minutes: U?.map(($) => +$.minutes) ?? [],
        seconds: U?.map(($) => $.seconds ? +$.seconds : void 0) ?? []
      };
    }
    return { hours: [], minutes: [], seconds: [] };
  });
  return {
    setTime: d,
    updateHours: h2,
    updateMinutes: T,
    updateSeconds: F,
    getSetDateTime: g,
    updateTimeValues: _,
    getSecondsValue: i,
    assignStartTime: C,
    validateTime: v,
    disabledTimesConfig: A
  };
}, _r = (e, t2) => {
  const l = () => {
    e.isTextInputDate && z();
  }, { modelValue: n, time: a } = na(e, t2, l), { defaultedStartTime: f, defaultedRange: i, defaultedTz: g } = Oe(e), { updateTimeValues: d, getSetDateTime: P, setTime: v, assignStartTime: R, disabledTimesConfig: h2, validateTime: T } = Gn(e, a, n, F);
  function F() {
    t2("update-flow-step");
  }
  const _ = (U) => {
    const { hours: $, minutes: ee, seconds: O } = U;
    return { hours: +$, minutes: +ee, seconds: O ? +O : 0 };
  }, C = () => {
    if (e.startTime) {
      if (Array.isArray(e.startTime)) {
        const $ = _(e.startTime[0]), ee = _(e.startTime[1]);
        return [set(H(), $), set(H(), ee)];
      }
      const U = _(e.startTime);
      return set(H(), U);
    }
    return i.value.enabled ? [null, null] : null;
  }, M = () => {
    if (i.value.enabled) {
      const [U, $] = C();
      n.value = [
        Ze(P(U, 0), g.value.timezone),
        Ze(P($, 1), g.value.timezone)
      ];
    } else
      n.value = Ze(P(C()), g.value.timezone);
  }, A = (U) => Array.isArray(U) ? [Bt(H(U[0])), Bt(H(U[1]))] : [Bt(U ?? H())], m = (U, $, ee) => {
    v("hours", U), v("minutes", $), v("seconds", e.enableSeconds ? ee : 0);
  }, z = () => {
    const [U, $] = A(n.value);
    return i.value.enabled ? m(
      [U.hours, $.hours],
      [U.minutes, $.minutes],
      [U.seconds, $.seconds]
    ) : m(U.hours, U.minutes, U.seconds);
  };
  onMounted(() => {
    if (!e.shadow)
      return R(f.value), n.value ? z() : M();
  });
  const L = () => {
    Array.isArray(n.value) ? n.value = n.value.map((U, $) => U && P(U, $)) : n.value = P(n.value), t2("time-update");
  };
  return {
    modelValue: n,
    time: a,
    disabledTimesConfig: h2,
    updateTime: (U, $ = true, ee = false) => {
      d(U, $, ee, L);
    },
    validateTime: T
  };
}, Yr = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "TimePickerSolo",
  props: {
    ...dt
  },
  emits: [
    "update:internal-model-value",
    "time-update",
    "am-pm-change",
    "mount",
    "reset-flow",
    "update-flow-step",
    "overlay-toggle"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, f = useSlots(), i = et(f, "timePicker"), g = ref(null), { time: d, modelValue: P, disabledTimesConfig: v, updateTime: R, validateTime: h2 } = _r(a, n);
    return onMounted(() => {
      a.shadow || n("mount", null);
    }), t2({ getSidebarProps: () => ({
      modelValue: P,
      time: d,
      updateTime: R
    }), toggleTimePicker: (_, C = false, M = "") => {
      g.value?.toggleTimePicker(_, C, M);
    } }), (_, C) => (openBlock(), createBlock(ma, {
      "multi-calendars": 0,
      stretch: "",
      "is-mobile": _.isMobile
    }, {
      default: withCtx(() => [
        createVNode(Kn, mergeProps({
          ref_key: "tpRef",
          ref: g
        }, _.$props, {
          hours: unref(d).hours,
          minutes: unref(d).minutes,
          seconds: unref(d).seconds,
          "internal-model-value": _.internalModelValue,
          "disabled-times-config": unref(v),
          "validate-time": unref(h2),
          "onUpdate:hours": C[0] || (C[0] = (M) => unref(R)(M)),
          "onUpdate:minutes": C[1] || (C[1] = (M) => unref(R)(M, false)),
          "onUpdate:seconds": C[2] || (C[2] = (M) => unref(R)(M, false, true)),
          onAmPmChange: C[3] || (C[3] = (M) => _.$emit("am-pm-change", M)),
          onResetFlow: C[4] || (C[4] = (M) => _.$emit("reset-flow")),
          onOverlayClosed: C[5] || (C[5] = (M) => _.$emit("overlay-toggle", { open: false, overlay: M })),
          onOverlayOpened: C[6] || (C[6] = (M) => _.$emit("overlay-toggle", { open: true, overlay: M }))
        }), createSlots({ _: 2 }, [
          renderList(unref(i), (M, A) => ({
            name: M,
            fn: withCtx((m) => [
              renderSlot(_.$slots, M, normalizeProps(guardReactiveProps(m)))
            ])
          }))
        ]), 1040, ["hours", "minutes", "seconds", "internal-model-value", "disabled-times-config", "validate-time"])
      ]),
      _: 3
    }, 8, ["is-mobile"]));
  }
}), Ir = { class: "dp--header-wrap" }, Er = {
  key: 0,
  class: "dp__month_year_wrap"
}, Nr = { key: 0 }, Lr = { class: "dp__month_year_wrap" }, Fr = ["data-dp-element", "aria-label", "data-test-id", "onClick", "onKeydown"], zr = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "DpHeader",
  props: {
    month: { type: Number, default: 0 },
    year: { type: Number, default: 0 },
    instance: { type: Number, default: 0 },
    years: { type: Array, default: () => [] },
    months: { type: Array, default: () => [] },
    ...dt
  },
  emits: ["update-month-year", "mount", "reset-flow", "overlay-closed", "overlay-opened"],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, {
      defaultedTransitions: f,
      defaultedAriaLabels: i,
      defaultedMultiCalendars: g,
      defaultedFilters: d,
      defaultedConfig: P,
      defaultedHighlight: v,
      propDates: R,
      defaultedUI: h2
    } = Oe(a), { transitionName: T, showTransition: F } = aa(f), { buildMatrix: _ } = At(), { handleMonthYearChange: C, isDisabled: M, updateMonthYear: A } = or(a, n), { showLeftIcon: m, showRightIcon: z } = ga(), L = ref(false), le = ref(false), U = ref(false), $ = ref([null, null, null, null]);
    onMounted(() => {
      n("mount");
    });
    const ee = (u) => ({
      get: () => a[u],
      set: (re) => {
        const G = u === ot.month ? ot.year : ot.month;
        n("update-month-year", { [u]: re, [G]: a[G] }), u === ot.month ? y(true) : V(true);
      }
    }), O = computed(ee(ot.month)), J = computed(ee(ot.year)), ce = computed(() => (u) => ({
      month: a.month,
      year: a.year,
      items: u === ot.month ? a.months : a.years,
      instance: a.instance,
      updateMonthYear: A,
      toggle: u === ot.month ? y : V
    })), pe = computed(() => {
      const u = a.months.find((re) => re.value === a.month);
      return u || { text: "", value: 0 };
    }), p = computed(() => Ht(a.months, (u) => {
      const re = a.month === u.value, G = Zt(
        u.value,
        _n(a.year, R.value.minDate),
        Yn(a.year, R.value.maxDate)
      ) || d.value.months.includes(u.value), I = Fn(v.value, u.value, a.year);
      return { active: re, disabled: G, highlighted: I };
    })), Y = computed(() => Ht(a.years, (u) => {
      const re = a.year === u.value, G = Zt(
        u.value,
        Wt(R.value.minDate),
        Wt(R.value.maxDate)
      ) || d.value.years.includes(u.value), I = xa(v.value, u.value);
      return { active: re, disabled: G, highlighted: I };
    })), te = (u, re, G) => {
      G !== void 0 ? u.value = G : u.value = !u.value, u.value ? (U.value = true, n("overlay-opened", re)) : (U.value = false, n("overlay-closed", re));
    }, y = (u = false, re) => {
      S(u), te(L, je.month, re);
    }, V = (u = false, re) => {
      S(u), te(le, je.year, re);
    }, S = (u) => {
      u || n("reset-flow");
    }, q = (u, re) => {
      a.arrowNavigation && ($.value[re] = Le(u), _($.value, "monthYear"));
    }, k = computed(() => [
      {
        type: ot.month,
        index: 1,
        toggle: y,
        modelValue: O.value,
        updateModelValue: (u) => O.value = u,
        text: pe.value.text,
        showSelectionGrid: L.value,
        items: p.value,
        ariaLabel: i.value?.openMonthsOverlay,
        overlayLabel: i.value.monthPicker?.(true) ?? void 0
      },
      {
        type: ot.year,
        index: 2,
        toggle: V,
        modelValue: J.value,
        updateModelValue: (u) => J.value = u,
        text: Ja(a.year, a.locale),
        showSelectionGrid: le.value,
        items: Y.value,
        ariaLabel: i.value?.openYearsOverlay,
        overlayLabel: i.value.yearPicker?.(true) ?? void 0
      }
    ]), se = computed(() => a.disableYearSelect ? [k.value[0]] : a.yearFirst ? [...k.value].reverse() : k.value);
    return t2({
      toggleMonthPicker: y,
      toggleYearPicker: V,
      handleMonthYearChange: C
    }), (u, re) => (openBlock(), createElementBlock("div", Ir, [
      u.$slots["month-year"] ? (openBlock(), createElementBlock("div", Er, [
        renderSlot(u.$slots, "month-year", normalizeProps(guardReactiveProps({
          month: e.month,
          year: e.year,
          months: e.months,
          years: e.years,
          updateMonthYear: unref(A),
          handleMonthYearChange: unref(C),
          instance: e.instance,
          isDisabled: unref(M)
        })))
      ])) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
        u.$slots["top-extra"] ? (openBlock(), createElementBlock("div", Nr, [
          renderSlot(u.$slots, "top-extra", { value: u.internalModelValue })
        ])) : createCommentVNode("", true),
        createBaseVNode("div", Lr, [
          unref(m)(unref(g), e.instance) && !u.vertical ? (openBlock(), createBlock(Qt, {
            key: 0,
            "aria-label": unref(i)?.prevMonth,
            disabled: unref(M)(false),
            class: normalizeClass(unref(h2)?.navBtnPrev),
            "el-name": "action-prev",
            onActivate: re[0] || (re[0] = (G) => unref(C)(false, true)),
            onSetRef: re[1] || (re[1] = (G) => q(G, 0))
          }, {
            default: withCtx(() => [
              u.$slots["arrow-left"] ? renderSlot(u.$slots, "arrow-left", { key: 0 }) : createCommentVNode("", true),
              u.$slots["arrow-left"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(Ua), { key: 1 }))
            ]),
            _: 3
          }, 8, ["aria-label", "disabled", "class"])) : createCommentVNode("", true),
          createBaseVNode("div", {
            class: normalizeClass(["dp__month_year_wrap", {
              dp__year_disable_select: u.disableYearSelect
            }])
          }, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(se.value, (G, I) => (openBlock(), createElementBlock(Fragment, {
              key: G.type
            }, [
              createBaseVNode("button", {
                ref_for: true,
                ref: (x) => q(x, I + 1),
                type: "button",
                "data-dp-element": `overlay-${G.type}`,
                class: normalizeClass(["dp__btn dp__month_year_select", { "dp--hidden-el": U.value }]),
                "aria-label": `${G.text}-${G.ariaLabel}`,
                "data-test-id": `${G.type}-toggle-overlay-${e.instance}`,
                onClick: G.toggle,
                onKeydown: (x) => unref(qe)(x, () => G.toggle(), true)
              }, [
                u.$slots[G.type] ? renderSlot(u.$slots, G.type, {
                  key: 0,
                  text: G.text,
                  value: a[G.type]
                }) : createCommentVNode("", true),
                u.$slots[G.type] ? createCommentVNode("", true) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
                  createTextVNode(toDisplayString(G.text), 1)
                ], 64))
              ], 42, Fr),
              createVNode(Transition, {
                name: unref(T)(G.showSelectionGrid),
                css: unref(F)
              }, {
                default: withCtx(() => [
                  G.showSelectionGrid ? (openBlock(), createBlock(ta, {
                    key: 0,
                    items: G.items,
                    "arrow-navigation": u.arrowNavigation,
                    "hide-navigation": u.hideNavigation,
                    "is-last": u.autoApply && !unref(P).keepActionRow,
                    "skip-button-ref": false,
                    config: u.config,
                    type: G.type,
                    "header-refs": [],
                    "esc-close": u.escClose,
                    "menu-wrap-ref": u.menuWrapRef,
                    "text-input": u.textInput,
                    "aria-labels": u.ariaLabels,
                    "overlay-label": G.overlayLabel,
                    onSelected: G.updateModelValue,
                    onToggle: G.toggle
                  }, createSlots({
                    "button-icon": withCtx(() => [
                      u.$slots["calendar-icon"] ? renderSlot(u.$slots, "calendar-icon", { key: 0 }) : createCommentVNode("", true),
                      u.$slots["calendar-icon"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(jt), { key: 1 }))
                    ]),
                    _: 2
                  }, [
                    u.$slots[`${G.type}-overlay-value`] ? {
                      name: "item",
                      fn: withCtx(({ item: x }) => [
                        renderSlot(u.$slots, `${G.type}-overlay-value`, {
                          text: x.text,
                          value: x.value
                        })
                      ]),
                      key: "0"
                    } : void 0,
                    u.$slots[`${G.type}-overlay`] ? {
                      name: "overlay",
                      fn: withCtx(() => [
                        renderSlot(u.$slots, `${G.type}-overlay`, mergeProps({ ref_for: true }, ce.value(G.type)))
                      ]),
                      key: "1"
                    } : void 0,
                    u.$slots[`${G.type}-overlay-header`] ? {
                      name: "header",
                      fn: withCtx(() => [
                        renderSlot(u.$slots, `${G.type}-overlay-header`, {
                          toggle: G.toggle
                        })
                      ]),
                      key: "2"
                    } : void 0
                  ]), 1032, ["items", "arrow-navigation", "hide-navigation", "is-last", "config", "type", "esc-close", "menu-wrap-ref", "text-input", "aria-labels", "overlay-label", "onSelected", "onToggle"])) : createCommentVNode("", true)
                ]),
                _: 2
              }, 1032, ["name", "css"])
            ], 64))), 128))
          ], 2),
          unref(m)(unref(g), e.instance) && u.vertical ? (openBlock(), createBlock(Qt, {
            key: 1,
            "aria-label": unref(i)?.prevMonth,
            "el-name": "action-prev",
            disabled: unref(M)(false),
            class: normalizeClass(unref(h2)?.navBtnPrev),
            onActivate: re[2] || (re[2] = (G) => unref(C)(false, true))
          }, {
            default: withCtx(() => [
              u.$slots["arrow-up"] ? renderSlot(u.$slots, "arrow-up", { key: 0 }) : createCommentVNode("", true),
              u.$slots["arrow-up"] ? createCommentVNode("", true) : (openBlock(), createBlock(unref(Ka), { key: 1 }))
            ]),
            _: 3
          }, 8, ["aria-label", "disabled", "class"])) : createCommentVNode("", true),
          unref(z)(unref(g), e.instance) ? (openBlock(), createBlock(Qt, {
            key: 2,
            ref: "rightIcon",
            "el-name": "action-next",
            disabled: unref(M)(true),
            "aria-label": unref(i)?.nextMonth,
            class: normalizeClass(unref(h2)?.navBtnNext),
            onActivate: re[3] || (re[3] = (G) => unref(C)(true, true)),
            onSetRef: re[4] || (re[4] = (G) => q(G, u.disableYearSelect ? 2 : 3))
          }, {
            default: withCtx(() => [
              u.$slots[u.vertical ? "arrow-down" : "arrow-right"] ? renderSlot(u.$slots, u.vertical ? "arrow-down" : "arrow-right", { key: 0 }) : createCommentVNode("", true),
              u.$slots[u.vertical ? "arrow-down" : "arrow-right"] ? createCommentVNode("", true) : (openBlock(), createBlock(resolveDynamicComponent(u.vertical ? unref(Ga) : unref(Va)), { key: 1 }))
            ]),
            _: 3
          }, 8, ["disabled", "aria-label", "class"])) : createCommentVNode("", true)
        ])
      ], 64))
    ]));
  }
}), Hr = {
  class: "dp__calendar_header",
  role: "row"
}, Wr = {
  key: 0,
  class: "dp__calendar_header_item",
  role: "gridcell"
}, Ur = ["aria-label"], Vr = {
  key: 0,
  class: "dp__calendar_item dp__week_num",
  role: "gridcell"
}, jr = { class: "dp__cell_inner" }, Kr = ["id", "aria-selected", "aria-disabled", "aria-label", "tabindex", "data-test-id", "onClick", "onTouchend", "onKeydown", "onMouseenter", "onMouseleave", "onMousedown"], Gr = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "DpCalendar",
  props: {
    mappedDates: { type: Array, default: () => [] },
    instance: { type: Number, default: 0 },
    month: { type: Number, default: 0 },
    year: { type: Number, default: 0 },
    ...dt
  },
  emits: [
    "select-date",
    "set-hover-date",
    "handle-scroll",
    "mount",
    "handle-swipe",
    "handle-space",
    "tooltip-open",
    "tooltip-close"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, { buildMultiLevelMatrix: f } = At(), {
      defaultedTransitions: i,
      defaultedConfig: g,
      defaultedAriaLabels: d,
      defaultedMultiCalendars: P,
      defaultedWeekNumbers: v,
      defaultedMultiDates: R,
      defaultedUI: h2
    } = Oe(a), T = ref(null), F = ref({
      bottom: "",
      left: "",
      transform: ""
    }), _ = ref([]), C = ref(null), M = ref(true), A = ref(""), m = ref({ startX: 0, endX: 0, startY: 0, endY: 0 }), z = ref([]), L = ref({ left: "50%" }), le = ref(false), U = computed(() => a.calendar ? a.calendar(a.mappedDates) : a.mappedDates), $ = computed(() => a.dayNames ? Array.isArray(a.dayNames) ? a.dayNames : a.dayNames(a.locale, +a.weekStart) : Tl(a.formatLocale, a.locale, +a.weekStart));
    onMounted(() => {
      n("mount", { cmp: "calendar", refs: _ }), g.value.noSwipe || C.value && (C.value.addEventListener("touchstart", q, { passive: false }), C.value.addEventListener("touchend", k, { passive: false }), C.value.addEventListener("touchmove", se, { passive: false })), a.monthChangeOnScroll && C.value && C.value.addEventListener("wheel", G, { passive: false });
    }), onUnmounted(() => {
      g.value.noSwipe || C.value && (C.value.removeEventListener("touchstart", q), C.value.removeEventListener("touchend", k), C.value.removeEventListener("touchmove", se)), a.monthChangeOnScroll && C.value && C.value.removeEventListener("wheel", G);
    });
    const ee = (B) => B ? a.vertical ? "vNext" : "next" : a.vertical ? "vPrevious" : "previous", O = (B, be) => {
      if (a.transitions) {
        const Ae = Fe(pt(H(), a.month, a.year));
        A.value = Ee(Fe(pt(H(), B, be)), Ae) ? i.value[ee(true)] : i.value[ee(false)], M.value = false, nextTick(() => {
          M.value = true;
        });
      }
    }, J = computed(
      () => ({
        ...h2.value.calendar ?? {}
      })
    ), ce = computed(() => (B) => {
      const be = Pl(B);
      return {
        dp__marker_dot: be.type === "dot",
        dp__marker_line: be.type === "line"
      };
    }), pe = computed(() => (B) => $e(B, T.value)), p = computed(() => ({
      dp__calendar: true,
      dp__calendar_next: P.value.count > 0 && a.instance !== 0
    })), Y = computed(() => (B) => a.hideOffsetDates ? B.current : true), te = async (B, be) => {
      const { width: Ae, height: ne } = B.getBoundingClientRect();
      T.value = be.value;
      let r = { left: `${Ae / 2}px` }, E = -50;
      if (await nextTick(), z.value[0]) {
        const { left: K, width: oe } = z.value[0].getBoundingClientRect();
        K < 0 && (r = { left: "0" }, E = 0, L.value.left = `${Ae / 2}px`), window.innerWidth < K + oe && (r = { right: "0" }, E = 0, L.value.left = `${oe - Ae / 2}px`);
      }
      F.value = {
        bottom: `${ne}px`,
        ...r,
        transform: `translateX(${E}%)`
      };
    }, y = async (B, be, Ae) => {
      const ne = Le(_.value[be][Ae]);
      ne && (B.marker?.customPosition && B.marker?.tooltip?.length ? F.value = B.marker.customPosition(ne) : await te(ne, B), n("tooltip-open", B.marker));
    }, V = async (B, be, Ae) => {
      if (le.value && R.value.enabled && R.value.dragSelect)
        return n("select-date", B);
      if (n("set-hover-date", B), B.marker?.tooltip?.length) {
        if (a.hideOffsetDates && !B.current) return;
        await y(B, be, Ae);
      }
    }, S = (B) => {
      T.value && (T.value = null, F.value = JSON.parse(JSON.stringify({ bottom: "", left: "", transform: "" })), n("tooltip-close", B.marker));
    }, q = (B) => {
      m.value.startX = B.changedTouches[0].screenX, m.value.startY = B.changedTouches[0].screenY;
    }, k = (B) => {
      m.value.endX = B.changedTouches[0].screenX, m.value.endY = B.changedTouches[0].screenY, u();
    }, se = (B) => {
      a.vertical && !a.inline && B.preventDefault();
    }, u = () => {
      const B = a.vertical ? "Y" : "X";
      Math.abs(m.value[`start${B}`] - m.value[`end${B}`]) > 10 && n("handle-swipe", m.value[`start${B}`] > m.value[`end${B}`] ? "right" : "left");
    }, re = (B, be, Ae) => {
      B && (Array.isArray(_.value[be]) ? _.value[be][Ae] = B : _.value[be] = [B]), a.arrowNavigation && f(_.value, "calendar");
    }, G = (B) => {
      a.monthChangeOnScroll && (B.preventDefault(), n("handle-scroll", B));
    }, I = (B) => v.value.type === "local" ? getWeek(B.value, { weekStartsOn: +a.weekStart }) : v.value.type === "iso" ? getISOWeek(B.value) : typeof v.value.type == "function" ? v.value.type(B.value) : "", x = (B) => {
      const be = B[0];
      return v.value.hideOnOffsetDates ? B.some((Ae) => Ae.current) ? I(be) : "" : I(be);
    }, c = (B, be, Ae = true) => {
      !Ae && _l() || (!R.value.enabled || g.value.allowPreventDefault) && (Dt(B, g.value), n("select-date", be));
    }, o = (B) => {
      Dt(B, g.value);
    }, X = (B) => {
      R.value.enabled && R.value.dragSelect ? (le.value = true, n("select-date", B)) : R.value.enabled && n("select-date", B);
    };
    return t2({ triggerTransition: O }), (B, be) => (openBlock(), createElementBlock("div", {
      class: normalizeClass(p.value)
    }, [
      createBaseVNode("div", {
        ref_key: "calendarWrapRef",
        ref: C,
        class: normalizeClass(J.value),
        role: "grid"
      }, [
        createBaseVNode("div", Hr, [
          B.weekNumbers ? (openBlock(), createElementBlock("div", Wr, toDisplayString(B.weekNumName), 1)) : createCommentVNode("", true),
          (openBlock(true), createElementBlock(Fragment, null, renderList($.value, (Ae, ne) => (openBlock(), createElementBlock("div", {
            key: ne,
            class: "dp__calendar_header_item",
            role: "gridcell",
            "data-test-id": "calendar-header",
            "aria-label": unref(d)?.weekDay?.(ne)
          }, [
            B.$slots["calendar-header"] ? renderSlot(B.$slots, "calendar-header", {
              key: 0,
              day: Ae,
              index: ne
            }) : createCommentVNode("", true),
            B.$slots["calendar-header"] ? createCommentVNode("", true) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
              createTextVNode(toDisplayString(Ae), 1)
            ], 64))
          ], 8, Ur))), 128))
        ]),
        be[2] || (be[2] = createBaseVNode("div", { class: "dp__calendar_header_separator" }, null, -1)),
        createVNode(Transition, {
          name: A.value,
          css: !!B.transitions
        }, {
          default: withCtx(() => [
            M.value ? (openBlock(), createElementBlock("div", {
              key: 0,
              class: "dp__calendar",
              role: "rowgroup",
              onMouseleave: be[1] || (be[1] = (Ae) => le.value = false)
            }, [
              (openBlock(true), createElementBlock(Fragment, null, renderList(U.value, (Ae, ne) => (openBlock(), createElementBlock("div", {
                key: ne,
                class: "dp__calendar_row",
                role: "row"
              }, [
                B.weekNumbers ? (openBlock(), createElementBlock("div", Vr, [
                  createBaseVNode("div", jr, toDisplayString(x(Ae.days)), 1)
                ])) : createCommentVNode("", true),
                (openBlock(true), createElementBlock(Fragment, null, renderList(Ae.days, (r, E) => (openBlock(), createElementBlock("div", {
                  id: unref(Ha)(r.value),
                  ref_for: true,
                  ref: (K) => re(K, ne, E),
                  key: E + ne,
                  role: "gridcell",
                  class: "dp__calendar_item",
                  "aria-selected": (r.classData.dp__active_date || r.classData.dp__range_start || r.classData.dp__range_end) ?? void 0,
                  "aria-disabled": r.classData.dp__cell_disabled || void 0,
                  "aria-label": unref(d)?.day?.(r),
                  tabindex: !r.current && B.hideOffsetDates ? void 0 : 0,
                  "data-test-id": unref(Ha)(r.value),
                  onClick: withModifiers((K) => c(K, r), ["prevent"]),
                  onTouchend: (K) => c(K, r, false),
                  onKeydown: (K) => unref(qe)(K, () => B.$emit("select-date", r)),
                  onMouseenter: (K) => V(r, ne, E),
                  onMouseleave: (K) => S(r),
                  onMousedown: (K) => X(r),
                  onMouseup: be[0] || (be[0] = (K) => le.value = false)
                }, [
                  createBaseVNode("div", {
                    class: normalizeClass(["dp__cell_inner", r.classData])
                  }, [
                    B.$slots.day && Y.value(r) ? renderSlot(B.$slots, "day", {
                      key: 0,
                      day: +r.text,
                      date: r.value
                    }) : createCommentVNode("", true),
                    B.$slots.day ? createCommentVNode("", true) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
                      createTextVNode(toDisplayString(r.text), 1)
                    ], 64)),
                    r.marker && Y.value(r) ? (openBlock(), createElementBlock(Fragment, { key: 2 }, [
                      B.$slots.marker ? renderSlot(B.$slots, "marker", {
                        key: 0,
                        marker: r.marker,
                        day: +r.text,
                        date: r.value
                      }) : (openBlock(), createElementBlock("div", {
                        key: 1,
                        class: normalizeClass(ce.value(r.marker)),
                        style: normalizeStyle(r.marker.color ? { backgroundColor: r.marker.color } : {})
                      }, null, 6))
                    ], 64)) : createCommentVNode("", true),
                    pe.value(r.value) ? (openBlock(), createElementBlock("div", {
                      key: 3,
                      ref_for: true,
                      ref_key: "activeTooltip",
                      ref: z,
                      class: "dp__marker_tooltip",
                      style: normalizeStyle(F.value)
                    }, [
                      r.marker?.tooltip ? (openBlock(), createElementBlock("div", {
                        key: 0,
                        class: "dp__tooltip_content",
                        onClick: o
                      }, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList(r.marker.tooltip, (K, oe) => (openBlock(), createElementBlock("div", {
                          key: oe,
                          class: "dp__tooltip_text"
                        }, [
                          B.$slots["marker-tooltip"] ? renderSlot(B.$slots, "marker-tooltip", {
                            key: 0,
                            tooltip: K,
                            day: r.value
                          }) : createCommentVNode("", true),
                          B.$slots["marker-tooltip"] ? createCommentVNode("", true) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
                            createBaseVNode("div", {
                              class: "dp__tooltip_mark",
                              style: normalizeStyle(K.color ? { backgroundColor: K.color } : {})
                            }, null, 4),
                            createBaseVNode("div", null, toDisplayString(K.text), 1)
                          ], 64))
                        ]))), 128)),
                        createBaseVNode("div", {
                          class: "dp__arrow_bottom_tp",
                          style: normalizeStyle(L.value)
                        }, null, 4)
                      ])) : createCommentVNode("", true)
                    ], 4)) : createCommentVNode("", true)
                  ], 2)
                ], 40, Kr))), 128))
              ]))), 128))
            ], 32)) : createCommentVNode("", true)
          ]),
          _: 3
        }, 8, ["name", "css"])
      ], 2)
    ], 2));
  }
}), yn = (e) => Array.isArray(e), Qr = (e, t2, l, n) => {
  const a = ref([]), f = ref(/* @__PURE__ */ new Date()), i = ref(), g = () => k(e.isTextInputDate), { modelValue: d, calendars: P, time: v, today: R } = na(e, t2, g), {
    defaultedMultiCalendars: h2,
    defaultedStartTime: T,
    defaultedRange: F,
    defaultedConfig: _,
    defaultedTz: C,
    propDates: M,
    defaultedMultiDates: A
  } = Oe(e), { validateMonthYearInRange: m, isDisabled: z, isDateRangeAllowed: L, checkMinMaxRange: le } = Tt(e), { updateTimeValues: U, getSetDateTime: $, setTime: ee, assignStartTime: O, validateTime: J, disabledTimesConfig: ce } = Gn(e, v, d, n), pe = computed(
    () => (b) => P.value[b] ? P.value[b].month : 0
  ), p = computed(
    () => (b) => P.value[b] ? P.value[b].year : 0
  ), Y = (b) => !_.value.keepViewOnOffsetClick || b ? true : !i.value, te = (b, ae, ve, N = false) => {
    Y(N) && (P.value[b] || (P.value[b] = { month: 0, year: 0 }), P.value[b].month = cn(ae) ? P.value[b]?.month : ae, P.value[b].year = cn(ve) ? P.value[b]?.year : ve);
  }, y = () => {
    e.autoApply && t2("select-date");
  }, V = () => {
    T.value && O(T.value);
  };
  onMounted(() => {
    e.shadow || (d.value || (be(), V()), k(true), e.focusStartDate && e.startDate && be());
  });
  const S = computed(() => e.flow?.length && !e.partialFlow ? e.flowStep === e.flow.length : true), q = () => {
    e.autoApply && S.value && t2("auto-apply", e.partialFlow ? e.flowStep !== e.flow.length : false);
  }, k = (b = false) => {
    if (d.value)
      return Array.isArray(d.value) ? (a.value = d.value, c(b)) : re(d.value, b);
    if (h2.value.count && b && !e.startDate)
      return u(H(), b);
  }, se = () => Array.isArray(d.value) && F.value.enabled ? getMonth(d.value[0]) === getMonth(d.value[1] ?? d.value[0]) : false, u = (b = /* @__PURE__ */ new Date(), ae = false) => {
    if ((!h2.value.count || !h2.value.static || ae) && te(0, getMonth(b), getYear(b)), h2.value.count && (!d.value || se() || !h2.value.solo) && (!h2.value.solo || ae))
      for (let ve = 1; ve < h2.value.count; ve++) {
        const N = set(H(), { month: pe.value(ve - 1), year: p.value(ve - 1) }), ue = add(N, { months: 1 });
        P.value[ve] = { month: getMonth(ue), year: getYear(ue) };
      }
  }, re = (b, ae) => {
    u(b), ee("hours", getHours(b)), ee("minutes", getMinutes(b)), ee("seconds", getSeconds(b)), h2.value.count && ae && B();
  }, G = (b) => {
    if (h2.value.count) {
      if (h2.value.solo) return 0;
      const ae = getMonth(b[0]), ve = getMonth(b[1]);
      return Math.abs(ve - ae) < h2.value.count ? 0 : 1;
    }
    return 1;
  }, I = (b, ae) => {
    b[1] && F.value.showLastInRange ? u(b[G(b)], ae) : u(b[0], ae);
    const ve = (N, ue) => [
      N(b[0]),
      b[1] ? N(b[1]) : v[ue][1]
    ];
    ee("hours", ve(getHours, "hours")), ee("minutes", ve(getMinutes, "minutes")), ee("seconds", ve(getSeconds, "seconds"));
  }, x = (b, ae) => {
    if ((F.value.enabled || e.weekPicker) && !A.value.enabled)
      return I(b, ae);
    if (A.value.enabled && ae) {
      const ve = b[b.length - 1];
      return re(ve, ae);
    }
  }, c = (b) => {
    const ae = d.value;
    x(ae, b), h2.value.count && h2.value.solo && B();
  }, o = (b, ae) => {
    const ve = set(H(), { month: pe.value(ae), year: p.value(ae) }), N = b < 0 ? addMonths(ve, 1) : subMonths(ve, 1);
    m(getMonth(N), getYear(N), b < 0, e.preventMinMaxNavigation) && (te(ae, getMonth(N), getYear(N)), t2("update-month-year", { instance: ae, month: getMonth(N), year: getYear(N) }), h2.value.count && !h2.value.solo && X(ae), l());
  }, X = (b) => {
    for (let ae = b - 1; ae >= 0; ae--) {
      const ve = subMonths(set(H(), { month: pe.value(ae + 1), year: p.value(ae + 1) }), 1);
      te(ae, getMonth(ve), getYear(ve));
    }
    for (let ae = b + 1; ae <= h2.value.count - 1; ae++) {
      const ve = addMonths(set(H(), { month: pe.value(ae - 1), year: p.value(ae - 1) }), 1);
      te(ae, getMonth(ve), getYear(ve));
    }
  }, B = () => {
    if (Array.isArray(d.value) && d.value.length === 2) {
      const b = H(
        H(d.value[1] ? d.value[1] : addMonths(d.value[0], 1))
      ), [ae, ve] = [getMonth(d.value[0]), getYear(d.value[0])], [N, ue] = [getMonth(d.value[1]), getYear(d.value[1])];
      (ae !== N || ae === N && ve !== ue) && h2.value.solo && te(1, getMonth(b), getYear(b));
    } else d.value && !Array.isArray(d.value) && (te(0, getMonth(d.value), getYear(d.value)), u(H()));
  }, be = () => {
    e.startDate && (te(0, getMonth(H(e.startDate)), getYear(H(e.startDate))), h2.value.count && X(0));
  }, Ae = (b, ae) => {
    if (e.monthChangeOnScroll) {
      const ve = (/* @__PURE__ */ new Date()).getTime() - f.value.getTime(), N = Math.abs(b.deltaY);
      let ue = 500;
      N > 1 && (ue = 100), N > 100 && (ue = 0), ve > ue && (f.value = /* @__PURE__ */ new Date(), o(e.monthChangeOnScroll !== "inverse" ? -b.deltaY : b.deltaY, ae));
    }
  }, ne = (b, ae, ve = false) => {
    e.monthChangeOnArrows && e.vertical === ve && r(b, ae);
  }, r = (b, ae) => {
    o(b === "right" ? -1 : 1, ae);
  }, E = (b) => {
    if (M.value.markers)
      return ca(b.value, M.value.markers);
  }, K = (b, ae) => {
    switch (e.sixWeeks === true ? "append" : e.sixWeeks) {
      case "prepend":
        return [true, false];
      case "center":
        return [b == 0, true];
      case "fair":
        return [b == 0 || ae > b, true];
      case "append":
        return [false, false];
      default:
        return [false, false];
    }
  }, oe = (b, ae, ve, N) => {
    if (e.sixWeeks && b.length < 6) {
      const ue = 6 - b.length, me = (ae.getDay() + 7 - N) % 7, ct = 6 - (ve.getDay() + 7 - N) % 7, [Pt, $a] = K(me, ct);
      for (let Rt = 1; Rt <= ue; Rt++)
        if ($a ? !!(Rt % 2) == Pt : Pt) {
          const ra = b[0].days[0], Aa = ge(addDays(ra.value, -7), getMonth(ae));
          b.unshift({ days: Aa });
        } else {
          const ra = b[b.length - 1], Aa = ra.days[ra.days.length - 1], Xn = ge(addDays(Aa.value, 1), getMonth(ae));
          b.push({ days: Xn });
        }
    }
    return b;
  }, ge = (b, ae) => {
    const ve = H(b), N = [];
    for (let ue = 0; ue < 7; ue++) {
      const me = addDays(ve, ue), vt = getMonth(me) !== ae;
      N.push({
        text: e.hideOffsetDates && vt ? "" : me.getDate(),
        value: me,
        current: !vt,
        classData: {}
      });
    }
    return N;
  }, _e = (b, ae) => {
    const ve = [], N = new Date(ae, b), ue = new Date(ae, b + 1, 0), me = e.weekStart, vt = startOfWeek(N, { weekStartsOn: me }), ct = (Pt) => {
      const $a = ge(Pt, b);
      if (ve.push({ days: $a }), !ve[ve.length - 1].days.some(
        (Rt) => $e(Fe(Rt.value), Fe(ue))
      )) {
        const Rt = addDays(Pt, 7);
        ct(Rt);
      }
    };
    return ct(vt), oe(ve, N, ue, me);
  }, Ye = (b) => {
    const ae = Mt(H(b.value), v.hours, v.minutes, xe());
    t2("date-update", ae), A.value.enabled ? en(ae, d, A.value.limit) : d.value = ae, n(), nextTick().then(() => {
      q();
    });
  }, nt = (b) => F.value.noDisabledRange ? In(a.value[0], b).some((ve) => z(ve)) : false, St = () => {
    a.value = d.value ? d.value.slice() : [], a.value.length === 2 && !(F.value.fixedStart || F.value.fixedEnd) && (a.value = []);
  }, D = (b, ae) => {
    const ve = [
      H(b.value),
      addDays(H(b.value), +F.value.autoRange)
    ];
    L(ve) ? (ae && de(b.value), a.value = ve) : t2("invalid-date", b.value);
  }, de = (b) => {
    const ae = getMonth(H(b)), ve = getYear(H(b));
    if (te(0, ae, ve), h2.value.count > 0)
      for (let N = 1; N < h2.value.count; N++) {
        const ue = El(
          set(H(b), { year: p.value(N - 1), month: pe.value(N - 1) })
        );
        te(N, ue.month, ue.year);
      }
  }, fe = (b) => {
    if (nt(b.value) || !le(b.value, d.value, F.value.fixedStart ? 0 : 1))
      return t2("invalid-date", b.value);
    a.value = Vn(H(b.value), d, t2, F);
  }, ht = (b, ae) => {
    if (St(), F.value.autoRange) return D(b, ae);
    if (F.value.fixedStart || F.value.fixedEnd) return fe(b);
    a.value[0] ? le(H(b.value), d.value) && !nt(b.value) ? Be(H(b.value), H(a.value[0])) ? (a.value.unshift(H(b.value)), t2("range-end", a.value[0])) : (a.value[1] = H(b.value), t2("range-end", a.value[1])) : (e.autoApply && t2("auto-apply-invalid", b.value), t2("invalid-date", b.value)) : (a.value[0] = H(b.value), t2("range-start", a.value[0]));
  }, xe = (b = true) => e.enableSeconds ? Array.isArray(v.seconds) ? b ? v.seconds[0] : v.seconds[1] : v.seconds : 0, Xe = (b) => {
    a.value[b] = Mt(
      a.value[b],
      v.hours[b],
      v.minutes[b],
      xe(b !== 1)
    );
  }, ya = () => {
    a.value[0] && a.value[1] && +a.value?.[0] > +a.value?.[1] && (a.value.reverse(), t2("range-start", a.value[0]), t2("range-end", a.value[1]));
  }, ha = () => {
    a.value.length && (a.value[0] && !a.value[1] ? Xe(0) : (Xe(0), Xe(1), n()), ya(), d.value = a.value.slice(), pa(a.value, t2, e.autoApply, e.modelAuto));
  }, la = (b, ae = false) => {
    if (z(b.value) || !b.current && e.hideOffsetDates) return t2("invalid-date", b.value);
    if (i.value = JSON.parse(JSON.stringify(b)), !F.value.enabled) return Ye(b);
    yn(v.hours) && yn(v.minutes) && !A.value.enabled && (ht(b, ae), ha());
  }, ba = (b, ae) => {
    te(b, ae.month, ae.year, true), h2.value.count && !h2.value.solo && X(b), t2("update-month-year", { instance: b, month: ae.month, year: ae.year }), l(h2.value.solo ? b : void 0);
    const ve = e.flow?.length ? e.flow[e.flowStep] : void 0;
    !ae.fromNav && (ve === je.month || ve === je.year) && n();
  }, ka = (b, ae) => {
    Un({
      value: b,
      modelValue: d,
      range: F.value.enabled,
      timezone: ae ? void 0 : C.value.timezone
    }), y(), e.multiCalendars && nextTick().then(() => k(true));
  }, wa = () => {
    const b = Qa(H(), C.value);
    !F.value.enabled && !A.value.enabled ? d.value = b : d.value && Array.isArray(d.value) && d.value[0] ? A.value.enabled ? d.value = [...d.value, b] : d.value = Be(b, d.value[0]) ? [b, d.value[0]] : [d.value[0], b] : d.value = [b], y();
  }, Da = () => {
    if (Array.isArray(d.value))
      if (A.value.enabled) {
        const b = Ma();
        d.value[d.value.length - 1] = $(b);
      } else
        d.value = d.value.map((b, ae) => b && $(b, ae));
    else
      d.value = $(d.value);
    t2("time-update");
  }, Ma = () => Array.isArray(d.value) && d.value.length ? d.value[d.value.length - 1] : null;
  return {
    calendars: P,
    modelValue: d,
    month: pe,
    year: p,
    time: v,
    disabledTimesConfig: ce,
    today: R,
    validateTime: J,
    getCalendarDays: _e,
    getMarker: E,
    handleScroll: Ae,
    handleSwipe: r,
    handleArrow: ne,
    selectDate: la,
    updateMonthYear: ba,
    presetDate: ka,
    selectCurrentDate: wa,
    updateTime: (b, ae = true, ve = false) => {
      U(b, ae, ve, Da);
    },
    assignMonthAndYear: u,
    setStartTime: V
  };
}, qr = { key: 0 }, Xr = /* @__PURE__ */ defineComponent({
  __name: "DatePicker",
  props: {
    ...dt
  },
  emits: [
    "tooltip-open",
    "tooltip-close",
    "mount",
    "update:internal-model-value",
    "update-flow-step",
    "reset-flow",
    "auto-apply",
    "focus-menu",
    "select-date",
    "range-start",
    "range-end",
    "invalid-fixed-range",
    "time-update",
    "am-pm-change",
    "time-picker-open",
    "time-picker-close",
    "recalculate-position",
    "update-month-year",
    "auto-apply-invalid",
    "date-update",
    "invalid-date",
    "overlay-toggle"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, {
      calendars: f,
      month: i,
      year: g,
      modelValue: d,
      time: P,
      disabledTimesConfig: v,
      today: R,
      validateTime: h2,
      getCalendarDays: T,
      getMarker: F,
      handleArrow: _,
      handleScroll: C,
      handleSwipe: M,
      selectDate: A,
      updateMonthYear: m,
      presetDate: z,
      selectCurrentDate: L,
      updateTime: le,
      assignMonthAndYear: U,
      setStartTime: $
    } = Qr(a, n, se, u), ee = useSlots(), { setHoverDate: O, getDayClassData: J, clearHoverDate: ce } = mo(d, a), { defaultedMultiCalendars: pe } = Oe(a), p = ref([]), Y = ref([]), te = ref(null), y = et(ee, "calendar"), V = et(ee, "monthYear"), S = et(ee, "timePicker"), q = (ne) => {
      a.shadow || n("mount", ne);
    };
    watch(
      f,
      () => {
        a.shadow || setTimeout(() => {
          n("recalculate-position");
        }, 0);
      },
      { deep: true }
    ), watch(
      pe,
      (ne, r) => {
        ne.count - r.count > 0 && U();
      },
      { deep: true }
    );
    const k = computed(() => (ne) => T(i.value(ne), g.value(ne)).map((r) => ({
      ...r,
      days: r.days.map((E) => (E.marker = F(E), E.classData = J(E), E))
    })));
    function se(ne) {
      ne || ne === 0 ? Y.value[ne]?.triggerTransition(i.value(ne), g.value(ne)) : Y.value.forEach((r, E) => r.triggerTransition(i.value(E), g.value(E)));
    }
    function u() {
      n("update-flow-step");
    }
    const re = (ne, r = false) => {
      A(ne, r), a.spaceConfirm && n("select-date");
    }, G = (ne, r, E = 0) => {
      p.value[E]?.toggleMonthPicker(ne, r);
    }, I = (ne, r, E = 0) => {
      p.value[E]?.toggleYearPicker(ne, r);
    }, x = (ne, r, E) => {
      te.value?.toggleTimePicker(ne, r, E);
    }, c = (ne, r) => {
      if (!a.range) {
        const E = d.value ? d.value : R, K = r ? new Date(r) : E, oe = ne ? startOfWeek(K, { weekStartsOn: 1 }) : endOfWeek(K, { weekStartsOn: 1 });
        A({
          value: oe,
          current: getMonth(K) === i.value(0),
          text: "",
          classData: {}
        }), document.getElementById(Ha(oe))?.focus();
      }
    }, o = (ne) => {
      p.value[0]?.handleMonthYearChange(ne, true);
    }, X = (ne) => {
      m(0, { month: i.value(0), year: g.value(0) + (ne ? 1 : -1), fromNav: true });
    }, B = (ne, r) => {
      ne === je.time && n(`time-picker-${r ? "open" : "close"}`), n("overlay-toggle", { open: r, overlay: ne });
    }, be = (ne) => {
      n("overlay-toggle", { open: false, overlay: ne }), n("focus-menu");
    };
    return t2({
      clearHoverDate: ce,
      presetDate: z,
      selectCurrentDate: L,
      toggleMonthPicker: G,
      toggleYearPicker: I,
      toggleTimePicker: x,
      handleArrow: _,
      updateMonthYear: m,
      getSidebarProps: () => ({
        modelValue: d,
        month: i,
        year: g,
        time: P,
        updateTime: le,
        updateMonthYear: m,
        selectDate: A,
        presetDate: z
      }),
      changeMonth: o,
      changeYear: X,
      selectWeekDate: c,
      setStartTime: $
    }), (ne, r) => (openBlock(), createElementBlock(Fragment, null, [
      createVNode(ma, {
        "multi-calendars": unref(pe).count,
        collapse: ne.collapse,
        "is-mobile": ne.isMobile
      }, {
        default: withCtx(({ instance: E, index: K }) => [
          ne.disableMonthYearSelect ? createCommentVNode("", true) : (openBlock(), createBlock(zr, mergeProps({
            key: 0,
            ref: (oe) => {
              oe && (p.value[K] = oe);
            },
            months: unref(Pn)(ne.formatLocale, ne.locale, ne.monthNameFormat),
            years: unref(qa)(ne.yearRange, ne.locale, ne.reverseYears),
            month: unref(i)(E),
            year: unref(g)(E),
            instance: E
          }, ne.$props, {
            onMount: r[0] || (r[0] = (oe) => q(unref(Ot).header)),
            onResetFlow: r[1] || (r[1] = (oe) => ne.$emit("reset-flow")),
            onUpdateMonthYear: (oe) => unref(m)(E, oe),
            onOverlayClosed: be,
            onOverlayOpened: r[2] || (r[2] = (oe) => ne.$emit("overlay-toggle", { open: true, overlay: oe }))
          }), createSlots({ _: 2 }, [
            renderList(unref(V), (oe, ge) => ({
              name: oe,
              fn: withCtx((_e) => [
                renderSlot(ne.$slots, oe, normalizeProps(guardReactiveProps(_e)))
              ])
            }))
          ]), 1040, ["months", "years", "month", "year", "instance", "onUpdateMonthYear"])),
          createVNode(Gr, mergeProps({
            ref: (oe) => {
              oe && (Y.value[K] = oe);
            },
            "mapped-dates": k.value(E),
            month: unref(i)(E),
            year: unref(g)(E),
            instance: E
          }, ne.$props, {
            onSelectDate: (oe) => unref(A)(oe, E !== 1),
            onHandleSpace: (oe) => re(oe, E !== 1),
            onSetHoverDate: r[3] || (r[3] = (oe) => unref(O)(oe)),
            onHandleScroll: (oe) => unref(C)(oe, E),
            onHandleSwipe: (oe) => unref(M)(oe, E),
            onMount: r[4] || (r[4] = (oe) => q(unref(Ot).calendar)),
            onResetFlow: r[5] || (r[5] = (oe) => ne.$emit("reset-flow")),
            onTooltipOpen: r[6] || (r[6] = (oe) => ne.$emit("tooltip-open", oe)),
            onTooltipClose: r[7] || (r[7] = (oe) => ne.$emit("tooltip-close", oe))
          }), createSlots({ _: 2 }, [
            renderList(unref(y), (oe, ge) => ({
              name: oe,
              fn: withCtx((_e) => [
                renderSlot(ne.$slots, oe, normalizeProps(guardReactiveProps({ ..._e })))
              ])
            }))
          ]), 1040, ["mapped-dates", "month", "year", "instance", "onSelectDate", "onHandleSpace", "onHandleScroll", "onHandleSwipe"])
        ]),
        _: 3
      }, 8, ["multi-calendars", "collapse", "is-mobile"]),
      ne.enableTimePicker ? (openBlock(), createElementBlock("div", qr, [
        ne.$slots["time-picker"] ? renderSlot(ne.$slots, "time-picker", normalizeProps(mergeProps({ key: 0 }, { time: unref(P), updateTime: unref(le) }))) : (openBlock(), createBlock(Kn, mergeProps({
          key: 1,
          ref_key: "timePickerRef",
          ref: te
        }, ne.$props, {
          hours: unref(P).hours,
          minutes: unref(P).minutes,
          seconds: unref(P).seconds,
          "internal-model-value": ne.internalModelValue,
          "disabled-times-config": unref(v),
          "validate-time": unref(h2),
          onMount: r[8] || (r[8] = (E) => q(unref(Ot).timePicker)),
          "onUpdate:hours": r[9] || (r[9] = (E) => unref(le)(E)),
          "onUpdate:minutes": r[10] || (r[10] = (E) => unref(le)(E, false)),
          "onUpdate:seconds": r[11] || (r[11] = (E) => unref(le)(E, false, true)),
          onResetFlow: r[12] || (r[12] = (E) => ne.$emit("reset-flow")),
          onOverlayClosed: r[13] || (r[13] = (E) => B(E, false)),
          onOverlayOpened: r[14] || (r[14] = (E) => B(E, true)),
          onAmPmChange: r[15] || (r[15] = (E) => ne.$emit("am-pm-change", E))
        }), createSlots({ _: 2 }, [
          renderList(unref(S), (E, K) => ({
            name: E,
            fn: withCtx((oe) => [
              renderSlot(ne.$slots, E, normalizeProps(guardReactiveProps(oe)))
            ])
          }))
        ]), 1040, ["hours", "minutes", "seconds", "internal-model-value", "disabled-times-config", "validate-time"]))
      ])) : createCommentVNode("", true)
    ], 64));
  }
}), Jr = (e, t2) => {
  const l = ref(), {
    defaultedMultiCalendars: n,
    defaultedConfig: a,
    defaultedHighlight: f,
    defaultedRange: i,
    propDates: g,
    defaultedFilters: d,
    defaultedMultiDates: P
  } = Oe(e), { modelValue: v, year: R, month: h2, calendars: T } = na(e, t2), { isDisabled: F } = Tt(e), { selectYear: _, groupedYears: C, showYearPicker: M, isDisabled: A, toggleYearPicker: m, handleYearSelect: z, handleYear: L } = jn({
    modelValue: v,
    multiCalendars: n,
    range: i,
    highlight: f,
    calendars: T,
    propDates: g,
    month: h2,
    year: R,
    filters: d,
    props: e,
    emit: t2
  }), le = (y, V) => [y, V].map((S) => format(S, "MMMM", { locale: e.formatLocale })).join("-"), U = computed(() => (y) => v.value ? Array.isArray(v.value) ? v.value.some((V) => isSameQuarter(y, V)) : isSameQuarter(v.value, y) : false), $ = (y) => {
    if (i.value.enabled) {
      if (Array.isArray(v.value)) {
        const V = $e(y, v.value[0]) || $e(y, v.value[1]);
        return xt(v.value, l.value, y) && !V;
      }
      return false;
    }
    return false;
  }, ee = (y, V) => y.quarter === getQuarter(V) && y.year === getYear(V), O = (y) => typeof f.value == "function" ? f.value({ quarter: getQuarter(y), year: getYear(y) }) : !!f.value.quarters.find((V) => ee(V, y)), J = computed(() => (y) => {
    const V = set(/* @__PURE__ */ new Date(), { year: R.value(y) });
    return eachQuarterOfInterval({
      start: startOfYear(V),
      end: endOfYear(V)
    }).map((S) => {
      const q = startOfQuarter(S), k = endOfQuarter(S), se = F(S), u = $(q), re = O(q);
      return {
        text: le(q, k),
        value: q,
        active: U.value(q),
        highlighted: re,
        disabled: se,
        isBetween: u
      };
    });
  }), ce = (y) => {
    en(y, v, P.value.limit), t2("auto-apply", true);
  }, pe = (y) => {
    v.value = tn(v, y, t2), pa(v.value, t2, e.autoApply, e.modelAuto);
  }, p = (y) => {
    v.value = y, t2("auto-apply");
  };
  return {
    defaultedConfig: a,
    defaultedMultiCalendars: n,
    groupedYears: C,
    year: R,
    isDisabled: A,
    quarters: J,
    showYearPicker: M,
    modelValue: v,
    setHoverDate: (y) => {
      l.value = y;
    },
    selectYear: _,
    selectQuarter: (y, V, S) => {
      if (!S)
        return T.value[V].month = getMonth(endOfQuarter(y)), P.value.enabled ? ce(y) : i.value.enabled ? pe(y) : p(y);
    },
    toggleYearPicker: m,
    handleYearSelect: z,
    handleYear: L
  };
}, Zr = { class: "dp--quarter-items" }, xr = ["data-test-id", "disabled", "onClick", "onMouseover"], eo = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "QuarterPicker",
  props: {
    ...dt
  },
  emits: [
    "update:internal-model-value",
    "reset-flow",
    "overlay-closed",
    "auto-apply",
    "range-start",
    "range-end",
    "overlay-toggle",
    "update-month-year"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, f = useSlots(), i = et(f, "yearMode"), {
      defaultedMultiCalendars: g,
      defaultedConfig: d,
      groupedYears: P,
      year: v,
      isDisabled: R,
      quarters: h2,
      modelValue: T,
      showYearPicker: F,
      setHoverDate: _,
      selectQuarter: C,
      toggleYearPicker: M,
      handleYearSelect: A,
      handleYear: m
    } = Jr(a, n);
    return t2({ getSidebarProps: () => ({
      modelValue: T,
      year: v,
      selectQuarter: C,
      handleYearSelect: A,
      handleYear: m
    }) }), (L, le) => (openBlock(), createBlock(ma, {
      "multi-calendars": unref(g).count,
      collapse: L.collapse,
      stretch: "",
      "is-mobile": L.isMobile
    }, {
      default: withCtx(({ instance: U }) => [
        createBaseVNode("div", {
          class: "dp-quarter-picker-wrap",
          style: normalizeStyle({ minHeight: `${unref(d).modeHeight}px` })
        }, [
          L.$slots["top-extra"] ? renderSlot(L.$slots, "top-extra", {
            key: 0,
            value: L.internalModelValue
          }) : createCommentVNode("", true),
          createBaseVNode("div", null, [
            createVNode(Wn, mergeProps(L.$props, {
              items: unref(P)(U),
              instance: U,
              "show-year-picker": unref(F)[U],
              year: unref(v)(U),
              "is-disabled": ($) => unref(R)(U, $),
              onHandleYear: ($) => unref(m)(U, $),
              onYearSelect: ($) => unref(A)($, U),
              onToggleYearPicker: ($) => unref(M)(U, $?.flow, $?.show)
            }), createSlots({ _: 2 }, [
              renderList(unref(i), ($, ee) => ({
                name: $,
                fn: withCtx((O) => [
                  renderSlot(L.$slots, $, normalizeProps(guardReactiveProps(O)))
                ])
              }))
            ]), 1040, ["items", "instance", "show-year-picker", "year", "is-disabled", "onHandleYear", "onYearSelect", "onToggleYearPicker"])
          ]),
          createBaseVNode("div", Zr, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(unref(h2)(U), ($, ee) => (openBlock(), createElementBlock("div", { key: ee }, [
              createBaseVNode("button", {
                type: "button",
                class: normalizeClass(["dp--qr-btn", {
                  "dp--qr-btn-active": $.active,
                  "dp--qr-btn-between": $.isBetween,
                  "dp--qr-btn-disabled": $.disabled,
                  "dp--highlighted": $.highlighted
                }]),
                "data-test-id": $.value,
                disabled: $.disabled,
                onClick: (O) => unref(C)($.value, U, $.disabled),
                onMouseover: (O) => unref(_)($.value)
              }, [
                L.$slots.quarter ? renderSlot(L.$slots, "quarter", {
                  key: 0,
                  value: $.value,
                  text: $.text
                }) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
                  createTextVNode(toDisplayString($.text), 1)
                ], 64))
              ], 42, xr)
            ]))), 128))
          ])
        ], 4)
      ]),
      _: 3
    }, 8, ["multi-calendars", "collapse", "is-mobile"]));
  }
}), Qn = (e, t2) => {
  const l = ref(0);
  onMounted(() => {
    n(), window.addEventListener("resize", n, { passive: true });
  }), onUnmounted(() => {
    window.removeEventListener("resize", n);
  });
  const n = () => {
    l.value = window.document.documentElement.clientWidth;
  };
  return {
    isMobile: computed(
      () => l.value <= e.value.mobileBreakpoint && !t2 ? true : void 0
    )
  };
}, to = ["id", "tabindex", "role", "aria-label"], ao = {
  key: 0,
  class: "dp--menu-load-container"
}, no = {
  key: 1,
  class: "dp--menu-header"
}, lo = ["data-dp-mobile"], ro = {
  key: 0,
  class: "dp__sidebar_left"
}, oo = ["data-dp-mobile"], so = ["data-test-id", "data-dp-mobile", "onClick", "onKeydown"], uo = {
  key: 2,
  class: "dp__sidebar_right"
}, io = {
  key: 3,
  class: "dp__action_extra"
}, hn = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "DatepickerMenu",
  props: {
    ...va,
    shadow: { type: Boolean, default: false },
    openOnTop: { type: Boolean, default: false },
    internalModelValue: { type: [Date, Array], default: null },
    noOverlayFocus: { type: Boolean, default: false },
    collapse: { type: Boolean, default: false },
    getInputRect: { type: Function, default: () => ({}) },
    isTextInputDate: { type: Boolean, default: false }
  },
  emits: [
    "close-picker",
    "select-date",
    "auto-apply",
    "time-update",
    "flow-step",
    "update-month-year",
    "invalid-select",
    "update:internal-model-value",
    "recalculate-position",
    "invalid-fixed-range",
    "tooltip-open",
    "tooltip-close",
    "time-picker-open",
    "time-picker-close",
    "am-pm-change",
    "range-start",
    "range-end",
    "auto-apply-invalid",
    "date-update",
    "invalid-date",
    "overlay-toggle",
    "menu-blur"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, f = ref(null), i = computed(() => {
      const { openOnTop: D, ...de } = a;
      return {
        ...de,
        isMobile: C.value,
        flowStep: pe.value,
        menuWrapRef: f.value
      };
    }), { setMenuFocused: g, setShiftKey: d, control: P } = Hn(), v = useSlots(), { defaultedTextInput: R, defaultedInline: h2, defaultedConfig: T, defaultedUI: F, handleEventPropagation: _ } = Oe(a), { isMobile: C } = Qn(T, a.shadow), M = ref(null), A = ref(0), m = ref(null), z = ref(false), L = ref(null), le = ref(false), U = (D) => {
      le.value = true, T.value.allowPreventDefault && D.preventDefault(), Dt(D, T.value, true);
    };
    onMounted(() => {
      if (!a.shadow) {
        z.value = true, $(), window.addEventListener("resize", $);
        const D = Le(f);
        D && !R.value.enabled && !h2.value.enabled && (g(true), q()), D && (D.addEventListener("pointerdown", U), D.addEventListener("mousedown", U));
      }
      document.addEventListener("mousedown", nt);
    }), onUnmounted(() => {
      window.removeEventListener("resize", $), document.removeEventListener("mousedown", nt);
      const D = Le(f);
      D && (D.removeEventListener("pointerdown", U), D.removeEventListener("mousedown", U));
    });
    const $ = () => {
      const D = Le(m);
      D && (A.value = D.getBoundingClientRect().width);
    }, { arrowRight: ee, arrowLeft: O, arrowDown: J, arrowUp: ce } = At(), { flowStep: pe, updateFlowStep: p, childMount: Y, resetFlow: te, handleFlow: y } = po(a, n, L), V = computed(() => a.monthPicker ? hr : a.yearPicker ? kr : a.timePicker ? Yr : a.quarterPicker ? eo : Xr), S = computed(() => {
      if (T.value.arrowLeft) return T.value.arrowLeft;
      const D = f.value?.getBoundingClientRect(), de = a.getInputRect();
      return de?.width < A?.value && de?.left <= (D?.left ?? 0) ? `${de?.width / 2}px` : de?.right >= (D?.right ?? 0) && de?.width < A?.value ? `${A?.value - de?.width / 2}px` : "50%";
    }), q = () => {
      const D = Le(f);
      D && D.focus({ preventScroll: true });
    }, k = computed(() => L.value?.getSidebarProps() || {}), se = () => {
      a.openOnTop && n("recalculate-position");
    }, u = et(v, "action"), re = computed(() => a.monthPicker || a.yearPicker ? et(v, "monthYear") : a.timePicker ? et(v, "timePicker") : et(v, "shared")), G = computed(() => a.openOnTop ? "dp__arrow_bottom" : "dp__arrow_top"), I = computed(() => ({
      dp__menu_disabled: a.disabled,
      dp__menu_readonly: a.readonly,
      "dp-menu-loading": a.loading
    })), x = computed(
      () => ({
        dp__menu: true,
        dp__menu_index: !h2.value.enabled,
        dp__relative: h2.value.enabled,
        ...F.value.menu ?? {}
      })
    ), c = (D) => {
      Dt(D, T.value, true);
    }, o = (D) => {
      a.escClose && (n("close-picker"), _(D));
    }, X = (D) => {
      if (a.arrowNavigation) {
        if (D === Qe.up) return ce();
        if (D === Qe.down) return J();
        if (D === Qe.left) return O();
        if (D === Qe.right) return ee();
      } else D === Qe.left || D === Qe.up ? r("handleArrow", Qe.left, 0, D === Qe.up) : r("handleArrow", Qe.right, 0, D === Qe.down);
    }, B = (D) => {
      d(D.shiftKey), !a.disableMonthYearSelect && D.code === Re.tab && D.target.classList.contains("dp__menu") && P.value.shiftKeyInMenu && (D.preventDefault(), Dt(D, T.value, true), n("close-picker"));
    }, be = () => {
      q(), n("time-picker-close");
    }, Ae = (D) => {
      L.value?.toggleTimePicker(false, false), L.value?.toggleMonthPicker(false, false, D), L.value?.toggleYearPicker(false, false, D);
    }, ne = (D, de = 0) => D === "month" ? L.value?.toggleMonthPicker(false, true, de) : D === "year" ? L.value?.toggleYearPicker(false, true, de) : D === "time" ? L.value?.toggleTimePicker(true, false) : Ae(de), r = (D, ...de) => {
      L.value?.[D] && L.value?.[D](...de);
    }, E = () => {
      r("selectCurrentDate");
    }, K = (D, de) => {
      r("presetDate", toValue(D), de);
    }, oe = () => {
      r("clearHoverDate");
    }, ge = (D, de) => {
      r("updateMonthYear", D, de);
    }, _e = (D, de) => {
      D.preventDefault(), X(de);
    }, Ye = (D) => {
      if (B(D), D.key === Re.home || D.key === Re.end)
        return r(
          "selectWeekDate",
          D.key === Re.home,
          D.target.getAttribute("id")
        );
      switch ((D.key === Re.pageUp || D.key === Re.pageDown) && (D.shiftKey ? (r("changeYear", D.key === Re.pageUp), Fa(f.value, "overlay-year")?.focus()) : (r("changeMonth", D.key === Re.pageUp), Fa(f.value, D.key === Re.pageUp ? "action-prev" : "action-next")?.focus()), D.target.getAttribute("id") && f.value?.focus({ preventScroll: true })), D.key) {
        case Re.esc:
          return o(D);
        case Re.arrowLeft:
          return _e(D, Qe.left);
        case Re.arrowRight:
          return _e(D, Qe.right);
        case Re.arrowUp:
          return _e(D, Qe.up);
        case Re.arrowDown:
          return _e(D, Qe.down);
        default:
          return;
      }
    }, nt = (D) => {
      h2.value.enabled && !h2.value.input && !f.value?.contains(D.target) && le.value && (le.value = false, n("menu-blur"));
    };
    return t2({
      updateMonthYear: ge,
      switchView: ne,
      handleFlow: y,
      onValueCleared: () => {
        L.value?.setStartTime?.();
      }
    }), (D, de) => (openBlock(), createElementBlock("div", {
      id: D.uid ? `dp-menu-${D.uid}` : void 0,
      ref_key: "dpMenuRef",
      ref: f,
      tabindex: unref(h2).enabled ? void 0 : "0",
      role: unref(h2).enabled ? void 0 : "dialog",
      "aria-label": D.ariaLabels?.menu,
      class: normalizeClass(x.value),
      style: normalizeStyle({ "--dp-arrow-left": S.value }),
      onMouseleave: oe,
      onClick: c,
      onKeydown: Ye
    }, [
      (D.disabled || D.readonly) && unref(h2).enabled || D.loading ? (openBlock(), createElementBlock("div", {
        key: 0,
        class: normalizeClass(I.value)
      }, [
        D.loading ? (openBlock(), createElementBlock("div", ao, [...de[19] || (de[19] = [
          createBaseVNode("span", { class: "dp--menu-loader" }, null, -1)
        ])])) : createCommentVNode("", true)
      ], 2)) : createCommentVNode("", true),
      D.$slots["menu-header"] ? (openBlock(), createElementBlock("div", no, [
        renderSlot(D.$slots, "menu-header")
      ])) : createCommentVNode("", true),
      !unref(h2).enabled && !D.teleportCenter ? (openBlock(), createElementBlock("div", {
        key: 2,
        class: normalizeClass(G.value)
      }, null, 2)) : createCommentVNode("", true),
      createBaseVNode("div", {
        ref_key: "innerMenuRef",
        ref: m,
        class: normalizeClass({
          dp__menu_content_wrapper: D.presetDates?.length || !!D.$slots["left-sidebar"] || !!D.$slots["right-sidebar"],
          "dp--menu-content-wrapper-collapsed": e.collapse && (D.presetDates?.length || !!D.$slots["left-sidebar"] || !!D.$slots["right-sidebar"])
        }),
        "data-dp-mobile": unref(C),
        style: normalizeStyle({ "--dp-menu-width": `${A.value}px` })
      }, [
        D.$slots["left-sidebar"] ? (openBlock(), createElementBlock("div", ro, [
          renderSlot(D.$slots, "left-sidebar", normalizeProps(guardReactiveProps(k.value)))
        ])) : createCommentVNode("", true),
        D.presetDates.length ? (openBlock(), createElementBlock("div", {
          key: 1,
          class: normalizeClass({ "dp--preset-dates-collapsed": e.collapse, "dp--preset-dates": true }),
          "data-dp-mobile": unref(C)
        }, [
          (openBlock(true), createElementBlock(Fragment, null, renderList(D.presetDates, (fe, ht) => (openBlock(), createElementBlock(Fragment, { key: ht }, [
            fe.slot ? renderSlot(D.$slots, fe.slot, {
              key: 0,
              presetDate: K,
              label: fe.label,
              value: fe.value
            }) : (openBlock(), createElementBlock("button", {
              key: 1,
              type: "button",
              style: normalizeStyle(fe.style || {}),
              class: normalizeClass(["dp__btn dp--preset-range", { "dp--preset-range-collapsed": e.collapse }]),
              "data-test-id": fe.testId ?? void 0,
              "data-dp-mobile": unref(C),
              onClick: withModifiers((xe) => K(fe.value, fe.noTz), ["prevent"]),
              onKeydown: (xe) => unref(qe)(xe, () => K(fe.value, fe.noTz), true)
            }, toDisplayString(fe.label), 47, so))
          ], 64))), 128))
        ], 10, oo)) : createCommentVNode("", true),
        createBaseVNode("div", {
          ref_key: "calendarWrapperRef",
          ref: M,
          class: "dp__instance_calendar",
          role: "document"
        }, [
          (openBlock(), createBlock(resolveDynamicComponent(V.value), mergeProps({
            ref_key: "dynCmpRef",
            ref: L
          }, i.value, {
            "flow-step": unref(pe),
            onMount: unref(Y),
            onUpdateFlowStep: unref(p),
            onResetFlow: unref(te),
            onFocusMenu: q,
            onSelectDate: de[0] || (de[0] = (fe) => D.$emit("select-date")),
            onDateUpdate: de[1] || (de[1] = (fe) => D.$emit("date-update", fe)),
            onTooltipOpen: de[2] || (de[2] = (fe) => D.$emit("tooltip-open", fe)),
            onTooltipClose: de[3] || (de[3] = (fe) => D.$emit("tooltip-close", fe)),
            onAutoApply: de[4] || (de[4] = (fe) => D.$emit("auto-apply", fe)),
            onRangeStart: de[5] || (de[5] = (fe) => D.$emit("range-start", fe)),
            onRangeEnd: de[6] || (de[6] = (fe) => D.$emit("range-end", fe)),
            onInvalidFixedRange: de[7] || (de[7] = (fe) => D.$emit("invalid-fixed-range", fe)),
            onTimeUpdate: de[8] || (de[8] = (fe) => D.$emit("time-update")),
            onAmPmChange: de[9] || (de[9] = (fe) => D.$emit("am-pm-change", fe)),
            onTimePickerOpen: de[10] || (de[10] = (fe) => D.$emit("time-picker-open", fe)),
            onTimePickerClose: be,
            onRecalculatePosition: se,
            onUpdateMonthYear: de[11] || (de[11] = (fe) => D.$emit("update-month-year", fe)),
            onAutoApplyInvalid: de[12] || (de[12] = (fe) => D.$emit("auto-apply-invalid", fe)),
            onInvalidDate: de[13] || (de[13] = (fe) => D.$emit("invalid-date", fe)),
            onOverlayToggle: de[14] || (de[14] = (fe) => D.$emit("overlay-toggle", fe)),
            "onUpdate:internalModelValue": de[15] || (de[15] = (fe) => D.$emit("update:internal-model-value", fe))
          }), createSlots({ _: 2 }, [
            renderList(re.value, (fe, ht) => ({
              name: fe,
              fn: withCtx((xe) => [
                renderSlot(D.$slots, fe, normalizeProps(guardReactiveProps({ ...xe })))
              ])
            }))
          ]), 1040, ["flow-step", "onMount", "onUpdateFlowStep", "onResetFlow"]))
        ], 512),
        D.$slots["right-sidebar"] ? (openBlock(), createElementBlock("div", uo, [
          renderSlot(D.$slots, "right-sidebar", normalizeProps(guardReactiveProps(k.value)))
        ])) : createCommentVNode("", true),
        D.$slots["action-extra"] ? (openBlock(), createElementBlock("div", io, [
          D.$slots["action-extra"] ? renderSlot(D.$slots, "action-extra", {
            key: 0,
            selectCurrentDate: E
          }) : createCommentVNode("", true)
        ])) : createCommentVNode("", true)
      ], 14, lo),
      !D.autoApply || unref(T).keepActionRow ? (openBlock(), createBlock(ir, mergeProps({
        key: 3,
        "menu-mount": z.value
      }, i.value, {
        "calendar-width": A.value,
        onClosePicker: de[16] || (de[16] = (fe) => D.$emit("close-picker")),
        onSelectDate: de[17] || (de[17] = (fe) => D.$emit("select-date")),
        onInvalidSelect: de[18] || (de[18] = (fe) => D.$emit("invalid-select")),
        onSelectNow: E
      }), createSlots({ _: 2 }, [
        renderList(unref(u), (fe, ht) => ({
          name: fe,
          fn: withCtx((xe) => [
            renderSlot(D.$slots, fe, normalizeProps(guardReactiveProps({ ...xe })))
          ])
        }))
      ]), 1040, ["menu-mount", "calendar-width"])) : createCommentVNode("", true)
    ], 46, to));
  }
});
var Et = /* @__PURE__ */ ((e) => (e.center = "center", e.left = "left", e.right = "right", e))(Et || {});
const co = ({
  menuRef: e,
  menuRefInner: t2,
  inputRef: l,
  pickerWrapperRef: n,
  inline: a,
  emit: f,
  props: i,
  slots: g
}) => {
  const { defaultedConfig: d } = Oe(i), P = ref({}), v = ref(false), R = ref({
    top: "0",
    left: "0"
  }), h$1 = ref(false), T = toRef(i, "teleportCenter");
  watch(T, () => {
    R.value = JSON.parse(JSON.stringify({})), L();
  });
  const F = (y) => {
    if (i.teleport) {
      const V = y.getBoundingClientRect();
      return {
        left: V.left + window.scrollX,
        top: V.top + window.scrollY
      };
    }
    return { top: 0, left: 0 };
  }, _ = (y, V) => {
    R.value.left = `${y + V - P.value.width}px`;
  }, C = (y) => {
    R.value.left = `${y}px`;
  }, M = (y, V) => {
    i.position === Et.left && C(y), i.position === Et.right && _(y, V), i.position === Et.center && (R.value.left = `${y + V / 2 - P.value.width / 2}px`);
  }, A = (y) => {
    const { width: V, height: S } = y.getBoundingClientRect(), { top: q, left: k } = F(y);
    return { top: +q, left: +k, width: V, height: S };
  }, m = () => {
    R.value.left = "50%", R.value.top = "50%", R.value.transform = "translate(-50%, -50%)", R.value.position = "fixed", delete R.value.opacity;
  }, z = () => {
    const y = Le(l);
    R.value = i.altPosition(y);
  }, L = (y = true) => {
    if (!a.value.enabled) {
      if (T.value) return m();
      if (i.altPosition !== null) return z();
      if (y) {
        const V = i.teleport ? t2.value?.$el : e.value;
        V && (P.value = V.getBoundingClientRect()), f("recalculate-position");
      }
      return ce();
    }
  }, le = ({ inputEl: y, left: V, width: S }) => {
    window.screen.width > 768 && !v.value && M(V, S), ee(y);
  }, U = (y) => {
    const { top: V, left: S, height: q, width: k } = A(y);
    R.value.top = `${q + V + +i.offset}px`, h$1.value = false, v.value || (R.value.left = `${S + k / 2 - P.value.width / 2}px`), le({ inputEl: y, left: S, width: k });
  }, $ = (y) => {
    const { top: V, left: S, width: q } = A(y);
    R.value.top = `${V - +i.offset - P.value.height}px`, h$1.value = true, le({ inputEl: y, left: S, width: q });
  }, ee = (y) => {
    if (i.autoPosition) {
      const { left: V, width: S } = A(y), { left: q, right: k } = P.value;
      if (!v.value) {
        if (Math.abs(q) !== Math.abs(k)) {
          if (q <= 0)
            return v.value = true, C(V);
          if (k >= document.documentElement.clientWidth)
            return v.value = true, _(V, S);
        }
        return M(V, S);
      }
    }
  }, O = () => {
    const y = Le(l);
    if (y) {
      if (i.autoPosition === st.top) return st.top;
      if (i.autoPosition === st.bottom) return st.bottom;
      const { height: V } = P.value, { top: S, height: q } = y.getBoundingClientRect(), se = window.innerHeight - S - q, u = S;
      return V <= se ? st.bottom : V > se && V <= u ? st.top : se >= u ? st.bottom : st.top;
    }
    return st.bottom;
  }, J = (y) => O() === st.bottom ? U(y) : $(y), ce = () => {
    const y = Le(l);
    if (y)
      return i.autoPosition ? J(y) : U(y);
  }, pe = function(y) {
    if (y) {
      const V = y.scrollHeight > y.clientHeight, q = window.getComputedStyle(y).overflowY.indexOf("hidden") !== -1;
      return V && !q;
    }
    return true;
  }, p = function(y) {
    return !y || y === document.body || y.nodeType === Node.DOCUMENT_FRAGMENT_NODE ? window : pe(y) ? y : p(
      y.assignedSlot && d.value.shadowDom ? y.assignedSlot.parentNode : y.parentNode
    );
  }, Y = (y) => {
    if (y)
      switch (i.position) {
        case Et.left:
          return { left: 0, transform: "translateX(0)" };
        case Et.right:
          return { left: `${y.width}px`, transform: "translateX(-100%)" };
        default:
          return { left: `${y.width / 2}px`, transform: "translateX(-50%)" };
      }
    return {};
  };
  return {
    openOnTop: h$1,
    menuStyle: R,
    xCorrect: v,
    setMenuPosition: L,
    getScrollableParent: p,
    shadowRender: (y, V, S) => {
      const q = document.createElement("div"), k = Le(l)?.getBoundingClientRect();
      q.setAttribute("id", "dp--temp-container");
      const se = n.value?.clientWidth ? n.value : document.body;
      se.append(q);
      const u = Y(k), re = d.value.shadowDom ? Object.keys(g).filter(
        (I) => ["right-sidebar", "left-sidebar", "top-extra", "action-extra"].includes(I)
      ) : Object.keys(g), G = h(
        V,
        {
          ...S,
          shadow: true,
          style: { opacity: 0, position: "absolute", ...u }
        },
        Object.fromEntries(re.map((I) => [I, g[I]]))
      );
      y != null && (G.appContext = y.appContext), render(G, q), P.value = G.el?.getBoundingClientRect(), render(null, q), se.removeChild(q);
    }
  };
}, bt = [
  { name: "clock-icon", use: ["time", "calendar", "shared"] },
  { name: "arrow-left", use: ["month-year", "calendar", "shared", "year-mode"] },
  { name: "arrow-right", use: ["month-year", "calendar", "shared", "year-mode"] },
  { name: "arrow-up", use: ["time", "calendar", "month-year", "shared"] },
  { name: "arrow-down", use: ["time", "calendar", "month-year", "shared"] },
  { name: "calendar-icon", use: ["month-year", "time", "calendar", "shared", "year-mode"] },
  { name: "day", use: ["calendar", "shared"] },
  { name: "month-overlay-value", use: ["calendar", "month-year", "shared"] },
  { name: "year-overlay-value", use: ["calendar", "month-year", "shared", "year-mode"] },
  { name: "year-overlay", use: ["month-year", "shared"] },
  { name: "month-overlay", use: ["month-year", "shared"] },
  { name: "month-overlay-header", use: ["month-year", "shared"] },
  { name: "year-overlay-header", use: ["month-year", "shared"] },
  { name: "hours-overlay-value", use: ["calendar", "time", "shared"] },
  { name: "hours-overlay-header", use: ["calendar", "time", "shared"] },
  { name: "minutes-overlay-value", use: ["calendar", "time", "shared"] },
  { name: "minutes-overlay-header", use: ["calendar", "time", "shared"] },
  { name: "seconds-overlay-value", use: ["calendar", "time", "shared"] },
  { name: "seconds-overlay-header", use: ["calendar", "time", "shared"] },
  { name: "hours", use: ["calendar", "time", "shared"] },
  { name: "minutes", use: ["calendar", "time", "shared"] },
  { name: "month", use: ["calendar", "month-year", "shared"] },
  { name: "year", use: ["calendar", "month-year", "shared", "year-mode"] },
  { name: "action-buttons", use: ["action"] },
  { name: "action-preview", use: ["action"] },
  { name: "calendar-header", use: ["calendar", "shared"] },
  { name: "marker-tooltip", use: ["calendar", "shared"] },
  { name: "action-extra", use: ["menu"] },
  { name: "time-picker-overlay", use: ["calendar", "time", "shared"] },
  { name: "am-pm-button", use: ["calendar", "time", "shared"] },
  { name: "left-sidebar", use: ["menu"] },
  { name: "right-sidebar", use: ["menu"] },
  { name: "month-year", use: ["month-year", "shared"] },
  { name: "time-picker", use: ["menu", "shared"] },
  { name: "action-row", use: ["action"] },
  { name: "marker", use: ["calendar", "shared"] },
  { name: "quarter", use: ["shared"] },
  { name: "top-extra", use: ["shared", "month-year"] },
  { name: "tp-inline-arrow-up", use: ["shared", "time"] },
  { name: "tp-inline-arrow-down", use: ["shared", "time"] },
  { name: "menu-header", use: ["menu"] }
], fo = [{ name: "trigger" }, { name: "input-icon" }, { name: "clear-icon" }, { name: "dp-input" }], vo = {
  all: () => bt,
  monthYear: () => bt.filter((e) => e.use.includes("month-year")),
  input: () => fo,
  timePicker: () => bt.filter((e) => e.use.includes("time")),
  action: () => bt.filter((e) => e.use.includes("action")),
  calendar: () => bt.filter((e) => e.use.includes("calendar")),
  menu: () => bt.filter((e) => e.use.includes("menu")),
  shared: () => bt.filter((e) => e.use.includes("shared")),
  yearMode: () => bt.filter((e) => e.use.includes("year-mode"))
}, et = (e, t2, l) => {
  const n = [];
  return vo[t2]().forEach((a) => {
    e[a.name] && n.push(a.name);
  }), l?.length && l.forEach((a) => {
    a.slot && n.push(a.slot);
  }), n;
}, aa = (e) => {
  const t2 = computed(() => (n) => e.value ? n ? e.value.open : e.value.close : ""), l = computed(() => (n) => e.value ? n ? e.value.menuAppearTop : e.value.menuAppearBottom : "");
  return { transitionName: t2, showTransition: !!e.value, menuTransition: l };
}, na = (e, t2, l) => {
  const { defaultedRange: n, defaultedTz: a } = Oe(e), f = H(Ze(H(), a.value.timezone)), i = ref([{ month: getMonth(f), year: getYear(f) }]), g = (h2) => {
    const T = {
      hours: getHours(f),
      minutes: getMinutes(f),
      seconds: 0
    };
    return n.value.enabled ? [T[h2], T[h2]] : T[h2];
  }, d = reactive({
    hours: g("hours"),
    minutes: g("minutes"),
    seconds: g("seconds")
  });
  watch(
    n,
    (h2, T) => {
      h2.enabled !== T.enabled && (d.hours = g("hours"), d.minutes = g("minutes"), d.seconds = g("seconds"));
    },
    { deep: true }
  );
  const P = computed({
    get: () => e.internalModelValue,
    set: (h2) => {
      !e.readonly && !e.disabled && t2("update:internal-model-value", h2);
    }
  }), v = computed(
    () => (h2) => i.value[h2] ? i.value[h2].month : 0
  ), R = computed(
    () => (h2) => i.value[h2] ? i.value[h2].year : 0
  );
  return watch(
    P,
    (h2, T) => {
      l && JSON.stringify(h2 ?? {}) !== JSON.stringify(T ?? {}) && l();
    },
    { deep: true }
  ), {
    calendars: i,
    time: d,
    modelValue: P,
    month: v,
    year: R,
    today: f
  };
}, mo = (e, t2) => {
  const {
    defaultedMultiCalendars: l,
    defaultedMultiDates: n,
    defaultedUI: a,
    defaultedHighlight: f,
    defaultedTz: i,
    propDates: g,
    defaultedRange: d
  } = Oe(t2), { isDisabled: P } = Tt(t2), v = ref(null), R = ref(Ze(/* @__PURE__ */ new Date(), i.value.timezone)), h2 = (c) => {
    !c.current && t2.hideOffsetDates || (v.value = c.value);
  }, T = () => {
    v.value = null;
  }, F = (c) => Array.isArray(e.value) && d.value.enabled && e.value[0] && v.value ? c ? Ee(v.value, e.value[0]) : Be(v.value, e.value[0]) : true, _ = (c, o) => {
    const X = () => e.value ? o ? e.value[0] || null : e.value[1] : null, B = e.value && Array.isArray(e.value) ? X() : null;
    return $e(H(c.value), B);
  }, C = (c) => {
    const o = Array.isArray(e.value) ? e.value[0] : null;
    return c ? !Be(v.value ?? null, o) : true;
  }, M = (c, o = true) => (d.value.enabled || t2.weekPicker) && Array.isArray(e.value) && e.value.length === 2 ? t2.hideOffsetDates && !c.current ? false : $e(H(c.value), e.value[o ? 0 : 1]) : d.value.enabled ? _(c, o) && C(o) || $e(c.value, Array.isArray(e.value) ? e.value[0] : null) && F(o) : false, A = (c, o) => {
    if (Array.isArray(e.value) && e.value[0] && e.value.length === 1) {
      const X = $e(c.value, v.value);
      return o ? Ee(e.value[0], c.value) && X : Be(e.value[0], c.value) && X;
    }
    return false;
  }, m = (c) => !e.value || t2.hideOffsetDates && !c.current ? false : d.value.enabled ? t2.modelAuto && Array.isArray(e.value) ? $e(c.value, e.value[0] ? e.value[0] : R.value) : false : n.value.enabled && Array.isArray(e.value) ? e.value.some((o) => $e(o, c.value)) : $e(c.value, e.value ? e.value : R.value), z = (c) => {
    if (d.value.autoRange || t2.weekPicker) {
      if (v.value) {
        if (t2.hideOffsetDates && !c.current) return false;
        const o = addDays(v.value, +d.value.autoRange), X = mt(H(v.value), t2.weekStart);
        return t2.weekPicker ? $e(X[1], H(c.value)) : $e(o, H(c.value));
      }
      return false;
    }
    return false;
  }, L = (c) => {
    if (d.value.autoRange || t2.weekPicker) {
      if (v.value) {
        const o = addDays(v.value, +d.value.autoRange);
        if (t2.hideOffsetDates && !c.current) return false;
        const X = mt(H(v.value), t2.weekStart);
        return t2.weekPicker ? Ee(c.value, X[0]) && Be(c.value, X[1]) : Ee(c.value, v.value) && Be(c.value, o);
      }
      return false;
    }
    return false;
  }, le = (c) => {
    if (d.value.autoRange || t2.weekPicker) {
      if (v.value) {
        if (t2.hideOffsetDates && !c.current) return false;
        const o = mt(H(v.value), t2.weekStart);
        return t2.weekPicker ? $e(o[0], c.value) : $e(v.value, c.value);
      }
      return false;
    }
    return false;
  }, U = (c) => xt(e.value, v.value, c.value), $ = () => t2.modelAuto && Array.isArray(t2.internalModelValue) ? !!t2.internalModelValue[0] : false, ee = () => t2.modelAuto ? Rn(t2.internalModelValue) : true, O = (c) => {
    if (t2.weekPicker) return false;
    const o = d.value.enabled ? !M(c) && !M(c, false) : true;
    return !P(c.value) && !m(c) && !(!c.current && t2.hideOffsetDates) && o;
  }, J = (c) => d.value.enabled ? t2.modelAuto ? $() && m(c) : false : m(c), ce = (c) => f.value ? Bl(c.value, g.value.highlight) : false, pe = (c) => {
    const o = P(c.value);
    return o && (typeof f.value == "function" ? !f.value(c.value, o) : !f.value.options.highlightDisabled);
  }, p = (c) => typeof f.value == "function" ? f.value(c.value) : f.value.weekdays?.includes(c.value.getDay()), Y = (c) => (d.value.enabled || t2.weekPicker) && (!(l.value.count > 0) || c.current) && ee() && !(!c.current && t2.hideOffsetDates) && !m(c) ? U(c) : false, te = (c) => {
    if (Array.isArray(e.value) && e.value.length === 1) {
      const { before: o, after: X } = mn(+d.value.maxRange, e.value[0]);
      return isBefore(c.value, o) || isAfter(c.value, X);
    }
    return false;
  }, y = (c) => {
    if (Array.isArray(e.value) && e.value.length === 1) {
      const { before: o, after: X } = mn(+d.value.minRange, e.value[0]);
      return xt([o, X], e.value[0], c.value);
    }
    return false;
  }, V = (c) => d.value.enabled && (d.value.maxRange || d.value.minRange) ? d.value.maxRange && d.value.minRange ? te(c) || y(c) : d.value.maxRange ? te(c) : y(c) : false, S = (c) => {
    const { isRangeStart: o, isRangeEnd: X } = u(c), B = d.value.enabled ? o || X : false;
    return {
      dp__cell_offset: !c.current,
      dp__pointer: !t2.disabled && !(!c.current && t2.hideOffsetDates) && !P(c.value) && !V(c),
      dp__cell_disabled: P(c.value) || V(c),
      dp__cell_highlight: !pe(c) && (ce(c) || p(c)) && !J(c) && !B && !le(c) && !(Y(c) && t2.weekPicker) && !X,
      dp__cell_highlight_active: !pe(c) && (ce(c) || p(c)) && J(c),
      dp__today: !t2.noToday && $e(c.value, R.value) && c.current,
      "dp--past": Be(c.value, R.value),
      "dp--future": Ee(c.value, R.value)
    };
  }, q = (c) => ({
    dp__active_date: J(c),
    dp__date_hover: O(c)
  }), k = (c) => {
    if (e.value && !Array.isArray(e.value)) {
      const o = mt(e.value, t2.weekStart);
      return {
        ...G(c),
        dp__range_start: $e(o[0], c.value),
        dp__range_end: $e(o[1], c.value),
        dp__range_between_week: Ee(c.value, o[0]) && Be(c.value, o[1])
      };
    }
    return {
      ...G(c)
    };
  }, se = (c) => {
    if (e.value && Array.isArray(e.value)) {
      const o = mt(e.value[0], t2.weekStart), X = e.value[1] ? mt(e.value[1], t2.weekStart) : [];
      return {
        ...G(c),
        dp__range_start: $e(o[0], c.value) || $e(X[0], c.value),
        dp__range_end: $e(o[1], c.value) || $e(X[1], c.value),
        dp__range_between_week: Ee(c.value, o[0]) && Be(c.value, o[1]) || Ee(c.value, X[0]) && Be(c.value, X[1]),
        dp__range_between: Ee(c.value, o[1]) && Be(c.value, X[0])
      };
    }
    return {
      ...G(c)
    };
  }, u = (c) => {
    const o = l.value.count > 0 ? c.current && M(c) && ee() : M(c) && ee(), X = l.value.count > 0 ? c.current && M(c, false) && ee() : M(c, false) && ee();
    return { isRangeStart: o, isRangeEnd: X };
  }, re = (c) => {
    const { isRangeStart: o, isRangeEnd: X } = u(c);
    return {
      dp__range_start: o,
      dp__range_end: X,
      dp__range_between: Y(c),
      dp__date_hover: $e(c.value, v.value) && !o && !X && !t2.weekPicker,
      dp__date_hover_start: A(c, true),
      dp__date_hover_end: A(c, false)
    };
  }, G = (c) => ({
    ...re(c),
    dp__cell_auto_range: L(c),
    dp__cell_auto_range_start: le(c),
    dp__cell_auto_range_end: z(c)
  }), I = (c) => d.value.enabled ? d.value.autoRange ? G(c) : t2.modelAuto ? { ...q(c), ...re(c) } : t2.weekPicker ? se(c) : re(c) : t2.weekPicker ? k(c) : q(c);
  return {
    setHoverDate: h2,
    clearHoverDate: T,
    getDayClassData: (c) => t2.hideOffsetDates && !c.current ? {} : {
      ...S(c),
      ...I(c),
      [t2.dayClass ? t2.dayClass(c.value, t2.internalModelValue) : ""]: true,
      ...a.value.calendarCell ?? {}
    }
  };
}, Tt = (e) => {
  const { defaultedFilters: t2, defaultedRange: l, propDates: n, defaultedMultiDates: a } = Oe(e), f = (p) => n.value.disabledDates ? typeof n.value.disabledDates == "function" ? n.value.disabledDates(H(p)) : !!ca(p, n.value.disabledDates) : false, i = (p) => n.value.maxDate ? e.yearPicker ? getYear(p) > getYear(n.value.maxDate) : Ee(p, n.value.maxDate) : false, g = (p) => n.value.minDate ? e.yearPicker ? getYear(p) < getYear(n.value.minDate) : Be(p, n.value.minDate) : false, d = (p) => {
    const Y = i(p), te = g(p), y = f(p), S = t2.value.months.map((re) => +re).includes(getMonth(p)), q = e.disabledWeekDays.length ? e.disabledWeekDays.some((re) => +re === getDay(p)) : false, k = T(p), se = getYear(p), u = zn(e.yearRange, se);
    return !(Y || te || y || S || u || q || k);
  }, P = (p, Y) => Be(...wt(n.value.minDate, p, Y)) || $e(...wt(n.value.minDate, p, Y)), v = (p, Y) => Ee(...wt(n.value.maxDate, p, Y)) || $e(...wt(n.value.maxDate, p, Y)), R = (p, Y, te) => {
    let y = false;
    return n.value.maxDate && te && v(p, Y) && (y = true), n.value.minDate && !te && P(p, Y) && (y = true), y;
  }, h2 = (p, Y, te, y) => {
    let V = false;
    return y && (n.value.minDate || n.value.maxDate) ? n.value.minDate && n.value.maxDate ? V = R(p, Y, te) : (n.value.minDate && P(p, Y) || n.value.maxDate && v(p, Y)) && (V = true) : V = true, V;
  }, T = (p) => Array.isArray(n.value.allowedDates) && !n.value.allowedDates.length ? true : n.value.allowedDates ? !ca(p, n.value.allowedDates, On(e.monthPicker, e.yearPicker)) : false, F = (p) => !d(p), _ = (p) => l.value.noDisabledRange ? !eachDayOfInterval({ start: p[0], end: p[1] }).some((te) => F(te)) : true, C = (p) => {
    if (p) {
      const Y = getYear(p);
      return Y >= +e.yearRange[0] && Y <= e.yearRange[1];
    }
    return true;
  }, M = (p, Y) => !!(Array.isArray(p) && p[Y] && (l.value.maxRange || l.value.minRange) && C(p[Y])), A = (p, Y, te = 0) => {
    if (M(Y, te) && C(p)) {
      const y = differenceInCalendarDays(p, Y[te]), V = In(Y[te], p), S = V.length === 1 ? 0 : V.filter((k) => F(k)).length, q = Math.abs(y) - (l.value.minMaxRawRange ? 0 : S);
      if (l.value.minRange && l.value.maxRange)
        return q >= +l.value.minRange && q <= +l.value.maxRange;
      if (l.value.minRange) return q >= +l.value.minRange;
      if (l.value.maxRange) return q <= +l.value.maxRange;
    }
    return true;
  }, m = () => !e.enableTimePicker || e.monthPicker || e.yearPicker || e.ignoreTimeValidation, z = (p) => Array.isArray(p) ? [p[0] ? Ca(p[0]) : null, p[1] ? Ca(p[1]) : null] : Ca(p), L = (p, Y, te) => p.find(
    (y) => +y.hours === getHours(Y) && y.minutes === "*" ? true : +y.minutes === getMinutes(Y) && +y.hours === getHours(Y)
  ) && te, le = (p, Y, te) => {
    const [y, V] = p, [S, q] = Y;
    return !L(y, S, te) && !L(V, q, te) && te;
  }, U = (p, Y) => {
    const te = Array.isArray(Y) ? Y : [Y];
    return Array.isArray(e.disabledTimes) ? Array.isArray(e.disabledTimes[0]) ? le(e.disabledTimes, te, p) : !te.some((y) => L(e.disabledTimes, y, p)) : p;
  }, $ = (p, Y) => {
    const te = Array.isArray(Y) ? [Bt(Y[0]), Y[1] ? Bt(Y[1]) : void 0] : Bt(Y), y = !e.disabledTimes(te);
    return p && y;
  }, ee = (p, Y) => e.disabledTimes ? Array.isArray(e.disabledTimes) ? U(Y, p) : $(Y, p) : Y, O = (p) => {
    let Y = true;
    if (!p || m()) return true;
    const te = !n.value.minDate && !n.value.maxDate ? z(p) : p;
    return (e.maxTime || n.value.maxDate) && (Y = vn(
      e.maxTime,
      n.value.maxDate,
      "max",
      Ne(te),
      Y
    )), (e.minTime || n.value.minDate) && (Y = vn(
      e.minTime,
      n.value.minDate,
      "min",
      Ne(te),
      Y
    )), ee(p, Y);
  }, J = (p) => {
    if (!e.monthPicker) return true;
    let Y = true;
    const te = H(it(p));
    if (n.value.minDate && n.value.maxDate) {
      const y = H(it(n.value.minDate)), V = H(it(n.value.maxDate));
      return Ee(te, y) && Be(te, V) || $e(te, y) || $e(te, V);
    }
    if (n.value.minDate) {
      const y = H(it(n.value.minDate));
      Y = Ee(te, y) || $e(te, y);
    }
    if (n.value.maxDate) {
      const y = H(it(n.value.maxDate));
      Y = Be(te, y) || $e(te, y);
    }
    return Y;
  }, ce = computed(() => (p) => !e.enableTimePicker || e.ignoreTimeValidation ? true : O(p)), pe = computed(() => (p) => e.monthPicker ? Array.isArray(p) && (l.value.enabled || a.value.enabled) ? !p.filter((te) => !J(te)).length : J(p) : true);
  return {
    isDisabled: F,
    validateDate: d,
    validateMonthYearInRange: h2,
    isDateRangeAllowed: _,
    checkMinMaxRange: A,
    isValidTime: O,
    isTimeValid: ce,
    isMonthValid: pe
  };
}, ga = () => {
  const e = computed(() => (n, a) => n?.includes(a)), t2 = computed(() => (n, a) => n.count ? n.solo ? true : a === 0 : true), l = computed(() => (n, a) => n.count ? n.solo ? true : a === n.count - 1 : true);
  return { hideNavigationButtons: e, showLeftIcon: t2, showRightIcon: l };
}, po = (e, t2, l) => {
  const n = ref(0), a = reactive({
    [Ot.timePicker]: !e.enableTimePicker || e.timePicker || e.monthPicker,
    [Ot.calendar]: false,
    [Ot.header]: false
  }), f = computed(() => e.monthPicker || e.timePicker), i = (R) => {
    if (e.flow?.length) {
      if (!R && f.value) return v();
      a[R] = true, Object.keys(a).filter((h2) => !a[h2]).length || v();
    }
  }, g = () => {
    e.flow?.length && n.value !== -1 && (n.value += 1, t2("flow-step", n.value), v()), e.flow?.length === n.value && nextTick().then(() => d());
  }, d = () => {
    n.value = -1;
  }, P = (R, h2, ...T) => {
    e.flow[n.value] === R && l.value && l.value[h2]?.(...T);
  }, v = (R = 0) => {
    R && (n.value += R), P(je.month, "toggleMonthPicker", true), P(je.year, "toggleYearPicker", true), P(je.calendar, "toggleTimePicker", false, true), P(je.time, "toggleTimePicker", true, true);
    const h2 = e.flow[n.value];
    (h2 === je.hours || h2 === je.minutes || h2 === je.seconds) && P(h2, "toggleTimePicker", true, true, h2);
  };
  return { childMount: i, updateFlowStep: g, resetFlow: d, handleFlow: v, flowStep: n };
}, go = {
  key: 1,
  class: "dp__input_wrap"
}, yo = ["id", "name", "inputmode", "placeholder", "disabled", "readonly", "required", "value", "autocomplete", "aria-label", "aria-disabled", "aria-invalid"], ho = {
  key: 2,
  class: "dp--clear-btn"
}, bo = ["aria-label"], ko = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "DatepickerInput",
  props: {
    isMenuOpen: { type: Boolean, default: false },
    inputValue: { type: String, default: "" },
    ...va
  },
  emits: [
    "clear",
    "open",
    "update:input-value",
    "set-input-date",
    "close",
    "select-date",
    "set-empty-date",
    "toggle",
    "focus-prev",
    "focus",
    "blur",
    "real-blur",
    "text-input"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, {
      defaultedTextInput: f,
      defaultedAriaLabels: i,
      defaultedInline: g,
      defaultedConfig: d,
      defaultedRange: P,
      defaultedMultiDates: v,
      defaultedUI: R,
      getDefaultPattern: h2,
      getDefaultStartTime: T
    } = Oe(a), { checkMinMaxRange: F } = Tt(a), _ = ref(), C = ref(null), M = ref(false), A = ref(false), m = computed(
      () => ({
        dp__pointer: !a.disabled && !a.readonly && !f.value.enabled,
        dp__disabled: a.disabled,
        dp__input_readonly: !f.value.enabled,
        dp__input: true,
        dp__input_not_clearable: !a.clearable,
        dp__input_icon_pad: !a.hideInputIcon,
        dp__input_valid: typeof a.state == "boolean" ? a.state : false,
        dp__input_invalid: typeof a.state == "boolean" ? !a.state : false,
        dp__input_focus: M.value || a.isMenuOpen,
        dp__input_reg: !f.value.enabled,
        ...R.value.input ?? {}
      })
    ), z = () => {
      n("set-input-date", null), a.clearable && a.autoApply && (n("set-empty-date"), _.value = null);
    }, L = (k) => {
      const se = T();
      return Yl(
        k,
        f.value.format ?? h2(),
        se ?? En({}, a.enableSeconds),
        a.inputValue,
        A.value,
        a.formatLocale
      );
    }, le = (k) => {
      const { rangeSeparator: se } = f.value, [u, re] = k.split(`${se}`);
      if (u) {
        const G = L(u.trim()), I = re ? L(re.trim()) : void 0;
        if (isAfter(G, I)) return;
        const x = G && I ? [G, I] : [G];
        F(I, x, 0) && (_.value = G ? x : null);
      }
    }, U = () => {
      A.value = true;
    }, $ = (k) => {
      if (P.value.enabled)
        le(k);
      else if (v.value.enabled) {
        const se = k.split(";");
        _.value = se.map((u) => L(u.trim())).filter((u) => u);
      } else
        _.value = L(k);
    }, ee = (k) => {
      const se = typeof k == "string" ? k : k.target?.value;
      se !== "" ? (f.value.openMenu && !a.isMenuOpen && n("open"), $(se), n("set-input-date", _.value)) : z(), A.value = false, n("update:input-value", se), n("text-input", k, _.value);
    }, O = (k) => {
      f.value.enabled ? ($(k.target.value), f.value.enterSubmit && za(_.value) && a.inputValue !== "" ? (n("set-input-date", _.value, true), _.value = null) : f.value.enterSubmit && a.inputValue === "" && (_.value = null, n("clear"))) : pe(k);
    }, J = (k, se) => {
      f.value.enabled && f.value.tabSubmit && !se && $(k.target.value), f.value.tabSubmit && za(_.value) && a.inputValue !== "" ? (n("set-input-date", _.value, true, true), _.value = null) : f.value.tabSubmit && a.inputValue === "" && (_.value = null, n("clear", true));
    }, ce = () => {
      M.value = true, n("focus"), nextTick().then(() => {
        f.value.enabled && f.value.selectOnFocus && C.value?.select();
      });
    }, pe = (k) => {
      if (Dt(k, d.value, true), f.value.enabled && f.value.openMenu && !g.value.input) {
        if (f.value.openMenu === "open" && !a.isMenuOpen) return n("open");
        if (f.value.openMenu === "toggle") return n("toggle");
      } else f.value.enabled || n("toggle");
    }, p = () => {
      n("real-blur"), M.value = false, (!a.isMenuOpen || g.value.enabled && g.value.input) && n("blur"), a.autoApply && f.value.enabled && _.value && !a.isMenuOpen && (n("set-input-date", _.value), n("select-date"), _.value = null);
    }, Y = (k) => {
      Dt(k, d.value, true), n("clear");
    }, te = () => {
      n("close");
    }, y = (k) => {
      if (k.key === "Tab" && J(k), k.key === "Enter" && O(k), k.key === "Escape" && f.value.escClose && te(), !f.value.enabled) {
        if (k.code === "Tab") return;
        k.preventDefault();
      }
    }, V = () => {
      C.value?.focus({ preventScroll: true });
    }, S = (k) => {
      _.value = k;
    }, q = (k) => {
      k.key === Re.tab && J(k, true);
    };
    return t2({
      focusInput: V,
      setParsedDate: S
    }), (k, se) => (openBlock(), createElementBlock("div", { onClick: pe }, [
      k.$slots.trigger && !k.$slots["dp-input"] && !unref(g).enabled ? renderSlot(k.$slots, "trigger", { key: 0 }) : createCommentVNode("", true),
      !k.$slots.trigger && (!unref(g).enabled || unref(g).input) ? (openBlock(), createElementBlock("div", go, [
        k.$slots["dp-input"] && !k.$slots.trigger && (!unref(g).enabled || unref(g).enabled && unref(g).input) ? renderSlot(k.$slots, "dp-input", {
          key: 0,
          value: e.inputValue,
          isMenuOpen: e.isMenuOpen,
          onInput: ee,
          onEnter: O,
          onTab: J,
          onClear: Y,
          onBlur: p,
          onKeypress: y,
          onPaste: U,
          onFocus: ce,
          openMenu: () => k.$emit("open"),
          closeMenu: () => k.$emit("close"),
          toggleMenu: () => k.$emit("toggle")
        }) : createCommentVNode("", true),
        k.$slots["dp-input"] ? createCommentVNode("", true) : (openBlock(), createElementBlock("input", {
          key: 1,
          id: k.uid ? `dp-input-${k.uid}` : void 0,
          ref_key: "inputRef",
          ref: C,
          "data-test-id": "dp-input",
          name: k.name,
          class: normalizeClass(m.value),
          inputmode: unref(f).enabled ? "text" : "none",
          placeholder: k.placeholder,
          disabled: k.disabled,
          readonly: k.readonly,
          required: k.required,
          value: e.inputValue,
          autocomplete: k.autocomplete,
          "aria-label": unref(i)?.input,
          "aria-disabled": k.disabled || void 0,
          "aria-invalid": k.state === false ? true : void 0,
          onInput: ee,
          onBlur: p,
          onFocus: ce,
          onKeypress: y,
          onKeydown: se[0] || (se[0] = (u) => y(u)),
          onPaste: U
        }, null, 42, yo)),
        createBaseVNode("div", {
          onClick: se[3] || (se[3] = (u) => n("toggle"))
        }, [
          k.$slots["input-icon"] && !k.hideInputIcon ? (openBlock(), createElementBlock("span", {
            key: 0,
            class: "dp__input_icon",
            onClick: se[1] || (se[1] = (u) => n("toggle"))
          }, [
            renderSlot(k.$slots, "input-icon")
          ])) : createCommentVNode("", true),
          !k.$slots["input-icon"] && !k.hideInputIcon && !k.$slots["dp-input"] ? (openBlock(), createBlock(unref(jt), {
            key: 1,
            "aria-label": unref(i)?.calendarIcon,
            class: "dp__input_icon dp__input_icons",
            onClick: se[2] || (se[2] = (u) => n("toggle"))
          }, null, 8, ["aria-label"])) : createCommentVNode("", true)
        ]),
        k.$slots["clear-icon"] && (k.alwaysClearable || e.inputValue && k.clearable && !k.disabled && !k.readonly) ? (openBlock(), createElementBlock("span", ho, [
          renderSlot(k.$slots, "clear-icon", { clear: Y })
        ])) : createCommentVNode("", true),
        !k.$slots["clear-icon"] && (k.alwaysClearable || k.clearable && e.inputValue && !k.disabled && !k.readonly) ? (openBlock(), createElementBlock("button", {
          key: 3,
          "aria-label": unref(i)?.clearInput,
          class: "dp--clear-btn",
          type: "button",
          onKeydown: se[4] || (se[4] = (u) => unref(qe)(u, () => Y(u), true, q)),
          onClick: se[5] || (se[5] = withModifiers((u) => Y(u), ["prevent"]))
        }, [
          createVNode(unref(Sn), {
            class: "dp__input_icons",
            "data-test-id": "clear-icon"
          })
        ], 40, bo)) : createCommentVNode("", true)
      ])) : createCommentVNode("", true)
    ]));
  }
}), wo = typeof window < "u" ? window : void 0, Ea = () => {
}, Do = (e) => getCurrentScope() ? (onScopeDispose(e), true) : false, Mo = (e, t2, l, n) => {
  if (!e) return Ea;
  let a = Ea;
  const f = watch(
    () => unref(e),
    (g) => {
      a(), g && (g.removeEventListener(t2, l), g.addEventListener(t2, l, n), a = () => {
        g.removeEventListener(t2, l, n), a = Ea;
      });
    },
    { immediate: true, flush: "post" }
  ), i = () => {
    f(), a();
  };
  return Do(i), i;
}, $o = (e, t2, l, n = {}) => {
  const { window: a = wo, event: f = "pointerdown" } = n;
  return a ? Mo(a, f, (g) => {
    const d = Le(e), P = Le(t2);
    !d || !P || d === g.target || g.composedPath().includes(d) || g.composedPath().includes(P) || l(g);
  }, { passive: true }) : void 0;
}, Ao = ["data-dp-mobile"], To = /* @__PURE__ */ defineComponent({
  compatConfig: {
    MODE: 3
  },
  __name: "VueDatePicker",
  props: {
    ...va
  },
  emits: [
    "update:model-value",
    "update:model-timezone-value",
    "text-submit",
    "closed",
    "cleared",
    "open",
    "focus",
    "blur",
    "internal-model-change",
    "recalculate-position",
    "flow-step",
    "update-month-year",
    "invalid-select",
    "invalid-fixed-range",
    "tooltip-open",
    "tooltip-close",
    "time-picker-open",
    "time-picker-close",
    "am-pm-change",
    "range-start",
    "range-end",
    "date-update",
    "invalid-date",
    "overlay-toggle",
    "text-input"
  ],
  setup(e, { expose: t2, emit: l }) {
    const n = l, a = e, f = useSlots(), i = ref(false), g = toRef(a, "modelValue"), d = toRef(a, "timezone"), P = ref(null), v = ref(null), R = ref(null), h2 = ref(false), T = ref(null), F = ref(false), _ = ref(false), C = ref(false), M = ref(false), { setMenuFocused: A, setShiftKey: m } = Hn(), { clearArrowNav: z } = At(), { validateDate: L, isValidTime: le } = Tt(a), {
      defaultedTransitions: U,
      defaultedTextInput: $,
      defaultedInline: ee,
      defaultedConfig: O,
      defaultedRange: J,
      defaultedMultiDates: ce
    } = Oe(a), { menuTransition: pe, showTransition: p } = aa(U), { isMobile: Y } = Qn(O), te = getCurrentInstance();
    onMounted(() => {
      x(a.modelValue), nextTick().then(() => {
        ee.value.enabled || (u(T.value)?.addEventListener("scroll", K), window?.addEventListener("resize", oe));
      }), ee.value.enabled && (i.value = true), window?.addEventListener("keyup", ge), window?.addEventListener("keydown", _e);
    }), onUnmounted(() => {
      ee.value.enabled || (u(T.value)?.removeEventListener("scroll", K), window?.removeEventListener("resize", oe)), window?.removeEventListener("keyup", ge), window?.removeEventListener("keydown", _e);
    });
    const y = et(f, "all", a.presetDates), V = et(f, "input");
    watch(
      [g, d],
      () => {
        x(g.value);
      },
      { deep: true }
    );
    const { openOnTop: S, menuStyle: q, xCorrect: k, setMenuPosition: se, getScrollableParent: u, shadowRender: re } = co({
      menuRef: P,
      menuRefInner: v,
      inputRef: R,
      pickerWrapperRef: T,
      inline: ee,
      emit: n,
      props: a,
      slots: f
    }), {
      inputValue: G,
      internalModelValue: I,
      parseExternalModelValue: x,
      emitModelValue: c,
      formatInputValue: o,
      checkBeforeEmit: X
    } = rr(n, a, { isInputFocused: h2, isTextInputDate: M }), B = computed(
      () => ({
        dp__main: true,
        dp__theme_dark: a.dark,
        dp__theme_light: !a.dark,
        dp__flex_display: ee.value.enabled,
        "dp--flex-display-collapsed": C.value,
        dp__flex_display_with_input: ee.value.input
      })
    ), be = computed(() => a.dark ? "dp__theme_dark" : "dp__theme_light"), Ae = computed(() => a.teleport ? {
      to: typeof a.teleport == "boolean" ? "body" : a.teleport,
      disabled: !a.teleport || ee.value.enabled
    } : {}), ne = computed(() => ({ class: "dp__outer_menu_wrap" })), r = computed(() => ee.value.enabled && (a.timePicker || a.monthPicker || a.yearPicker || a.quarterPicker)), E = () => R.value?.$el?.getBoundingClientRect() ?? { width: 0, left: 0, right: 0 }, K = () => {
      i.value && (O.value.closeOnScroll ? Xe() : se());
    }, oe = () => {
      i.value && se();
      const N = v.value?.$el.getBoundingClientRect().width ?? 0;
      C.value = document.body.offsetWidth <= N;
    }, ge = (N) => {
      N.key === "Tab" && !ee.value.enabled && !a.teleport && O.value.tabOutClosesMenu && (T.value.contains(document.activeElement) || Xe()), _.value = N.shiftKey;
    }, _e = (N) => {
      _.value = N.shiftKey;
    }, Ye = () => {
      !a.disabled && !a.readonly && (re(te, hn, a), se(false), i.value = true, i.value && n("open"), i.value || xe(), x(a.modelValue));
    }, nt = () => {
      G.value = "", xe(), v.value?.onValueCleared(), R.value?.setParsedDate(null), n("update:model-value", null), n("update:model-timezone-value", null), n("cleared"), O.value.closeOnClearValue && Xe();
    }, St = () => {
      const N = I.value;
      return !N || !Array.isArray(N) && L(N) ? true : Array.isArray(N) ? ce.value.enabled || N.length === 2 && L(N[0]) && L(N[1]) ? true : J.value.partialRange && !a.timePicker ? L(N[0]) : false : false;
    }, D = () => {
      X() && St() ? (c(), Xe()) : n("invalid-select", I.value);
    }, de = (N) => {
      fe(), c(), O.value.closeOnAutoApply && !N && Xe();
    }, fe = () => {
      R.value && $.value.enabled && R.value.setParsedDate(I.value);
    }, ht = (N = false) => {
      a.autoApply && le(I.value) && St() && (J.value.enabled && Array.isArray(I.value) ? (J.value.partialRange || I.value.length === 2) && de(N) : de(N));
    }, xe = () => {
      $.value.enabled || (I.value = null);
    }, Xe = (N = false) => {
      N && I.value && O.value.setDateOnMenuClose && D(), ee.value.enabled || (i.value && (i.value = false, k.value = false, A(false), m(false), z(), n("closed"), G.value && x(g.value)), xe(), n("blur"), v.value?.$el?.remove());
    }, ya = (N, ue, me = false) => {
      if (!N) {
        I.value = null;
        return;
      }
      const vt = Array.isArray(N) ? !N.some((Pt) => !L(Pt)) : L(N), ct = le(N);
      vt && ct ? (M.value = true, I.value = N, ue ? (F.value = me, D(), n("text-submit")) : a.autoApply && ht(true), nextTick().then(() => {
        M.value = false;
      })) : n("invalid-date", N);
    }, ha = () => {
      a.autoApply && le(I.value) && c(), fe();
    }, la = () => i.value ? Xe() : Ye(), ba = (N) => {
      I.value = N;
    }, ka = () => {
      $.value.enabled && (h2.value = true, o()), n("focus");
    }, wa = () => {
      $.value.enabled && (h2.value = false, x(a.modelValue), F.value && Ol(T.value, _.value)?.focus()), n("blur");
    }, Da = (N) => {
      v.value && v.value.updateMonthYear(0, {
        month: dn(N.month),
        year: dn(N.year)
      });
    }, Ma = (N) => {
      x(N ?? a.modelValue);
    }, an = (N, ue) => {
      v.value?.switchView(N, ue);
    }, b = (N, ue) => O.value.onClickOutside ? O.value.onClickOutside(N, ue) : Xe(true), ae = (N = 0) => {
      v.value?.handleFlow(N);
    }, ve = () => P;
    return $o(
      P,
      R,
      (N) => b(St, N)
    ), t2({
      closeMenu: Xe,
      selectDate: D,
      clearValue: nt,
      openMenu: Ye,
      onScroll: K,
      formatInputValue: o,
      // exposed for testing purposes
      updateInternalModelValue: ba,
      // modify internal modelValue
      setMonthYear: Da,
      parseModel: Ma,
      switchView: an,
      toggleMenu: la,
      handleFlow: ae,
      getDpWrapMenuRef: ve
    }), (N, ue) => (openBlock(), createElementBlock("div", {
      ref_key: "pickerWrapperRef",
      ref: T,
      class: normalizeClass(B.value),
      "data-datepicker-instance": "",
      "data-dp-mobile": unref(Y)
    }, [
      createVNode(ko, mergeProps({
        ref_key: "inputRef",
        ref: R,
        "input-value": unref(G),
        "onUpdate:inputValue": ue[0] || (ue[0] = (me) => isRef(G) ? G.value = me : null),
        "is-menu-open": i.value
      }, N.$props, {
        onClear: nt,
        onOpen: Ye,
        onSetInputDate: ya,
        onSetEmptyDate: unref(c),
        onSelectDate: D,
        onToggle: la,
        onClose: Xe,
        onFocus: ka,
        onBlur: wa,
        onRealBlur: ue[1] || (ue[1] = (me) => h2.value = false),
        onTextInput: ue[2] || (ue[2] = (me) => N.$emit("text-input", me))
      }), createSlots({ _: 2 }, [
        renderList(unref(V), (me, vt) => ({
          name: me,
          fn: withCtx((ct) => [
            renderSlot(N.$slots, me, normalizeProps(guardReactiveProps(ct)))
          ])
        }))
      ]), 1040, ["input-value", "is-menu-open", "onSetEmptyDate"]),
      (openBlock(), createBlock(resolveDynamicComponent(N.teleport ? Teleport : "div"), normalizeProps(guardReactiveProps(Ae.value)), {
        default: withCtx(() => [
          createVNode(Transition, {
            name: unref(pe)(unref(S)),
            css: unref(p) && !unref(ee).enabled
          }, {
            default: withCtx(() => [
              i.value ? (openBlock(), createElementBlock("div", mergeProps({
                key: 0,
                ref_key: "dpWrapMenuRef",
                ref: P
              }, ne.value, {
                class: { "dp--menu-wrapper": !unref(ee).enabled },
                style: unref(ee).enabled ? void 0 : unref(q)
              }), [
                createVNode(hn, mergeProps({
                  ref_key: "dpMenuRef",
                  ref: v
                }, N.$props, {
                  "internal-model-value": unref(I),
                  "onUpdate:internalModelValue": ue[3] || (ue[3] = (me) => isRef(I) ? I.value = me : null),
                  class: { [be.value]: true, "dp--menu-wrapper": N.teleport },
                  "open-on-top": unref(S),
                  "no-overlay-focus": r.value,
                  collapse: C.value,
                  "get-input-rect": E,
                  "is-text-input-date": M.value,
                  onClosePicker: Xe,
                  onSelectDate: D,
                  onAutoApply: ht,
                  onTimeUpdate: ha,
                  onFlowStep: ue[4] || (ue[4] = (me) => N.$emit("flow-step", me)),
                  onUpdateMonthYear: ue[5] || (ue[5] = (me) => N.$emit("update-month-year", me)),
                  onInvalidSelect: ue[6] || (ue[6] = (me) => N.$emit("invalid-select", unref(I))),
                  onAutoApplyInvalid: ue[7] || (ue[7] = (me) => N.$emit("invalid-select", me)),
                  onInvalidFixedRange: ue[8] || (ue[8] = (me) => N.$emit("invalid-fixed-range", me)),
                  onRecalculatePosition: unref(se),
                  onTooltipOpen: ue[9] || (ue[9] = (me) => N.$emit("tooltip-open", me)),
                  onTooltipClose: ue[10] || (ue[10] = (me) => N.$emit("tooltip-close", me)),
                  onTimePickerOpen: ue[11] || (ue[11] = (me) => N.$emit("time-picker-open", me)),
                  onTimePickerClose: ue[12] || (ue[12] = (me) => N.$emit("time-picker-close", me)),
                  onAmPmChange: ue[13] || (ue[13] = (me) => N.$emit("am-pm-change", me)),
                  onRangeStart: ue[14] || (ue[14] = (me) => N.$emit("range-start", me)),
                  onRangeEnd: ue[15] || (ue[15] = (me) => N.$emit("range-end", me)),
                  onDateUpdate: ue[16] || (ue[16] = (me) => N.$emit("date-update", me)),
                  onInvalidDate: ue[17] || (ue[17] = (me) => N.$emit("invalid-date", me)),
                  onOverlayToggle: ue[18] || (ue[18] = (me) => N.$emit("overlay-toggle", me)),
                  onMenuBlur: ue[19] || (ue[19] = (me) => N.$emit("blur"))
                }), createSlots({ _: 2 }, [
                  renderList(unref(y), (me, vt) => ({
                    name: me,
                    fn: withCtx((ct) => [
                      renderSlot(N.$slots, me, normalizeProps(guardReactiveProps({ ...ct })))
                    ])
                  }))
                ]), 1040, ["internal-model-value", "class", "open-on-top", "no-overlay-focus", "collapse", "is-text-input-date", "onRecalculatePosition"])
              ], 16)) : createCommentVNode("", true)
            ]),
            _: 3
          }, 8, ["name", "css"])
        ]),
        _: 3
      }, 16))
    ], 10, Ao));
  }
}), qn = /* @__PURE__ */ (() => {
  const e = To;
  return e.install = (t2) => {
    t2.component("Vue3DatePicker", e);
  }, e;
})(), So = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  default: qn
}, Symbol.toStringTag, { value: "Module" }));
Object.entries(So).forEach(([e, t2]) => {
  e !== "default" && (qn[e] = t2);
});
register(t44);
function formatTimezoneId(timezoneId) {
  return timezoneId.slice(timezoneId.indexOf("/") + 1).replaceAll("/", " - ").replaceAll("_", " ");
}
function getTimezones() {
  return Intl.supportedValuesOf("timeZone").filter((tz) => !tz.startsWith("Etc/")).map((timezoneId) => ({
    timezoneId,
    label: formatTimezoneId(timezoneId)
  })).sort((a, b) => a.timezoneId.localeCompare(b.timezoneId));
}
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "NcTimezonePicker",
  props: /* @__PURE__ */ mergeModels({
    additionalTimezones: { default: () => [] },
    uid: { default: createElementId() }
  }, {
    "modelValue": { default: "floating" },
    "modelModifiers": {}
  }),
  emits: ["update:modelValue"],
  setup(__props) {
    const modelValue = useModel(__props, "modelValue");
    const props = __props;
    const formattedAdditionalTimezones = computed(() => {
      return props.additionalTimezones.map(({ timezoneId, label }) => ({
        timezoneId,
        label
      }));
    });
    const options = computed(() => {
      const timezones = getTimezones();
      timezones.unshift(...formattedAdditionalTimezones.value);
      return timezones;
    });
    function filterBy(option, label, search) {
      const terms = search.trim().split(/\s+/);
      const values = Object.values(option);
      return terms.every((term) => {
        return values.some((value) => value.toLowerCase().includes(term.toLowerCase()));
      });
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(NcSelect, {
        modelValue: modelValue.value,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => modelValue.value = $event),
        "aria-label-combobox": unref(t)("Search for time zone"),
        clearable: false,
        filterBy,
        multiple: false,
        options: options.value,
        placeholder: unref(t)("Type to search time zone"),
        uid: _ctx.uid,
        reduce: (option) => option.timezoneId,
        label: "label"
      }, null, 8, ["modelValue", "aria-label-combobox", "options", "placeholder", "uid", "reduce"]);
    };
  }
});
register(t13);
const _hoisted_1 = { class: "vue-date-time-picker__wrapper" };
const _hoisted_2 = {
  ref: "target",
  class: "vue-date-time-picker__wrapper vue-date-time-picker__wrapper--teleport"
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcDateTimePicker",
  props: /* @__PURE__ */ mergeModels({
    appendToBody: { type: Boolean },
    ariaLabel: { default: t("Datepicker input") },
    ariaLabelMenu: { default: t("Datepicker menu") },
    clearable: { type: Boolean },
    confirm: { type: Boolean },
    format: { type: [String, Function], default: void 0 },
    locale: { default: getCanonicalLocale() },
    max: { default: void 0 },
    min: { default: void 0 },
    minuteStep: { default: 10 },
    modelValue: { default: null },
    placeholder: { default: void 0 },
    showTimezoneSelect: { type: Boolean },
    showWeekNumber: { type: Boolean },
    type: { default: "date" },
    inline: { type: Boolean, default: false }
  }, {
    "timezoneId": { default: "UTC" },
    "timezoneIdModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["update:modelValue", "update:timezoneId", "blur"], ["update:timezoneId"]),
  setup(__props, { emit: __emit }) {
    const timezoneId = useModel(__props, "timezoneId");
    const props = __props;
    const emit = __emit;
    const targetElement = useTemplateRef("target");
    const pickerInstance = useTemplateRef("picker");
    const value = computed(() => {
      if (props.modelValue === null && props.clearable) {
        return null;
      }
      if (props.type === "week") {
        const date = props.modelValue instanceof Date ? props.modelValue : /* @__PURE__ */ new Date();
        const end = new Date(date);
        end.setUTCDate(date.getUTCDate() + 6);
        return [date, end];
      } else if (props.type === "year") {
        const date = props.modelValue instanceof Date ? props.modelValue : /* @__PURE__ */ new Date();
        return date.getUTCFullYear();
      } else if (props.type === "month") {
        const date = props.modelValue instanceof Date ? props.modelValue : /* @__PURE__ */ new Date();
        return { year: date.getUTCFullYear(), month: date.getUTCMonth() };
      } else if (props.type === "time") {
        const time = props.modelValue instanceof Date ? props.modelValue : /* @__PURE__ */ new Date();
        return {
          hours: time.getHours(),
          minutes: time.getMinutes(),
          seconds: time.getSeconds()
        };
      } else if (props.type === "time-range") {
        const time = [props.modelValue].flat();
        if (time.length !== 2) {
          const start = /* @__PURE__ */ new Date();
          const end = new Date(start);
          end.setHours(end.getHours() + 1);
          time.splice(0, 2, start, end);
        }
        return time.map((date) => ({
          hours: date.getHours(),
          minutes: date.getMinutes(),
          seconds: date.getSeconds()
        }));
      } else if (props.type.endsWith("-range")) {
        if (props.modelValue === void 0) {
          const start = /* @__PURE__ */ new Date();
          const end = new Date(start);
          end.setUTCDate(start.getUTCDate() + 7);
          return [start, end];
        }
        return props.modelValue;
      }
      return props.modelValue ?? /* @__PURE__ */ new Date();
    });
    const placeholderFallback = computed(() => {
      if (props.type === "date") {
        return t("Select date");
      } else if (props.type === "time") {
        return t("Select time");
      } else if (props.type === "datetime") {
        return t("Select date and time");
      } else if (props.type === "week") {
        return t("Select week");
      } else if (props.type === "month") {
        return t("Select month");
      } else if (props.type === "year") {
        return t("Select year");
      } else if (props.type.endsWith("-range")) {
        return t("Select time range");
      }
      return t("Select date and time");
    });
    const realFormat = computed(() => {
      if (props.format) {
        return props.format;
      } else if (props.type === "week") {
        return "RR-II";
      }
      let formatter;
      if (props.type === "date" || props.type === "date-range") {
        formatter = new Intl.DateTimeFormat(getCanonicalLocale(), { dateStyle: "medium" });
      } else if (props.type === "time" || props.type === "time-range") {
        formatter = new Intl.DateTimeFormat(getCanonicalLocale(), { timeStyle: "short" });
      } else if (props.type === "datetime" || props.type === "datetime-range") {
        formatter = new Intl.DateTimeFormat(getCanonicalLocale(), { dateStyle: "medium", timeStyle: "short" });
      } else if (props.type === "month") {
        formatter = new Intl.DateTimeFormat(getCanonicalLocale(), { year: "numeric", month: "2-digit" });
      } else if (props.type === "year") {
        formatter = new Intl.DateTimeFormat(getCanonicalLocale(), { year: "numeric" });
      }
      if (formatter) {
        return (input) => Array.isArray(input) ? formatter.formatRange(input[0], input[1]) : formatter.format(input);
      }
      return void 0;
    });
    const pickerType = computed(() => ({
      timePicker: props.type === "time" || props.type === "time-range",
      yearPicker: props.type === "year",
      monthPicker: props.type === "month",
      weekPicker: props.type === "week",
      range: props.type.endsWith("-range") && {
        // do not use partial ranges (meaning after selecting the start [Date, null] will be emitted)
        // if this is needed someday we can enable it,
        // but its not covered by our component interface (props / events) documentation so just disabled for now.
        partialRange: false
      },
      enableTimePicker: !(props.type === "date" || props.type === "date-range"),
      flow: props.type === "datetime" ? ["calendar", "time"] : void 0
    }));
    const minTime = computed(() => props.min && { hours: props.min.getHours(), minutes: props.min.getMinutes(), seconds: props.min.getSeconds() });
    const maxTime = computed(() => props.max && { hours: props.max.getHours(), minutes: props.max.getMinutes(), seconds: props.max.getSeconds() });
    function onUpdateModelValue(value2) {
      if (value2 === null) {
        return emit("update:modelValue", null);
      }
      if (props.type === "time") {
        emit("update:modelValue", formatLibraryTime(value2));
      } else if (props.type === "time-range") {
        const start = formatLibraryTime(value2[0]);
        const end = formatLibraryTime(value2[1]);
        if (end.getTime() < start.getTime()) {
          end.setDate(end.getDate() + 1);
        }
        emit("update:modelValue", [start, end]);
      } else if (props.type === "month") {
        const data = value2;
        emit("update:modelValue", new Date(data.year, data.month, 1));
      } else if (props.type === "year") {
        emit("update:modelValue", new Date(value2, 0));
      } else if (props.type === "week") {
        emit("update:modelValue", value2[0]);
      } else {
        emit("update:modelValue", value2);
      }
    }
    function formatLibraryTime(time) {
      const date = /* @__PURE__ */ new Date();
      date.setHours(time.hours);
      date.setMinutes(time.minutes);
      date.setSeconds(time.seconds);
      return date;
    }
    const weekStart = getFirstDay();
    const dayNames = [...getDayNamesMin()];
    for (let i = 0; i < weekStart; i++) {
      dayNames.push(dayNames.shift());
    }
    const weekNumName = t("W");
    const ariaLabels = computed(() => ({
      toggleOverlay: t("Toggle overlay"),
      menu: props.ariaLabelMenu,
      input: props.ariaLabel,
      openTimePicker: t("Open time picker"),
      closeTimePicker: t("Close time Picker"),
      incrementValue: (type) => {
        if (type === "hours") {
          return t("Increment hours");
        } else if (type === "minutes") {
          return t("Increment minutes");
        }
        return t("Increment seconds");
      },
      decrementValue: (type) => {
        if (type === "hours") {
          return t("Decrement hours");
        } else if (type === "minutes") {
          return t("Decrement minutes");
        }
        return t("Decrement seconds");
      },
      openTpOverlay: (type) => {
        if (type === "hours") {
          return t("Open hours overlay");
        } else if (type === "minutes") {
          return t("Open minutes overlay");
        }
        return t("Open seconds overlay");
      },
      amPmButton: t("Switch AM/PM mode"),
      openYearsOverlay: t("Open years overlay"),
      openMonthsOverlay: t("Open months overlay"),
      nextMonth: t("Next month"),
      prevMonth: t("Previous month"),
      nextYear: t("Next year"),
      prevYear: t("Previous year"),
      weekDay: (day) => getDayNames()[day],
      clearInput: t("Clear value"),
      calendarIcon: t("Calendar icon"),
      timePicker: t("Time picker"),
      monthPicker: (overlay) => overlay ? t("Month picker overlay") : t("Month picker"),
      yearPicker: (overlay) => overlay ? t("Year picker overlay") : t("Year picker")
    }));
    function selectDate() {
      pickerInstance.value.selectDate();
    }
    function cancelSelection() {
      pickerInstance.value.closeMenu();
    }
    const calcMinMaxTime = computed(() => {
      if (props.type === "datetime") {
        return {
          minDate: props.min,
          maxDate: props.max,
          minTime: props.min && value.value && sameDay(props.min, value.value) ? minTime.value : void 0,
          maxTime: props.max && value.value && sameDay(props.max, value.value) ? maxTime.value : void 0
        };
      }
      if (props.type === "datetime-range") {
        return {
          minDate: props.min,
          maxDate: props.max,
          minTime: props.min && value.value ? sameDay(props.min, value.value[0]) ? minTime.value : void 0 : void 0,
          maxTime: props.max && value.value ? sameDay(props.max, value.value[1]) ? maxTime.value : void 0 : void 0
        };
      }
      if (props.type === "time" || props.type === "time-range") {
        return {
          minTime: props.min ? minTime.value : void 0,
          maxTime: props.max ? maxTime.value : void 0
        };
      }
      return {
        minDate: props.min,
        maxDate: props.max
      };
    });
    function sameDay(a, b) {
      return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1, [
        createVNode(unref(qn), mergeProps({
          ref: "picker",
          "aria-labels": ariaLabels.value,
          autoApply: !_ctx.confirm,
          class: ["vue-date-time-picker", { "vue-date-time-picker--clearable": _ctx.clearable }],
          cancelText: unref(t)("Cancel"),
          clearable: _ctx.clearable,
          dayNames,
          placeholder: _ctx.placeholder ?? placeholderFallback.value,
          format: realFormat.value,
          locale: _ctx.locale,
          minDate: calcMinMaxTime.value.minDate,
          maxDate: calcMinMaxTime.value.maxDate,
          minTime: calcMinMaxTime.value.minTime,
          maxTime: calcMinMaxTime.value.maxTime,
          minutesIncrement: _ctx.minuteStep,
          modelValue: value.value,
          nowButtonLabel: unref(t)("Now"),
          selectText: unref(t)("Pick"),
          sixWeeks: "fair",
          inline: _ctx.inline,
          teleport: _ctx.appendToBody ? targetElement.value || void 0 : false,
          textInput: "",
          weekNumName: unref(weekNumName),
          weekNumbers: _ctx.showWeekNumber ? { type: "iso" } : void 0,
          weekStart: unref(weekStart)
        }, pickerType.value, {
          "onUpdate:modelValue": onUpdateModelValue,
          onBlur: _cache[1] || (_cache[1] = ($event) => emit("blur"))
        }), createSlots({
          "action-buttons": withCtx(() => [
            createVNode(unref(NcButton), {
              size: "small",
              variant: "tertiary",
              onClick: cancelSelection
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(unref(t)("Cancel")), 1)
              ]),
              _: 1
            }),
            createVNode(unref(NcButton), {
              size: "small",
              variant: "primary",
              onClick: selectDate
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(unref(t)("Pick")), 1)
              ]),
              _: 1
            })
          ]),
          "clear-icon": withCtx(({ clear }) => [
            createVNode(unref(NcButton), {
              "aria-label": unref(t)("Clear value"),
              variant: "tertiary-no-background",
              onClick: clear
            }, {
              icon: withCtx(() => [
                createVNode(NcIconSvgWrapper, {
                  inline: "",
                  path: unref(mdiClose),
                  size: 20
                }, null, 8, ["path"])
              ]),
              _: 2
            }, 1032, ["aria-label", "onClick"])
          ]),
          "input-icon": withCtx(() => [
            createVNode(NcIconSvgWrapper, {
              path: unref(mdiCalendarBlank),
              size: 20
            }, null, 8, ["path"])
          ]),
          "clock-icon": withCtx(() => [
            createVNode(NcIconSvgWrapper, {
              inline: "",
              path: unref(mdiClock),
              size: 20
            }, null, 8, ["path"])
          ]),
          "arrow-left": withCtx(() => [
            createVNode(NcIconSvgWrapper, {
              inline: "",
              path: unref(mdiChevronLeft),
              size: 20
            }, null, 8, ["path"])
          ]),
          "arrow-right": withCtx(() => [
            createVNode(NcIconSvgWrapper, {
              inline: "",
              path: unref(mdiChevronRight),
              size: 20
            }, null, 8, ["path"])
          ]),
          "arrow-down": withCtx(() => [
            createVNode(NcIconSvgWrapper, {
              inline: "",
              path: unref(mdiChevronDown),
              size: 20
            }, null, 8, ["path"])
          ]),
          "arrow-up": withCtx(() => [
            createVNode(NcIconSvgWrapper, {
              inline: "",
              path: unref(mdiChevronUp),
              size: 20
            }, null, 8, ["path"])
          ]),
          _: 2
        }, [
          _ctx.showTimezoneSelect ? {
            name: "action-extra",
            fn: withCtx(() => [
              createVNode(_sfc_main$1, {
                modelValue: timezoneId.value,
                "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => timezoneId.value = $event),
                class: "vue-date-time-picker__timezone",
                appendToBody: false,
                inputLabel: unref(t)("Time zone")
              }, null, 8, ["modelValue", "inputLabel"])
            ]),
            key: "0"
          } : void 0
        ]), 1040, ["aria-labels", "autoApply", "class", "cancelText", "clearable", "placeholder", "format", "locale", "minDate", "maxDate", "minTime", "maxTime", "minutesIncrement", "modelValue", "nowButtonLabel", "selectText", "inline", "teleport", "weekNumName", "weekNumbers", "weekStart"]),
        (openBlock(), createBlock(Teleport, {
          to: "body",
          disabled: !_ctx.appendToBody
        }, [
          createBaseVNode("div", _hoisted_2, null, 512)
        ], 8, ["disabled"]))
      ]);
    };
  }
});
const NcDateTimePicker = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-e6654786"]]);
export {
  NcDateTimePicker as default
};
//# sourceMappingURL=index-BcMnKoRR.chunk.mjs.map
