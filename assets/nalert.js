jQuery( function ( $ ) {
	validate_nalert = function(){
		return true;
	}
	$('.nalert_post_meta').change(function(){
		$id = this.value;
		if( this.checked ) 
			$("#check_"+$id).show();
		else
			$("#check_"+$id).hide();
	});
	show_more_menu = function(){
		if($("#extraPluginAlert").length){
			$a = $("#extraPluginAlert").html();
			notif({
					msg: $a+"<br/><b>Success:</b> In One minute i'll be gone",
					type: "success",
					width: 1000,
					height: 60,
					position: "center",
					timeout: 60000,
			});
		}
		
	}
	$(window).on('load', function() {
		show_more_menu ();
	});
});