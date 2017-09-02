/**
 * @author chad56s
 */
$( function() {
	$('th.sortable').click(function(){
		var sortBy = escape($(this).text());
		var sortOrder = "up";
		
		var bFound = false;
		var bReverseOrder = false;
		
		var base = window.location.pathname;
		var query = window.location.search.substring(1);
		var aVars = query.split("&");
							
		var sortByIdx = -1;
		var sortOrderIdx = -1;
		
		for (var i=0; i<aVars.length; i++){
			var pair = aVars[i].split('=');
			var key = pair[0];
			var val = pair[1];
			
			if(key.toLowerCase() == 'sortorder'){
				sortOrderIdx = i;
				sortOrder = val;
			}
			if(key.toLowerCase() == 'sortby'){
				sortByIdx = i;
				bReverseOrder = sortBy == val;
				aVars[i] = key + '=' + sortBy;
				bFound = true;
			}
		}

		if(sortOrderIdx > -1)
			aVars.splice(sortOrderIdx,1);
			
		if(!bFound)
			aVars.push('sortBy=' + sortBy);
			
		if(bReverseOrder){
			sortOrder = (sortOrder == 'up') ? 'down' : 'up';
			aVars.push('sortOrder=' + sortOrder);
		}
		
			
		window.location.href= base + "?" + aVars.join('&');
	});

	//center function
	jQuery.fn.center = function () {
		 this.css("position","absolute");
		 this.css("top", ( $(window).height() - this.height() ) / 2+$(window).scrollTop() + "px");
		 this.css("left", ( $(window).width() - this.width() ) / 2+$(window).scrollLeft() + "px");
		 return this;
	}
});