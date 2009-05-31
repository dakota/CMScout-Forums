$(function()
{
	$(".lockAction").click(function(){
		var _this = this;
		$.jGrowl('Saving');
		$.get($(this).attr('href'), function(response){
			if($(_this).hasClass('sprite-lock'))
			{
				$(_this).removeClass('sprite-lock').addClass('sprite-unlock');
			}
			else if ($(_this).hasClass('sprite-unlock'))
			{
				$(_this).removeClass('sprite-unlock').addClass('sprite-lock');
			}
			$.jGrowl('Saved');
		});
		
		return false;
	});
	
	$(".deleteAction").click(function(){
		var _this = this;
		jConfirm('Are you sure you want to delete this thread?', 'Delete thread', function(selection){
			if (selection)
			{
				window.location = $(_this).attr('href');
			}
		});
		return false;
	});
	
	$(".moveAction").click(function() {
		var _this = this;
		$("#genericDialog").html('<div class="loading">&nbsp;</div>');
		$("#genericDialog").load($(this).attr('href'), function(){
			var buttons = {
					'Cancel' : function() {
						$("#genericDialog").dialog('option', 'buttons', {});
						$("#genericDialog").dialog('close');
					},
					'Move' : function() {
						var newForum = $("#forumMove").val();
						window.location = $(_this).attr('href') + '/' + newForum;
						$("#genericDialog").dialog('close');
					}
				};
			$("#genericDialog").dialog('option', 'buttons', buttons);			
		});
		$("#genericDialog").dialog('option', 'position', 'middle');
		$("#genericDialog").dialog('option', 'min-height', '0px');
		$("#genericDialog").dialog('option', 'height', 'auto');
		$("#genericDialog").dialog('option', 'title', 'Move thread');
		$("#genericDialog").dialog('open');		
		return false;
	})
	
});