function toggleCalendarEventDate(sender) {
    if (sender.checked) {
        var now = new Date();
        if ($('#Form_CalendarEventDate_Day').val() == 0) {
            $('#Form_CalendarEventDate_Day').val(now.getDate());
        }
        if ($('#Form_CalendarEventDate_Month').val() == 0) {
            $('#Form_CalendarEventDate_Month').val(now.getMonth() + 1);
        }
        if ($('#Form_CalendarEventDate_Year').val() == 0) {
            $('#Form_CalendarEventDate_Year').val(now.getFullYear());
        }
        $('.CalendarEventDate').removeClass('Hidden');
    } else {
        $('.CalendarEventDate').addClass('Hidden');    
    }
}

/**
 * Get CalendarModule::ToString() and replace panel box content.
 */
function calendarModule(sender) {
    $.ajax({
        url: gdn.url(sender.value),
    })
    .done(function( data ) {
        calendar = data.substr(56, data.length - 66);
        document.getElementById('CalendarModule').innerHTML = calendar;
    });
}

function calendarModuleShow() {
    $('.CalendarEventList').addClass('Hidden');
    $('.CalendarEvent').addClass('Hidden');
    $('.Calendar').removeClass('Hidden');
    $('#CalendarModule').css('height', '').css('overflow', '');
}

function calendarModuleHide(sender) {
    var cm = $('#CalendarModule');
    cm.height(cm.height());
    $('.Calendar').addClass('Hidden');
    cm.css('overflow', 'scrollY');
    // show 
    $('#Calendar_' + sender.attributes.value.value).removeClass('Hidden');
    $('div.CalendarEventList').removeClass('Hidden');

}
