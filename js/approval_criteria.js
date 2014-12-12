$(document).ready(function(){
	//load dos dados ao carregar a página.
	$('.grade_option').toggle($('.average_option').is(':checked'));
	$('.optative_grade_option').toggle($('.optative_approval_option').is(':checked'));

	$('.average_option').click(function(){
		$('.grade_option').toggle(this.checked);
	})

	$('.optative_radio').change(function(e){
		if (e.target.className == 'optative_approval_option') {
			$('.optative_grade_option').show();
		} else {
			$('.optative_grade_option').hide();
		}
		
	})

	//controla a habilitação dos blocos (obrigatórios/optativos).

	$('.mandatory_block').find('input, textarea, button, select').each(function () {
		$(this).prop('disabled', !$('#mandatory_courses_checkbox').is(':checked'));
	});
	

	$('#mandatory_courses_checkbox').click(function() {
		$('.mandatory_block').find('input, textarea, button, select').each(function (e) {
			$(this).prop('disabled', !$('#mandatory_courses_checkbox').is(':checked'));
		});
	});

	$('.optative_block').find('input, textarea, button, select').each(function () {
		$(this).prop('disabled', !$('#optative_courses_checkbox').is(':checked'));
	});
	

	$('#optative_courses_checkbox').click(function() {
		$('.optative_block').find('input, textarea, button, select').each(function (e) {
			$(this).prop('disabled', !$('#optative_courses_checkbox').is(':checked'));
		});
	});

});