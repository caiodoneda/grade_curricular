$(document).ready(function(){
	var options = $('#menuinscricoesactivityid').find('option');
	options.each(function(k, v) {
		if (v.value != 0 && v.selected) {
			$('#menustudentcohortid').prop('disabled', true);
		}
	});

	$('#menuinscricoesactivityid').change(function(e){
        if (e.target.value == 0) {
			$('#menustudentcohortid').prop('disabled', false);
		} else {
			$('#menustudentcohortid').prop('disabled', true);
		}
	})

	var options = $('#menustudentcohortid').find('option');
	options.each(function(k, v) {
		if (v.value != 0 && v.selected) {
			$('#menuinscricoesactivityid').prop('disabled', true);
		}
	});

	$('#menustudentcohortid').change(function(e){
		if (e.target.value == 0) {
			$('#menuinscricoesactivityid').prop('disabled', false);
		} else {
			$('#menuinscricoesactivityid').prop('disabled', true);
		}
	})
});
