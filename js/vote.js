(function ($) {
	$(function () {
	
		$("#polling-place a").click(function (evt){
		
			evt.preventDefault()
	
			var post_id, vote_type, selected_vote, polling_percentage_bar, polling_percentage_text;
			
			post_id = $(this).parent().data("post-id");
			vote_type = $(this).attr("id");
			selected_vote = $(this);
			polling_percentage_bar = $("#percentage-filled");
			polling_percentage_text = $("#percentage-filled span");
	
			$.ajax({
			
				type: "post",
				url: wpordie_var.url,
				data: "action=add-vote&nonce="+wpordie_var.nonce+"&post_id="+post_id+"&vote_type="+vote_type,
				
				success: function (new_percentage) {
				
					// If the vote is successful, add some visual effects and update the percentage.
					if(new_percentage != "failed") {
					
						selected_vote.addClass("vote-selected");
						
						selected_vote.siblings()
							.addClass("vote-not-selected");
		
						polling_percentage_text.html(new_percentage+"%");
		
						polling_percentage_bar.attr("style", "width:"+new_percentage+"%");
						
					}
	
				}
			});
	
		});
	
	});
}(jQuery));