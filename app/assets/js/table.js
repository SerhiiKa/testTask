;(function ($) {
	$(document).ready(function ($) {
		console.log('tasks');
		$('#tasks_table').DataTable();

		$('body').on('click', '#addTaskModal', function () {
			event.preventDefault();
			var taskTitle = $('#inputTaskTitle').val();
			var freelancerName = $('#freelancer_for_task_select').val();
			var dataAjax = {
				action: 'add_task',
				data: {
					'task_title': taskTitle,
					'freelancer_id': freelancerName
				},
				nonce_code : ajaxData.nonce
			};

			console.log(dataAjax)


			$.ajax({
				url: ajaxData.url,
				data: dataAjax,
				type: 'POST',
				dataType: 'json',
				success: function (data) {
					console.log(data)
					if (data.status){
						alert('Success!');
					} else {
						alert(data.msg);
					}

					location.reload();
				},
				error: function (xhr) {
					alert('Ajax request fail');
					location.reload();
				}
			});
		});
	});
})(jQuery);
