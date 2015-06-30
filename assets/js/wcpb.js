(function($) {
	var wcpb = function() {
		/* used to holds next request's data (most likely to be transported to server) */
		this.request = null;
		/* used to holds last operation's response from server */
		this.response = null;
		/* to prevetn Ajax conflict. */
		this.ajaxFlaQ = true;
		/*Holds currently selected fields */
		this.activeField = null;
		
		this.initialize = function() {
			this.registerEvents();
		};
		
		this.registerEvents = function() {
			$(document).on( "keyup", "#wcpb-product-search-txt", this, function(e) {
				e.data.prepareRequest( "GET", "search", $(this).val() );
				e.data.dock();
			});			
			$(document).on( "click", "ul.wcpb-auto-complete-ul li a", this, function(e) {
				$(this).toggleClass("selected");
				e.preventDefault();
				e.stopPropagation();
			});
			$(document).on( "click", "#wcpb-add-product", this, function(e) {
				var sel_product = [];
				$("ul.wcpb-auto-complete-ul li").each(function(){
					if( $(this).find('a').hasClass('selected') ) {
						sel_product.push( $(this).find('a').attr('product_id') );
					}
				});
				if( sel_product.length > 0 ) {
					e.data.prepareRequest( "GET", "add-to-bundle", sel_product );
					e.data.dock();
				} else {
					$("#wcpb-product-search-txt").attr( 'placeholder', 'Please search for a product.!' );
					$("#wcpb-product-search-txt").focus();
				}
				e.preventDefault();
			});
			$(document).on( "click", "a.wcpb-remove-bundle-btn", this, function(e) {
				e.data.prepareRequest( "DELETE", "remove-from-bundle", $(this).attr('product_id') );
				e.data.dock();
				e.preventDefault();
				e.stopPropagation();
			});
			$(document).on( "click", ".wcpb_close_all", function(e) {
				$(".wcpb-wc-metabox-content").hide();
				e.preventDefault();
			});
			$(document).on( "click", ".wcpb_expand_all", function(e) {
				$(".wcpb-wc-metabox-content").show();
				e.preventDefault();
			});
			$(document).on( "click", "h3.wcpb-product-bundle-row-header", function() {
				$(this).next().toggle();
			});
			$(document).on( "submit", "form#post", this, function(e) {			
				return e.data.onPostSubmit( $(this));
			});
		};	
		
		this.onPostSubmit = function() {
			var key = "";
			var bundles = [];
			$("#wcpb-products-container > div").each(function(){	
				key = $(this).attr('product_id');
				bundles.push( 
					{ 
						"product_id" : key,
						"bundle" : {						
							quantity : $("input[name=wcpb_bundle_product_"+ $(this).attr('product_id') +"_quantity]").val(),
							price : $("input[name=wcpb_bundle_product_"+ $(this).attr('product_id') +"_price]").val(),	
							tax_included : ( $("input[name=wcpb_bundle_product_"+ $(this).attr('product_id') +"_tax_included]").is(':checked') ) ? "yes" : "no",
							thumbnail : ( $("input[name=wcpb_bundle_product_"+ $(this).attr('product_id') +"_thumbnail]").is(':checked') ) ? "yes" : "no",
							title : $("input[name=wcpb_bundle_product_"+ $(this).attr('product_id') +"_title]").val(),
							desc : $("textarea[name=wcpb_bundle_product_"+ $(this).attr('product_id') +"_desc]").val()
						} 
					} 
				);
			});		
			
			$("#wcpb-bundles-array").val( JSON.stringify( bundles ) );			
		};
		
		this.prepareRequest = function( _request, _context, _payload ) {
			this.request = {
				request : _request,
				context : _context,
				post 	: wcpb_var.post_id,
				payload : _payload
			};
		};
		
		this.prepareResponse = function( _status, _msg, _data ) {
			this.response = {
				status : _status,
				message : _msg,
				payload : _data
			};
		};
		
		this.dock = function( _action, _target ) {		
			var me = this;
			/* see the ajax handler is free */
			if( !this.ajaxFlaQ ) {
				return;
			}		
			
			$.ajax({  
				type       : "POST",  
				data       : { action : "wcpb_ajax", WCPB_AJAX_PARAM : JSON.stringify(this.request)},  
				dataType   : "json",  
				url        : wcpb_var.ajaxurl,  
				beforeSend : function(){  				
					/* enable the ajax lock - actually it disable the dock */					
					me.ajaxFlaQ = false;				
					$("#wcpb-ajax-spinner").show();
				},  
				success    : function(data) {				
					/* disable the ajax lock */
					me.ajaxFlaQ = true;			
					$("#wcpb-ajax-spinner").hide();
					me.prepareResponse( data.status, data.message, data.data );		               

					/* handle the response and route to appropriate target */
					if( me.response.status ) {
						me.responseHandler();
					} else {
						/* alert the user that some thing went wrong */						
					}				
				},  
				error      : function(jqXHR, textStatus, errorThrown) {                    
					/* disable the ajax lock */
					me.ajaxFlaQ = true;
					$("#wcpb-ajax-spinner").hide();
				}  
			});		
		};
		
		this.responseHandler = function(){	
			if( this.request.context == "search" ) {
				$("#wcpb-product-search-result-holder").html( this.response.payload );
				$("#wcpb-product-search-result-holder").show();
			} else if( this.request.context == "add-to-bundle" ) {
				if( this.response.status ) {
					if( $("#wcpb-products-container > div").hasClass('wcpb-empty-msg') ) {
						$("#wcpb-products-container").html( this.response.payload );
					} else {
						$("#wcpb-products-container").append( this.response.payload );
					}					
				}
			} else if( this.request.context == "remove-from-bundle" ) {
				if( this.response.status ) {
					$("#wcpb-products-container > div[product_id="+ this.request.payload +"]").remove();					
					if( !$("#wcpb-products-container > div").hasClass('wcpb-product-bundle-row') ) {
						$("#wcpb-products-container").html('<div class="wcpb-empty-msg"><p>Search for products, select as many as product you want and add those to bundle using "Add Products". Only "Simple" or "variable" product are allowed to add. You can also drag drop to re arrange the order of bundled products in product page.!</p></div>');
					}
				}				
			}
		};
	};
	
	$(document).ready(function(){
		var wcpbObj = new wcpb();
		wcpbObj.initialize();		
		$('#wcpb-products-container').sortable();
	});
	
	$(document).click(function(){
		$("#wcpb-product-search-result-holder").hide();
	});	
})( jQuery );