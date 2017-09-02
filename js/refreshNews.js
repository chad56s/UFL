
function refreshNews(){
	$(".news:has(span[class='liveNews'])").each(function(){
			var n = $(this);
			$(this).parent().load('ajaxHandlers/getNews.php?id=' + n.attr("id"));
		})
	//alert($(".news:has(span[class=)").parent().find(".news:first").attr("id"));
}


setInterval('refreshNews()',30000);
