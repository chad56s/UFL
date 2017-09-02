$( function(){
	$('.news')
	.attr("editable",true)
	.click(function(){
	      var self = this;
			var newsItem = $(this);
			var newsItemId = newsItem.attr("id");
			var originalNews = $(this).html();
			
			this.saveNews = function()
			{
			   newsContent = unescape($("textarea[name='newsEdit']",newsItem).val());
            newsItem.html(newsContent);
            newsItem.attr("editable",true);
            submitNews(newsItemId,newsContent);
			}
			
			this.cancelNews = function()
			{
            alert('cancelled');
            newsItem.html(originalNews);
            newsItem.attr("editable",true);
			}
			
			if(newsItem.attr("editable")){
				$(this)
					.attr("editable",false)
					.html('<textarea name="newsEdit" rows=' + Math.max($(this).height()/10,20) + ' cols=' + Math.max($(this).width()/10,40) + '>' + $(this).html() + '</textarea>')
					.append('<br/><button id="btnBoxScore">Box Score</button><button id="btnTop10">Top 10</button><br/><button id="btnSave">Save</button><button id="btnCancel">Cancel</button>');
				$("textarea[name='newsEdit']",newsItem)
					.click(function(e){
						e.stopPropagation();
						})
					.bind('keydown',function(e){
                  e.stopPropagation();
						if(e.keyCode == 83 && e.ctrlKey==true)
						{
							self.saveNews()
						}
						else if(e.keyCode == 81 && e.ctrlKey==true)
						{
							self.cancelNews();
						}
					});

				$("#btnBoxScore",newsItem).click(function(e){
	 				e.stopPropagation();
	 				$.get('news/boxScore.html',null,
	  				(function(o){
	     				var curVal = $("textarea[name='newsEdit']",newsItem).val();
	     				$("textarea[name='newsEdit']",newsItem).val(curVal + o);
	   				}
	  			),'text');
				});
				
				$("#btnTop10",newsItem).click(function(e){
	 				e.stopPropagation();
	 				$.get('news/top10.html',null,
	  				(function(o){
	     				var curVal = $("textarea[name='newsEdit']",newsItem).val();
	     				$("textarea[name='newsEdit']",newsItem).val(curVal + o);
	   				}
	  			),'text');
				});
	

            $("#btnSave",newsItem).click(function(e){
               e.stopPropagation();
               self.saveNews();
            });
            

            $("#btnCancel",newsItem).click(function(e){
               e.stopPropagation();
               self.cancelNews();
            });
				
			}//end if editable

	})//end news click 
	.parent().append('<br/>edit');
		
		
	function submitNews(xid,xnews)
	{
		//make an ajax request to update the news
		var newsUpdate = {
			id: xid,
			editNews: xnews
		};
		$.post('ajaxHandlers/updateNews.php',newsUpdate,done);
		
	}
	
	function done(o)
	{
		alert(o);
	}
	
});