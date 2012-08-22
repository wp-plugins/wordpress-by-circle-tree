jQuery(function($) {
	var $news = $("#byct_news_content"),
	$refresh = $("#refreshCTNews"),
	loadingString = "<li><h3>Loading Circle Tree News</h3></li>";
 	if ($refresh.length > 0) {
		$refresh.bind("click", function  () {
			$news.append(loadingString);
			$.ajax({
			    url: document.location.protocol + "//ajax.googleapis.com/ajax/services/feed/load?v=1.0&num=4&callback=?&q=" + encodeURIComponent("http://mycircletree.com/feed/"),
			    dataType: "json",
			    success: function(data) {
			      	$news.empty();
			      	var str = "";
					$.each(data.responseData.feed.entries, function (k,entry) {
						str += "<li><h4><a target=\"_blank\" href=\""+entry.link+"\" title=\"View "+entry.title+" on our Website\">"+entry.title+"</a></h4><p>"+entry.contentSnippet+"<a style=\"float:right;\" target=\"_blank\" href=\""+entry.link+"\">Read more...</a></p></li>";
					});
					str +=	"<h3><a href=\"http://mycircletree.com/\" target=\"_blank\">Read more on the Circle Tree Blog</a></h3>";
					$(str).appendTo($news); 
			    }
			});
		  return false;
		}).trigger("click");
 	}
});