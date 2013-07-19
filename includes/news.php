<script>
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
						str += "<li><h4><a target=\"_blank\" href=\""+entry.link+"\" title=\"View "+entry.title+" on our Website\">"
						  + entry.title+"</a></h4><p>" + 
						  entry.contentSnippet + "<a class=\"button button-mini\" style=\"float:right;\" target=\"_blank\" href=\""+entry.link+"\">Read more...</a></p></li>";
					});
					str +=	"<li><a class=\"alignright\" href=\"https://mycircletree.com/circle-tree-blog/\" target=\"_blank\">"
						   + "Continue reading on the Circle Tree<sup>&reg;</sup> Blog</a></li>";
					$(str).appendTo($news); 
			    }
			});
		  return false;
		}).trigger("click");
 	}
});
</script>