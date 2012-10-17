/* Croatian/Bosnian initialisation for the timepicker plugin */
/* Written by Rene Brakus (rene.brakus@infobip.com). */
jQuery(function($){
    $.timepicker.regional['hr'] = {
                hourText: 'Sat',
                minuteText: 'Minuta',
                amPmText: ['Prijepodne', 'Poslijepodne'],
                closeButtonText: 'Zatvoriti',
                nowButtonText: 'Sada',
                deselectButtonText: 'Poništite'}

    $.timepicker.setDefaults($.timepicker.regional['hr']);
});