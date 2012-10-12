/* Polish initialisation for the timepicker plugin */
/* Written by Mateusz Wadolkowski (mw@pcdoctor.pl). */
jQuery(function($){
    $.timepicker.regional['pl'] = {
                hourText: 'Godziny',
                minuteText: 'Minuty',
                amPmText: ['', ''],
				closeButtonText: 'Zamknij',
                nowButtonText: 'Teraz',
                deselectButtonText: 'Odznacz'}
    $.timepicker.setDefaults($.timepicker.regional['pl']);
});