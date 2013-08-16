jQuery(function($) {
	function do_datatables ()
	{
		$(".widefat").dataTable({
			bStateSave: true,
			bDeferRender: true,
			bProcessing: true,
			sPaginationType: "full_numbers",
			aoColumnDefs:
				[
	               {
	            	   bSortable: false,
	            	   aTargets: ['actions']
	               }
	           ]
		});
		$("a.disabled").on('click', function (e) {
			e.preventDefault();
		});
	}
	if (pagenow == 'dashboard_page_circle_tree_login_log') {
		do_datatables();
	}//End log page only
	var ajax_timeout;
	 
	$("#wpbody").on('submit click', '.ajax', function (e){
		var $this = $(this),
		params = $this.serialize(),
		$submit = $this.find('input[type=submit]');
		if ($submit.is(':disabled')) {
			return false;
		}
		//Prevent form input click from triggering submits
		if (e.type == 'click' && $this.is('form') && ! $(e.target).hasClass('button')) {
			return false;
		}
		$submit.attr('disabled', true);
		if ($this.is('a')) {
			$submit = $this;
			if ($this.hasClass('disabled')) {
				return false;
			}
			$this.addClass('disabled');
			//Pull params from href on links
			params = 'action=' + $this.data('action') + 
				'&page=circle_tree_login_log' + 
				'&nonce=' + $this.data('nonce') + 
				'&ip=' + $this.data('ip');
		}
		params = params.replace('action', 'ajax_action');
		var $loader = $this.find('.byct_loading'); 
		if ($loader.length == 0) {
			$this.append('<div class="byct_loading"></div>'); 
			$loader = $this.find('.byct_loading');
		} else {
			$loader.show();
		}
		clearTimeout(ajax_timeout);
		$.ajax({
			url: ajaxurl,
			data: 'action=by_ct_action&' + params,
			success: function  (data)
			{
				$loader.hide();
				$submit.removeAttr('disabled').removeClass('disabled');
				if (typeof(data) == 'object') {
					if (data.html.length > 0 ) {
						contents = $(data.html).html();
						$(".wrap").empty().html(contents);
						$("#listtable_fixed").remove(); 
						do_datatables();
					}
					data = data.code;
				}
				if (typeof(parseInt(data)) == 'number') {
					var response_code = parseInt(data);
					var $elem = $(".byct_messages.code_" + response_code);
					$elem.stop().fadeTo(200,1);
					var ajax_timeout = setTimeout(function  (){
						$elem.stop().fadeTo(200,0, function  (){
							$elem.removeAttr('style');
						});
					},2000);
					if (response_code == 10) {
						$elem.append('<em>Reloading...</em>');
						setTimeout(function  (){
							window.location.reload();
						},200)
					}
				} 
			}
		});
		return false;
	}); 
})