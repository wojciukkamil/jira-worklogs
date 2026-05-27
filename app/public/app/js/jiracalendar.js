
var selectedEvent = null;

function validateTime(str) {
    if (str == '' || str == null) {
        return false;
    }

    // Regex dopasowuje opcjonalne wartości: godziny (h), minuty (m) i sekundy (s) w dowolnej kolejności
    const regex = /^(?:(\d+)\s*h)?\s*(?:(\d+)\s*m)?\s*(?:(\d+)\s*s)?$/i;
    const match = str.trim().match(regex);

    if (!match) {
        return false;
    }

    // Pobieramy grupy dopasowania (jeśli brak, ustawiamy 0) i konwertujemy na liczby
    const hours = parseInt(match[1] || 0, 10);
    const minutes = parseInt(match[2] || 0, 10);
    const seconds = parseInt(match[3] || 0, 10);

    // Opcjonalnie: zabezpieczenie przed błędnymi wartościami (np. 70m)
    if (minutes > 59 || seconds > 59) {
        return false;
    }

    return true;
}

function appNotify(title, message, type = 'default', icon = "none") {
    var content = {};
    content.title = title;
    content.message = message;
    content.icon = icon;
    // content.url = "index.html";
    // content.target = "_blank";

    $.notify(content, {
        type: type,
        placement: {
            from: "top",
            align: "right"
        },
        time: 300000,
        // delay: 2000,
    });
}

function appNotifySuccess(title, message, type = 'success', icon = "fa fa-bell") {
    appNotify(title, message, type, icon);
}

function appNotifyDanger(title, message, type = 'danger', icon = "fa fa-bug") {
    appNotify(title, message, type, icon);
}

function appNotifyInfo(title, message, type = 'info', icon = "fa fa-bell") {
    appNotify(title, message, type, icon);
}

function eventEdit() {
    $.ajax({
        url: "/worklogs/edit",
        data: {
            worklog: selectedEvent.event.extendedProps.worklog,
            issue: selectedEvent.event.extendedProps.issue,
            start: selectedEvent.event.extendedProps.worklog.started,
            end: (selectedEvent.event.end === undefined || selectedEvent.event.end == null || selectedEvent.event.end.length <= 0) ? null : selectedEvent.event.end.toISOString()
        },
        type: "POST",
        success: function (response) {
            $('#editEventModal .modal-body').html(response);
            $('#editEventModal').modal('show');
        },
        error: function (response) {
            console.log(response);
            alert("Error updating worklog");
        }
    }).done(function () {
        $(this).addClass("done");
    });
}

function addHoursToDate(date, hours) {
    return new Date(date.setHours(date.getHours() + hours));
}

function eventCopy(days) {
    event.preventDefault();

    var date = new Date(selectedEvent.event.extendedProps.worklog.started);
    date.setDate(date.getDate() + days);

    let message = '';
    if (selectedEvent.event.extendedProps.worklog.comment.content[0] != undefined) {
        message = selectedEvent.event.extendedProps.worklog.comment.content[0].content[0].text;
    }

    date = addHoursToDate(date, 2);

    $.ajax({
        url: '/worklogs/save-add',
        type: 'POST',
        data: {
            'worklog_add_form_issue_key': selectedEvent.event.extendedProps.issue.key,
            'worklog_add_form_start': date.toISOString().substring(0, 10) + 'T' + date.toISOString().substring(11),
            'worklog_add_form_time_spent': selectedEvent.event.extendedProps.worklog.timeSpent,
            'worklog_add_form_message': message,
        },
        success: function (response) {
            $('#addEventModal').modal('hide');
            appNotifySuccess('Czas pracy został skopiowany!', 'Utworzono analogiczny czas pracy (+' + days + 'd).');
            selectedEvent.view.calendar.refetchEvents();
        },
        error: function () {
            alert('Error adding worklog.');
        }
    });
}

