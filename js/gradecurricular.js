$(document).ready(function(){
	var options = $('#menuinscricoeseditionid').find('option');
	options.each(function(k, v) {
		if (v.value != 0 && v.selected) {
			$('#menustudentcohortid').prop('disabled', true);
		}
	});

	$('#menuinscricoeseditionid').change(function(e){
		if (e.target.value == 0) {
			$('#menustudentcohortid').prop('disabled', false);
		} else {
			$('#menustudentcohortid').prop('disabled', true);
		}
	})

	var options = $('#menustudentcohortid').find('option');
	options.each(function(k, v) {
		if (v.value != 0 && v.selected) {
			$('#menuinscricoeseditionid').prop('disabled', true);
		}
	});

	$('#menustudentcohortid').change(function(e){
		if (e.target.value == 0) {
			$('#menuinscricoeseditionid').prop('disabled', false);
		} else {
			$('#menuinscricoeseditionid').prop('disabled', true);
		}
	})
});
