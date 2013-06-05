jQuery(function() {
	var $ = jQuery;
	
	if($('#post-pagination').find('a').hasClass('disable')) {
	$('#post-pagination').find('a.disable').attr('href', '#').on('click', function(e) {
			e.preventDefault();
		});
	}
	
	// prepend to top of page
	$('#post-pagination').prependTo('#post-body');
	
	// filters changed by select 
	$('#pagination-filter').on('change', function() {
		$('.pagination_prev, .pagination_next').data('pagination_filters', $(this).val())
	});
	
	// get selects default value
    if($('#pagination-filter').data('defaultSelection')) { 
      $('#pagination-filter').val($('#pagination-filter').data('defaultSelection'));
      $('#pagination-filter').change();
    } else { 
      $('#pagination-filter').val('post_date');
    }
	
	
	// upon click get fitler data and redirect to link
	$('.pagination_prev').on('click', function(e) {
		if($(this).hasClass('disable') || $(this).data('pagination_filters') == null) return;
		e.preventDefault();
		var url_prev = $(this).attr('href');
		
		if(url_prev.search(/\&orderby\=([a-z\_\-]*)/i) > -1)
		url_prev = url_prev.replace(/\&orderby\=([a-z\_\-]*)/i,'&orderby=' + $(this).data('pagination_filters'));
		else
		url_prev = url_prev + '&orderby=' + $(this).data('pagination_filters');
		
	  window.location = url_prev;
	})
	
	
	$('.pagination_next').on('click', function(e) {
		if($(this).hasClass('disable') || $(this).data('pagination_filters') === null) return;
		e.preventDefault();
		var url_next = $(this).attr('href');
		
		if(url_next.search(/\&orderby\=([a-z\_\-]*)/i) > -1) {
		url_next = url_next.replace(/\&orderby\=([a-z\_\-]*)/i,'&orderby=' + $(this).data('pagination_filters'));
	  } else {
		  url_next = url_next + '&orderby=' + $(this).data('pagination_filters');
	  }
		
		window.location = url_next;
	
	})
	
});


/* filters sent from admin columns view */
function post_pagination_change_filter(filter) {
	filter[1] = '&orderby=' + filter[1];
	filter[0] = '&order=' + filter[0];
	filters = filter[0] + filter[1];	

	jQuery('.row-title').each(function() {
		jQuery(this).attr('href', jQuery(this).attr('href') + filters);
	});
	
	jQuery('.edit').each(function() {
		jQuery(this).find('a').attr('href', jQuery(this).find('a').attr('href') + filters);
	});
}