function eventDelete() {
    swal({
        title: 'Czy chcesz usunąć czas pracy?',
        text: "Operacja jest nieodwracalna!",
        type: 'warning',
        buttons: {
            cancel: {
                visible: true,
                text: 'Anuluj',
                className: 'btn btn-danger'
            },
            confirm: {
                text: 'Tak, usuwam',
                className: 'btn btn-success'
            }
        }
    }).then((willDelete) => {
        if (willDelete) {
            $.ajax({
                url: '/worklogs/delete',
                type: 'POST',
                data: {
                    'worklog_id': selectedEvent.event.extendedProps.worklog.id,
                    'issue_key': selectedEvent.event.extendedProps.issue.key,
                },
                success: function (response) {
                    $('#editEventModal').modal('hide');
                    $('#confirmDelete').modal('hide');
                    appNotifySuccess('Pomyślnie usunięto czas pracy!', 'Czas pracy został usunięty.');
                    // swal({
                    //     title: "Pomyślnie usunięto event!",
                    //     text: "Czas pracy został usunięty!",
                    //     icon: "success",
                    //     buttons: {
                    //         confirm: {
                    //             text: "OK",
                    //             value: true,
                    //             visible: true,
                    //             className: "btn btn-success",
                    //             closeModal: true
                    //         }
                    //     }
                    // });
                    selectedEvent.view.calendar.refetchEvents();
                },
                error: function () {
                    alert('Error deleting worklog.');
                }
            });

        } else {
            appNotifyInfo('Anulowano usunięcie czasu pracy!', 'Czas pracy nie został usunięty.');
            // swal("Anulowano usunięcie czasu pracy", {
            //     buttons: {
            //         confirm: {
            //             className: 'btn btn-success'
            //         }
            //     }
            // });
        }
    });
}

function eventShow() {
    $.ajax({
        url: "/worklogs/show",
        data: {
            worklog: selectedEvent.event.extendedProps.worklog,
            issue: selectedEvent.event.extendedProps.issue,
            start: selectedEvent.event.extendedProps.worklog.started,
            end: (selectedEvent.event.end === undefined || selectedEvent.event.end == null || selectedEvent.event.end.length <= 0) ? null : selectedEvent.event.end.toISOString()
        },
        type: "POST",
        success: function (response) {
            $('#showEventModal .modal-body').html(response);
            $('#showEventModal').modal('show');
        },
        error: function (response) {
            console.log(response);
            alert("Error preview worklog");
        }
    }).done(function () {
        $(this).addClass("done");
    });
}

