$().ready(function() {
	$(".datepicker").datepicker({
			changeMonth: true,
			changeYear: true
	});
	$( ".datepicker" ).datepicker( "option", "dateFormat", "dd/mm/yy");

	$(".checkbox_div .select_all").click(function(){
		$(this).parent().children("input").each(function(){
			$(this).attr("checked", true);
		});

		// Ajoute tout à la liste des ordres
		$("#sortable li").each(function(){$(this).remove()});
		$(this).parent().children("input").each(function(){
			var id = $(this).val();
			var name = $(this).attr("data-name");
			$("#sortable").append('<li data-id="'+ id +'" class="ui-state-default sortable">'+ name +'</li>');
		});
		return false;
	});

	$(".checkbox_div .select_none").click(function(){
		$(this).parent().children("input").each(function(){
			$(this).attr("checked", false);
		});
		$("#sortable li").each(function(){$(this).remove()});
		return false;
	});

	// Update sort list
	$("input").change(function(){
		if($(this).attr("checked") == false){
			var id = $(this).val();
			$("#sortable li").each(function(){
				if($(this).attr("data-id") == id)
					$(this).remove();
			});
		} else {
			var id = $(this).val();
			var name = $(this).attr("data-name");
			$("#sortable").append('<li data-id="'+ id +'" class="ui-state-default sortable">'+ name +'</li>');
		}

		update_order();
	});

	$( "#sortable" ).sortable({
		placeholder: "ui-state-highlight",
		update:  update_order
	});
	$( "#sortable" ).disableSelection();

	$("a.confirm").click(function(){
		return confirm("Êtes-vous sûr ?");
	});

	$("h3.select_all_none").css("cursor", "pointer");

	// Select all and none input when clicking on h3 
	$("h3.select_all_none").click(function(){
		if($(this).attr("data-unchecked") == "true"){
			$(this).attr("data-unchecked", "false");
			$(this).next("div").children("input").each(function(){
				$(this).attr("checked", true);
			});
		} else {
			$(this).attr("data-unchecked", "true");
			$(this).next("div").children("input").each(function(){
				$(this).attr("checked", false);
			});
		}
	});

});

function update_order(){
	var order = [];
	$("#sortable li").each(function(){
		order.push($(this).attr("data-id"));
	});
	$("#order").val(order.join(","));
}
