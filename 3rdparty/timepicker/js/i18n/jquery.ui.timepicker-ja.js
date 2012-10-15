/* Japanese initialisation for the jQuery time picker plugin. */
/* Written by Bernd Plagge (bplagge@choicenet.ne.jp). */
jQuery(function($){
    $.timepicker.regional['ja'] = {
                hourText: '時間',
                minuteText: '分',
                amPmText: ['午前', '午後'],
                closeButtonText: '閉じる',
                nowButtonText: '現時',
                deselectButtonText: '選択解除' }
    $.timepicker.setDefaults($.timepicker.regional['ja']);
});
