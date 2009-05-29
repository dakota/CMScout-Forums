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
});