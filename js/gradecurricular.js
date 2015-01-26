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
});