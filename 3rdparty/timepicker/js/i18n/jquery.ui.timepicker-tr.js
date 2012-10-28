/* Turkish initialisation for the jQuery time picker plugin. */
/* Written by Mutlu Tevfik Koçak (mtkocak@gmail.com) */
jQuery(function($){
    $.timepicker.regional['tr'] = {
                hourText: 'Saat',
                minuteText: 'Dakika',
                amPmText: ['AM', 'PM'],
                closeButtonText: 'Kapat',
                nowButtonText: 'Şu anda',
                deselectButtonText: 'Seçimi temizle' }
    $.timepicker.setDefaults($.timepicker.regional['tr']);
});