function forwardToJira() {
    var url = 'https://tuzubezpieczenia.atlassian.net/browse/' + selectedEvent.event.extendedProps.issue.key;
    window.open(url, '_blank')
}

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    var loadingEl = document.getElementById('loading');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',    // Widok miesiąca
        allDaySlot: false,
        timeZone: 'Europe/Warsaw',
        locale: 'pl',
        themeSystem: 'bootstrap5',
        editable: true,
        droppable: true,
        selectable: true,
        locale: 'pl',                   // Język polski
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        firstDay: 1,
        weekNumbers: true,
        dayMaxEvents: true,
        slotMinTime: "07:00:00",
        slotMaxTime: "18:00:00",
        contentHeight: 'auto',
        // events: [                       // Przykładowe wydarzenia
        //     {
        //         title: 'Spotkanie projektowe',
        //         start: '2026-05-20'
        //     },
        //     {
        //         title: 'Darmowe szkolenie',
        //         start: '2026-05-25',
        //         end: '2026-05-27'
        //     }
        // ],
        buttonText: {
            today: 'Dzisiaj',
            month: 'Miesiąc',
            day: 'Dzień',
            week: 'Tydzień'
        },
        // monthNames: ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'],
        // monthNamesShort: ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'],
        // dayNames: ['Niedziela', 'Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota'],
        // dayNamesShort: ['Nie', 'Pon', 'Wto', 'Śro', 'Czw', 'Pią', 'Sob'],
        eventSources: [{
            url: '/calendar/load',
            method: 'POST',
            extraParams: { filters: JSON.stringify({}) }
        }],
        loading: function (isLoading) {
            if (isLoading) {
                loadingEl.style.display = 'block';
                document.querySelector('.loader-overlay').style.display = 'block';
                // showLoader();
            } else {
                loadingEl.style.display = 'none';
                // hideLoader();
                document.querySelector('.loader-overlay').style.display = 'none';
            }
        },
        dayHeaderDidMount: function (arg) {
            // Pobierz datę dla nagłówka
            const dateStr = arg.date.toISOString().split('T')[0]; // YYYY-MM-DD
            const htmlContent = '<div>' + arg.text + '</div>' +
                '<div class="header_summary_time header_summary_time_' + dateStr + '" style="font-weight:normal; font-size:10px; color:green;">' +
                '---' +
                '</div>';
            arg.el.innerHTML = htmlContent;
        },
        eventsSet: function (info) {
            $(".header_summary_time").html('---');
            let events = calendar.getEvents();
            // console.log('Events in view sssssssssssssss: ', events);
            // let element = document.querySelector('.fc-col-header-cell [data-date="2026-04-24"]');
            // let element = $(".header_summary_time_2026-04-24");
            //             console.log('Header element for 2026-04-24: ', element);
            let days = [];
            let times = {};
            $.each(events, function (key, event) {
                // here you can access all the properties just by typing either value.propertyName or value["propertyName"]
                // example: value.ri_idx; value.ri_startDate; value.ri_endDate;
                days.push(event.start.toISOString().substring(0, 10));
                // console.log('Event time spent: ', event.extendedProps.timeInterval);

                if (event.start && event.end) {
                    let diff = Math.abs(event.end - event.start) / (1000 * 60);
                    times[event.start.toISOString().substring(0, 10)] = (times[event.start.toISOString().substring(0, 10)] || 0) + diff;
                }
            });
            // console.log('days: ', days);
            // console.log('times: ', times);


            $.each(times, function (key, dayTime) {
                let hours = Math.floor(dayTime / 60);
                let mins = dayTime % 60;
                let timeStr = hours + 'h ' + (mins > 0 ? mins + 'm' : '');
                $(".header_summary_time_" + key).html(timeStr);
            });
        },
        select: function (info) {
            // alert('Selected: ' + info.startStr + ' to ' + info.endStr);
            $.ajax({
                url: "/worklogs/add",
                data: {
                    'worklog_started': info.startStr,
                    'worklog_ended': info.endStr
                },
                type: "POST",
                success: function (response) {
                    $('#addEventModal .modal-body').html(response);
                    $('#addEventModal').modal('show');

                },
                error: function (response) {
                    alert("Error added worklog");
                }
            }).done(function () {
                $(this).addClass("done");
            });
            // Open form to add event in this time range
        },
        eventDrop: function (info) {
            $.ajax({
                url: "/worklogs/update",
                data: {
                    'worklog_started': info.event.start.toISOString(),
                    'worklog_ended': info.event.end.toISOString(),
                    'worklog': info.event.extendedProps.worklog,
                    'issue': info.event.extendedProps.issue,
                },
                type: "POST",
                success: function (response) {
                    // swal({
                    //     title: "Pomyślnie przeniesiono event!",
                    //     text: "Event został przeniesiony!",
                    //     icon: "success",
                    //     buttons: {
                    //         confirm: {
                    //             text: "OK",
                    //             value: true,
                    //             visible: true,
                    //             className: "btn btn-success",
                    //             closeModal: true
                    //         }
                    //     }
                    // });
                    appNotifySuccess('Pomyślnie przeniesiono event!', 'Event został przeniesiony.');
                    info.view.calendar.refetchEvents();

                },
                error: function (response) {
                    alert("Error updating worklog");
                }
            }).done(function () {
                $(this).addClass("done");
            });
        },
        eventResize: function (info) {
            // console.log(info.event);
            $.ajax({
                url: "/worklogs/update",
                data: {
                    worklog_started: info.event.start.toISOString(),
                    worklog_ended: info.event.end.toISOString(),
                    worklog: info.event.extendedProps.worklog,
                    issue: info.event.extendedProps.issue,
                },
                type: "POST",
                success: function (response) {
                    // swal({
                    //     title: "Pomyślnie zaktualizowano czas pracy!",
                    //     text: "Czas pracy został zaktualizowany!",
                    //     icon: "success",
                    //     buttons: {
                    //         confirm: {
                    //             text: "OK",
                    //             value: true,
                    //             visible: true,
                    //             className: "btn btn-success",
                    //             closeModal: true
                    //         }
                    //     }
                    // });
                    appNotifySuccess('Pomyślnie zaktualizowano czas pracy!', 'Czas pracy został zaktualizowany.');
                    info.view.calendar.refetchEvents();
                },
                error: function (response) {
                    alert("Error updating worklog");
                }
            }).done(function () {
                $(this).addClass("done");
            });
        },
        eventContent: function (info) {
            let title = info.event.title;
            let icon = info.event.extendedProps.icon;
            let desc = info.event.extendedProps.description;
            let timeInterval = info.event.extendedProps.timeInterval;
            if (info.view.type != 'dayGridMonth') {
                return {
                    html: `<div class="calendar-event" title="${title} - ${desc}"><img class="issue-item-avatar" src="${icon}"/><b>${title}</b> <span class="badge text-bg-secondary rounded-pill spent-time">${timeInterval}</span><br/><div class="event-desc">${desc}</div></div>`
                };
            } else {
                return {
                    html: `<div title="${info.event.start} - ${info.event.end} ${title} - ${desc}"><b><img class="issue-item-avatar" src="${icon}"/>${title}</b> <span class="badge text-bg-secondary rounded-pill">${timeInterval}</span></div>`
                };
            }
        },
        eventDidMount: function (info) {
            info.el.addEventListener('contextmenu', function (e) {
                e.preventDefault(); // Prevent the browser's default right-click menu

                // 3. Trigger your custom context menu function
                showCustomMenu(e.pageX, e.pageY, info);
            });
        },
        eventClick: function (info) {
            $.ajax({
                url: "/worklogs/edit",
                data: {
                    worklog: info.event.extendedProps.worklog,
                    issue: info.event.extendedProps.issue,
                    start: info.event.extendedProps.worklog.started,
                    end: (info.event.end === undefined || info.event.end == null || info.event.end.length <= 0) ? null : info.event.end.toISOString()
                },
                type: "POST",
                success: function (response) {
                    $('#editEventModal .modal-body').html(response);
                    $('#editEventModal').modal('show');

                },
                error: function (response) {
                    console.log(response);
                    alert("Error updating worklog");
                }
            }).done(function () {
                $(this).addClass("done");
            });
        },
        // Odpala się po kliknięciu w wydarzenie
        // eventClick: function(calEvent, jsEvent, view) {
        //     selectedEvent = calEvent; // Zapisujemy kliknięte wydarzenie

        //     // Wyświetlamy menu w miejscu kliknięcia myszką
        //     $('#action-menu')
        //         .css({
        //             top: jsEvent.pageY + 10,
        //             left: jsEvent.pageX + 10
        //         })
        //         .show();

        //     // Zatrzymujemy propagację, żeby nie wywoływać innych zdarzeń
        //     return false;
        // },
        // Ukrywamy menu po kliknięciu gdziekolwiek indziej
        // dayClick: function() {
        //     $('#action-menu').hide();
        // }
    });

    calendar.render();

    function showCustomMenu(x, y, info) {
        // console.log(info.el.style('ba'));

        if (selectedEvent) {
            selectedEvent.el.style.backgroundColor = '';
        }

        selectedEvent = info;
        // $('.fc-event').style.backgroundColor = '';
        selectedEvent.el.style.backgroundColor = '#48abf7';
        let menu = document.getElementById('event-context-menu');
        if (!menu) {
            menu = document.createElement('div');
            menu.id = 'event-context-menu';
            menu.style.position = 'absolute';
            menu.style.background = '#fff';
            menu.style.border = '1px solid #ccc';
            menu.style.boxShadow = '2px 2px 5px rgba(0,0,0,0.2)';
            // menu.style.zIndex = '1000';
            menu.innerHTML = `
                <ul class="dropdown-menu alert-success" role="menu" style="list-style: none; margin: 0; padding: 10px; display:block;">
                    <li>
                        <div class="dropdown-item" onClick="eventEdit();"><i class="fas fa-edit"></i> Edytuj czas pracy</div>
                        <div class="dropdown-item" onClick="eventCopy(1);"><i class="fas fa-copy"></i> Powiel czas pracy (kilejny dzień [+1d])</div>
                        <div class="dropdown-item" onClick="eventCopy(7);"><i class="fas fa-copy"></i> Powiel czas pracy (kilejny tydzień [+7d])</div>
                        <div class="dropdown-item" onClick="eventDelete();"><i class="fas fa-trash-alt"></i> Usuń czas pracy</div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-item" onClick="eventShow();"><i class="fas fa-search"></i> Wyświetl szczegóły czasu pracy</div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-item" onClick="forwardToJira();"><i class="fas fa-fast-forward"></i> Przejdź do zadania JIRA</div>
                    </li>
                </ul>
                `;
            document.body.appendChild(menu);
            // <ul class="dropdown-menu" role="menu" style="">
            // 								<li>
            // 									<a class="dropdown-item" href="#">Action</a>
            // 									<a class="dropdown-item" href="#">Another action</a>
            // 									<div class="dropdown-divider"></div>
            // 									<a class="dropdown-item" href="#">Something else here</a>
            // 								</li>
            // 							</ul>
            //   <ul style="list-style: none; margin: 0; padding: 10px;">
            //     <li id="menu-edit" style="padding: 5px 10px; cursor: pointer;">Edit Event</li>
            //     <li id="menu-delete" style="padding: 5px 10px; cursor: pointer; color: red;">Delete Event</li>
            //   </ul>

            // Event listener for menu items
            menu.addEventListener('click', function (e) {
                if (e.target.id === 'menu-edit') {
                    console.log(`Editing event ${eventId}: ${eventTitle}`);
                    // Add your edit logic here
                } else if (e.target.id === 'menu-delete') {
                    console.log(`Deleting event ${eventId}`);
                    // Add your delete logic here
                }
                menu.style.display = 'none'; // Hide menu after click

                //     $('.fc-event').style.backgroundColor = '';
                // selectedEvent.el.style.backgroundColor = '';
            });
        }

        // Position and display the menu
        menu.style.left = `${x}px`;
        menu.style.top = `${y}px`;
        menu.style.display = 'block';

        // Hide menu on any click outside
        document.addEventListener('click', function hideMenu() {
            menu.style.display = 'none';
            selectedEvent.el.style.backgroundColor = '';

            document.removeEventListener('click', hideMenu);
        }, { once: true });
    }

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#action-menu').length) {
            $('#action-menu').hide();
        }
    });

    $('#worklogSubmitButton').on('click', function () {
        var formData = $('#worklogForm').serialize();

        if (!validateTime($('#worklogForm input[name="worklog_form_time_spent"]').val())) {
            swal("Wystąpił błąd!", 'Nieporawny format daty.', {
                icon: "error",
                buttons: {
                    confirm: {
                        className: 'btn btn-danger'
                    }
                },
            });
        } else {
            $.ajax({
                url: '/worklogs/save',
                type: 'POST',
                data: {
                    'worklog_form_start': $('#worklogForm input[name="worklog_form_start"]').val(),
                    'worklog_form_time_spent': $('#worklogForm input[name="worklog_form_time_spent"]').val(),
                    'worklog_form_message': $('#worklogForm textarea[name="worklog_form_message"]').val(),
                    'worklog_id': $('#worklogForm input[name="worklog_id"]').val(),
                    'issue_key': $('#worklogForm input[name="issue_key"]').val(),
                },
                success: function (response) {
                    $('#editEventModal').modal('hide');
                    // swal({
                    //     title: "Pomyślnie zapisano zmiany!",
                    //     text: "Event został zaktualizowany!",
                    //     icon: "success",
                    //     buttons: {
                    //         confirm: {
                    //             text: "OK",
                    //             value: true,
                    //             visible: true,
                    //             className: "btn btn-success",
                    //             closeModal: true
                    //         }
                    //     }
                    // });

                    calendar.refetchEvents();

                    var content = {};

                    appNotifySuccess('Pomyślnie zapisano zmiany!', 'Czas pracy został zaktualizowany.');





                    //                       $.notify({
                    // 	icon: 'fa fa-bell',
                    // 	title: 'Kaiadmin zaktalizowano',
                    // 	message: 'Premium Bootstrap 5 Admin Dashboard',
                    // },{
                    // 	type: 'success',
                    // 	placement: {
                    // 		from: "top",
                    // 		align: "right"
                    // 	},
                    // 	// time: 3000,
                    // });
                },
                error: function () {
                    appNotifyDanger('Wystąpił błąd!', 'Wystąpił błąd podczas zapisu zdarzenia.');
                    // swal("Wystąpił błąd!", 'Wystąpił błąd podczas zapisu zdarzenia', {
                    //     icon: "error",
                    //     buttons: {
                    //         confirm: {
                    //             className: 'btn btn-danger'
                    //         }
                    //     },
                    // });
                }
            });
        }
    });

    $('#confirmDelete').on('click', function () {
        swal({
            title: 'Czy chcesz usunąć czas pracy?',
            text: "Operacja jest nieodwracalna!",
            type: 'warning',
            buttons: {
                cancel: {
                    visible: true,
                    text: 'Anuluj',
                    className: 'btn btn-danger'
                },
                confirm: {
                    text: 'Tak, usuwam',
                    className: 'btn btn-success'
                }
            }
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: '/worklogs/delete',
                    type: 'POST',
                    data: {
                        'worklog_id': $('#worklogForm input[name="worklog_id"]').val(),
                        'issue_key': $('#worklogForm input[name="issue_key"]').val(),
                    },
                    success: function (response) {
                        $('#editEventModal').modal('hide');
                        $('#confirmDelete').modal('hide');
                        appNotifySuccess('Pomyślnie usunięto czas pracy!', 'Czas pracy został usunięty.');
                        // swal({
                        //     title: "Pomyślnie usunięto event!",
                        //     text: "Czas pracy został usunięty!",
                        //     icon: "success",
                        //     buttons: {
                        //         confirm: {
                        //             text: "OK",
                        //             value: true,
                        //             visible: true,
                        //             className: "btn btn-success",
                        //             closeModal: true
                        //         }
                        //     }
                        // });
                        calendar.refetchEvents();
                    },
                    error: function () {
                        alert('Error deleting worklog.');
                    }
                });

            } else {
                appNotifyInfo('Anulowano usunięcie czasu pracy!', 'Czas pracy nie został usunięty.');
                // swal("Anulowano usunięcie czasu pracy", {
                //     buttons: {
                //         confirm: {
                //             className: 'btn btn-success'
                //         }
                //     }
                // });
            }
        });

    });

    $('#goToJiraIssue').on('click', function (event) {
        event.preventDefault();
        var url = 'https://tuzubezpieczenia.atlassian.net/browse/' + $('#worklogForm input[name="issue_key"]').val();
        window.open(url, '_blank')
    });

    $('#copyToNextDay').on('click', function (event) {
        event.preventDefault();
        let date = new Date($('#worklogForm input[name="worklog_form_start"]').val());
        date.setDate(date.getDate() + 1);
        $.ajax({
            url: '/worklogs/save-add',
            type: 'POST',
            data: {
                'worklog_add_form_issue_key': $('#worklogForm input[name="issue_key"]').val(),
                'worklog_add_form_start': date.toISOString().substring(0, 10) + 'T' + $('#worklogForm input[name="worklog_form_start"]').val().substring(11),
                'worklog_add_form_time_spent': $('#worklogForm input[name="worklog_form_time_spent"]').val(),
                'worklog_add_form_message': $('#worklogForm textarea[name="worklog_form_message"]').val(),
            },
            success: function (response) {
                $('#addEventModal').modal('hide');
                appNotifySuccess('Czas pracy został skopiowany!', 'Utworzono analogiczny czas pracy kolejnego dnia.');
                calendar.refetchEvents();
            },
            error: function () {
                alert('Error adding worklog.');
            }
        });
    });

    $('#worklogAddSubmitButton').on('click', function () {
        var formData = $('#worklogFormAdd').serialize();

        if ($('#worklogFormAdd select[name="worklog_add_form_issue_key"]').val() == '') {
            appNotifyDanger('Wystąpił błąd!', 'Musisz wybrać zadanie.');
        } else if (!validateTime($('#worklogFormAdd input[name="worklog_add_form_time_spent"]').val())) {

            appNotifyDanger('Wystąpił błąd!', 'Nieporawny format daty.');
        } else {
            $.ajax({
                url: '/worklogs/save-add',
                type: 'POST',
                data: {
                    'worklog_add_form_issue_key': $('#worklogFormAdd select[name="worklog_add_form_issue_key"]').val(),
                    'worklog_add_form_start': $('#worklogFormAdd input[name="worklog_add_form_start"]').val(),
                    'worklog_add_form_time_spent': $('#worklogFormAdd input[name="worklog_add_form_time_spent"]').val(),
                    'worklog_add_form_message': $('#worklogFormAdd textarea[name="worklog_add_form_message"]').val(),
                },
                success: function (response) {
                    $('#addEventModal').modal('hide');
                    appNotifySuccess('Pomyślnie dodano czas pracy!', 'Czas pracy został dodany!');
                    // swal({
                    //     title: "Pomyślnie dodano czas pracy!",
                    //     text: "Czas pracy został dodany!",
                    //     icon: "success",
                    //     buttons: {
                    //         confirm: {
                    //             text: "OK",
                    //             value: true,
                    //             visible: true,
                    //             className: "btn btn-success",
                    //             closeModal: true
                    //         }
                    //     }
                    // });
                    calendar.refetchEvents();
                },
                error: function (xhr, status, error) {
                    swal("Wystąpił błąd!", 'Wystąpił błąd podczas dodawania zdarzenia', {
                        icon: "error",
                        buttons: {
                            confirm: {
                                className: 'btn btn-danger'
                            }
                        },
                    });
                }
            });
        }
    });

});


