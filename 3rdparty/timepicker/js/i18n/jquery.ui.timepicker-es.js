/* Spanish initialisation for the jQuery time picker plugin. */
/* Writen by Jandro González (agonzalezalves@gmail.com) */
jQuery(function($){
    $.timepicker.regional['es'] = {
                hourText: 'Hora',
                minuteText: 'Minuto',
                amPmText: ['AM', 'PM'],
                closeButtonText: 'Aceptar',
                nowButtonText: 'Ahora',
                deselectButtonText: 'Deseleccionar' }
    $.timepicker.setDefaults($.timepicker.regional['es']);
});
