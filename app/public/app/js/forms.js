// $('#worklogSubmitButton').on('click', function () {
//     // var formData = getFormObj('worklogForm');
//     var formData = $('#worklogForm').serialize();
//     $.ajax({
//         url: '/worklogs/save',
//         type: 'POST',
//         data: {
//             'worklog_form_start': $('#worklogForm input[name="worklog_form_start"]').val(),
//             'worklog_form_time_spent': $('#worklogForm input[name="worklog_form_time_spent"]').val(),
//             'worklog_form_message': $('#worklogForm textarea[name="worklog_form_message"]').val(),
//             'worklog_id': $('#worklogForm input[name="worklog_id"]').val(),
//             'issue_key': $('#worklogForm input[name="issue_key"]').val(),
//         },
//         success: function (response) {
//             $('#editEventModal').modal('hide');
//             calendar.refetchEvents();
//             // alert('Worklog updated successfully!');
//         },
//         error: function () {
//             alert('Error updating worklog.');
//         }
//     });
// });

// $('#confirmDeleteWorklogButton').on('click', function () {
//     // alert('delete');
//     // var worklogId = $('#confirmDeleteWorklogButton').data('worklog-id');
//     $.ajax({
//         url: '/worklogs/delete',
//         type: 'POST',
//         data: {
//             'worklog_id': $('#worklogForm input[name="worklog_id"]').val(),
//             'issue_key': $('#worklogForm input[name="issue_key"]').val(),
//         },
//         success: function (response) {
//             // Handle successful deletion
//             $('#editEventModal').modal('hide');
//             $('#confirmDelete').modal('hide');
//             calendar.refetchEvents();
//         },
//         error: function () {
//             alert('Error deleting worklog.');
//         }
//     });
// });

// $('#goToJiraIssue').on('click', function (event) {
//     event.preventDefault();
//     var url = 'https://tuzubezpieczenia.atlassian.net/browse/' + $('#worklogForm input[name="issue_key"]').val();
//     // window.location = url;
//     window.open(url, '_blank')
// });

// $('#copyToNextDay').on('click', function (event) {
//     event.preventDefault();
//     let date = new Date($('#worklogForm input[name="worklog_form_start"]').val());
//     console.log(date);
//     date.setDate(date.getDate() + 1);
//     console.log(date.toISOString().substring(0, 10));

//     // 'worklog_form_start': $('#worklogForm input[name="worklog_form_start"]').val(),
//     // 'worklog_form_time_spent': $('#worklogForm input[name="worklog_form_time_spent"]').val(),
//     // 'worklog_form_message': $('#worklogForm textarea[name="worklog_form_message"]').val(),
//     // 'worklog_id': $('#worklogForm input[name="worklog_id"]').val(),
//     // 'issue_key': $('#worklogForm input[name="issue_key"]').val(),


//     $.ajax({
//         url: '/worklogs/save-add',
//         type: 'POST',
//         data: {
//             'worklog_add_form_issue_key': $('#worklogForm input[name="issue_key"]').val(),
//             'worklog_add_form_start': date.toISOString().substring(0, 10) + 'T' + $('#worklogForm input[name="worklog_form_start"]').val().substring(11),
//             'worklog_add_form_time_spent': $('#worklogForm input[name="worklog_form_time_spent"]').val(),
//             'worklog_add_form_message': $('#worklogForm textarea[name="worklog_form_message"]').val(),
//         },
//         success: function (response) {
//             $('#addEventModal').modal('hide');
//             calendar.refetchEvents();
//             // alert('Worklog updated successfully!');
//         },
//         error: function () {
//             alert('Error adding worklog.');
//         }
//     });
// });

// $('#worklogAddSubmitButton').on('click', function () {
//     // var formData = getFormObj('worklogForm');
//     var formData = $('#worklogFormAdd').serialize();
//     $.ajax({
//         url: '/worklogs/save-add',
//         type: 'POST',
//         data: {
//             'worklog_add_form_issue_key': $('#worklogFormAdd select[name="worklog_add_form_issue_key"]').val(),
//             'worklog_add_form_start': $('#worklogFormAdd input[name="worklog_add_form_start"]').val(),
//             'worklog_add_form_time_spent': $('#worklogFormAdd input[name="worklog_add_form_time_spent"]').val(),
//             'worklog_add_form_message': $('#worklogFormAdd textarea[name="worklog_add_form_message"]').val(),
//         },
//         success: function (response) {
//             $('#addEventModal').modal('hide');
//             calendar.refetchEvents();
//             // alert('Worklog updated successfully!');
//         },
//         error: function () {
//             alert('Error adding worklog.');
//         }
//     });
// });