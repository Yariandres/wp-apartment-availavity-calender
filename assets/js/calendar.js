jQuery(document).ready(function ($) {
  // Check if calendar data is available
  if (typeof aacCalendarData === 'undefined') {
    console.error('Apartment Availability Calendar: Calendar data not found');
    return;
  }

  // Initialize calendar
  const calendarEl = document.getElementById('aac-calendar');
  if (!calendarEl) {
    console.error(
      'Apartment Availability Calendar: Calendar element not found'
    );
    return;
  }
  // Create events array
  let events = [];

  // Add apartment 1 events
  if (aacCalendarData.apartment1 && aacCalendarData.apartment1.events) {
    aacCalendarData.apartment1.events.forEach(function (event) {
      events.push({
        title: event.title,
        start: event.start,
        end: event.end,
        color: aacCalendarData.apartment1.color,
        apartment: aacCalendarData.apartment1.name,
      });
    });
  }

  // Add apartment 2 events
  if (aacCalendarData.apartment2 && aacCalendarData.apartment2.events) {
    aacCalendarData.apartment2.events.forEach(function (event) {
      events.push({
        title: event.title,
        start: event.start,
        end: event.end,
        color: aacCalendarData.apartment2.color,
        apartment: aacCalendarData.apartment2.name,
      });
    });
  }

  // Initialize FullCalendar
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    events: events,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,multiMonthYear',
    },
    height: 'auto',
    firstDay: 1, // Start week on Monday
  });

  calendar.render();

  const legendHtml =
    '<div class="aac-legend">' +
    '<div class="aac-legend-item">' +
    '<div class="aac-legend-color" style="background-color: ' +
    aacCalendarData.apartment1.color +
    '"></div>' +
    '<div>' +
    aacCalendarData.apartment1.name +
    '</div>' +
    '</div>' +
    '<div class="aac-legend-item">' +
    '<div class="aac-legend-color" style="background-color: ' +
    aacCalendarData.apartment2.color +
    '"></div>' +
    '<div>' +
    aacCalendarData.apartment2.name +
    '</div>' +
    '</div>' +
    '</div>';

  $(calendarEl).before(legendHtml);
});
