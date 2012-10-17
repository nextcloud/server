/* Swedish initialisation for the timepicker plugin */
/* Written by Björn Westlin (bjorn.westlin@su.se). */
jQuery(function($){
    $.timepicker.regional['sv'] = {
                hourText: 'Timme',
                minuteText: 'Minut',
                amPmText: ['AM', 'PM'] ,
                closeButtonText: 'Stäng',
                nowButtonText: 'Nu',
                deselectButtonText: 'Rensa' }
    $.timepicker.setDefaults($.timepicker.regional['sv']);
});