// const menu = document.getElementById('myContextMenu');
// const area = document.getElementsByClassName('calendar-event');

// // Funkcja pokazująca menu
// function showContextMenu(x, y) {
//   menu.style.left = `${x}px`;
//   menu.style.top = `${y}px`;
//   menu.style.display = 'block';
//   $('.fc-timegrid-event-harness .fc-timegrid-event-harness-inset').style('z-index', null);
// }

// // Nasłuchiwanie kliknięcia prawym przyciskiem
// area.addEventListener('contextmenu', (e) => {
//   e.preventDefault(); // Blokuje domyślne menu przeglądarki
//   showContextMenu(e.clientX, e.clientY);
// });

// // Ukrywanie menu po kliknięciu w inne miejsce
// document.addEventListener('click', () => {
//   menu.style.display = 'none';
// });



// <!-- javascript for init -->
// type = ['','info','success','warning','danger'];

// function showNotification(from, align){
//   color = Math.floor((Math.random() * 4) + 1);

//   $.notify({
//       icon: "tim-icons icon-bell-55",
//       message: "Welcome to <b>Black Dashboard Pro</b> - a beautiful freebie for every web developer."

//     },{
//         type: type[color],
//         timer: 8000,
//         placement: {
//             from: from,
//             align: align
//         }
//     });
// }
