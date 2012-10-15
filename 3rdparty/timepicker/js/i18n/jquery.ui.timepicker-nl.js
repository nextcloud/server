/* Nederlands initialisation for the timepicker plugin */
/* Written by Lowie Hulzinga. */
jQuery(function($){
    $.timepicker.regional['nl'] = {
                hourText: 'Uren',
                minuteText: 'Minuten',
                amPmText: ['AM', 'PM'],
				closeButtonText: 'Sluiten',
				nowButtonText: 'Actuele tijd',
				deselectButtonText: 'Wissen' }
    $.timepicker.setDefaults($.timepicker.regional['nl']);
});