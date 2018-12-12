;(function ($) {
	$(document).ready(function ($) {
		console.log('dashboard');
		let ajax = null;

		function callAjax () {
			if (ajax != null) {
				ajax.abort();
			}
			let dataAjax = {
				action: 'shortcode_dashboard'
			};

			ajax = $.ajax({
				url: ajaxDashboardData.url,
				data: dataAjax,
				type: 'POST',
				dataType: 'json',
				success: function (data) {
					console.log(data);
					if (data.status) {
						$('#number_freelancer').fadeOut(200, function () {
							$(this).text(data.number_freelancer).fadeIn();
						});
						$('#number_tasks').fadeOut(200, function () {
							$(this).text(data.number_tasks).fadeIn();
						});
					} else {
						console.log(data);
					}
				},
				error: function (xhr) {
					console.log(xhr.statusText);
				},
				complete: function () {
					setTimeout(callAjax, ajaxDashboardData.timeout_ajax);
				}
			});

		}

		if ($('#number_freelancer').length && $('#number_tasks').length) {
			setTimeout(callAjax, ajaxDashboardData.timeout_ajax);
		}

	});
})(jQuery);
