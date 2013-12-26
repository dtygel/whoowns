jQuery( document ).ready(function() {
var mapType = "";
if (jQuery("#cy-full").length>0) 
	mapType='full';
	else if (jQuery("#cy-mini").length>0)
		mapType='mini';
if (mapType!='') {
	var params = {
		action: 'whoowns_load_network_data',
		post_id: ajax_object.post_id,
		imgUrl: ajax_object.img_url
	};
	jQuery.post(ajax_object.ajax_url, params, function(network_data) {
	  var l = network_data.nodes.length;
	  if (mapType=='mini') {
	  	var iconSize;
	  	var nameSize = 12;
	  	if (l<6) {
	    	iconSize = 48;
	    	} else if (l<12) {
	  	  	iconSize = 32;
	  	  	} else if (l<24) {
	  		  	iconSize = 24;
	  		  	nameSize = 10;
	  	  	} else {
	  		  	iconSize = 16;
	  		  	nameSize = 8;
	  	  	}
	  } else {
	  	var iconSize = 32;
	  	var nameSize=14;
	  	if (l<14)
	  		iconSize = 48;
	  		else if (l>30) {
	  			iconSize = 24;
	  			nameSize = 10;
	  		}
	  }
	  for (i=0;i<l;i++) {
	  	network_data.nodes[i].data.img = params.imgUrl+network_data.nodes[i].data.icon+'_'+iconSize+'.png';
	  	network_data.nodes[i].data.imgSel = params.imgUrl+network_data.nodes[i].data.icon+'Sel_'+iconSize+'.png';
	  }
	  //console.log(network_data.nodes[1].data.img);
	  
	
	  options = {
	    showOverlay: false,
	    layout: {
			name: 'arbor'
		},
		renderer: {
			hideEdgesOnViewport: (network_data.edges.length>ajax_object.threshold_moving_edges)
		},
	
	    style: cytoscape.stylesheet()
	      .selector('node')
	        .css({
	          'content': '',
	          'font-family': 'helvetica',
	          'font-size': nameSize,
	          'text-outline-width': 2,
	          'text-outline-color': '#fff',
	          'text-valign': 'bottom',
	          'color': '#777',
	          'background-color': 'data(color)',
	          'background-image': 'data(img)',
	          //'shape': 'rectangle',
	          'height': iconSize+20,
	          'width': iconSize+20,
	          //'width': 'mapData(weight, 30, 80, 20, 50)',
	          //'height': 'mapData(weight, 30, 80, 20, 50)',
	          'border-color': '#fff'
	        })
	      .selector('node:selected')
	        .css({
	          'background-image': 'data(imgSel)'
	        })
	      .selector('edge')
	        .css({
	          'width': 'mapData(relShare, 5, 50, 1, 3)',
	          'line-color': 'data(color)',
	          'target-arrow-color': 'data(color)',
	          'target-arrow-shape': 'triangle'
	        })
	      .selector('edge:selected')
	        .css({
	          'width': 'mapData(relShare, 5, 50, 3, 5)'
	        })
	    ,
	
	    elements: network_data,
	
	    ready: function(){
	      cy = this;
	      jQuery("#cy-loading").hide();
	      cy.panningEnabled(true);
	      cy.boxSelectionEnabled(false);
	      if (cy.nodes().length<ajax_object.threshold_nodes_with_names) {
	        cy.style()
	  		  .selector('node')
	      		.css({'content': 'data(name)'})
	     /*  .selector('edge')
	        	.css({'width': 'mapData(relShare, 5, 50, 1, 3)'})*/
	  		  .update()
	  	  }
	  	  //cy.nodes().bind("mouseover", function(){console.log(this._private.data.name)});
	  	  cy.nodes().bind({
			mouseover: function(e) {
			  var title = this._private.data.name;
	        	jQuery("#whoowns_tooltip")
	        	.html(title)
	        	.css({
	        		left:e.originalEvent.clientX-50,
	        		top:e.originalEvent.clientY-80
	        	})
	        	.show();
			},
			mouseout: function() {
				jQuery("#whoowns_tooltip")
	        	.hide();
			},
			click: function(e) {
				if (e.cyTarget.css('content')!='' && e.cyTarget.selected())
					e.cyTarget.css('content','');
					else
						e.cyTarget.css('content',e.cyTarget.data('name'));
			},
			select: function(e) {
				if (jQuery('#cy-info').length>0){
					if (typeof e.cyTarget.data('info')=='undefined') {
						jQuery('#cy-info-loading').show();
						var params = {
							action: 'whoowns_load_owner_data',
							post_id: e.cyTarget.data('id'),
						};
						jQuery.post(ajax_object.ajax_url, params, function(owner_data) {
							var html = ''+
								'<h2>Informações sobre <a href="'+owner_data.link+'">'+owner_data.name+'</a></h2>'+
								'<p class="description"><a href="'+owner_data.link+'">Clique para ver sua rede de poder</a></p>'+
								'<p><span class="cy-label">Tipo: </span><a href="'+owner_data.type.link+'">'+owner_data.type.name+'</a></p>';
							if (owner_data.PA)
								html += '<p><span class="cy-label">Poder acumulado: </span>'+owner_data.PA;
							if (owner_data.R)
								html += '<p><span class="cy-label">Empresa rankeada. Posição no ranking: </span>'+owner_data.R;
							if (owner_data.bndes_total_fundings)
								html += '<p><span class="cy-label">Financiamentos do BNDES que recebeu diretamente: </span>'+owner_data.bndes_total_fundings;
							if (owner_data.bndes_network_fundings)
								html += '<p><span class="cy-label">Financiamentos do BNDES recebidos em sua rede de poder: </span>'+owner_data.bndes_network_fundings;
							if (owner_data.ed_total_donations)
								html += '<p><span class="cy-label">Doações eleitorais que realizou diretamente: </span>'+owner_data.ed_total_donations;
							if (owner_data.ed_network_donations)
								html += '<p><span class="cy-label">Doações eleitorais realizadas por integrantes de sua rede de poder: </span>'+owner_data.ed_network_donations;
							//cy.$('#'+params.post_id).data('info',html);
							e.cyTarget.data('info',html);
							console.log(e.cyTarget);
							jQuery('#cy-info')
								.html(html)
								.show();
							jQuery('#cy-info-loading').hide();
						},"json");
					} else {
						console.log(e.cyTarget.data('info'));
						jQuery('#cy-info').html(e.cyTarget.data('info'))
						jQuery('#cy-info').show();
						jQuery('#cy-info-loading').hide();
					}
				}
			},
			unselect: function(e) {
				jQuery('#cy-info').hide();
			}
		});
		
		cy.edges().bind({
			mouseover: function(e) {
			  var edge = cy.$('#'+this._private.data.id);
			  var source = cy.$('#'+edge.data('source'));
			  var target = cy.$('#'+edge.data('target'));
			  var txt = '<center>'+source.data('name') + '<br /> possui ' + edge.data('weightTxt') + ' de <br />' + target.data('name')+'</center>';
	        	jQuery("#whoowns_tooltip")
	        	.html(txt)
	        	.css({
	        		left:e.originalEvent.clientX-80,
	        		top:e.originalEvent.clientY-80
	        	})
	        	.show();
			},
			mouseout: function() {
				jQuery("#whoowns_tooltip")
	        	.hide();
			}, 
			select: function(e) {
				console.log(e.cyTarget);
				var edge = e.cyTarget;
			    var source = cy.$('#'+edge.data('source'));
			    var target = cy.$('#'+edge.data('target'));
			    source.select();
			    target.select();
			    jQuery('#cy-info').hide();
			}
		});
	  }
	};
	
	var graph = jQuery('#cy-'+mapType);
	graph.cytoscape(options);
	graph.cytoscape(function(){
      graph.cytoscapePanzoom();
    });
	
	//Here come the legends, if a div is there!
	if (jQuery('#cy-legends').length>0) {
		jQuery('#cy-legends').html('');
		jQuery.post(ajax_object.ajax_url, {action:'whoowns_show_legends'}, function(legends_html) {
			jQuery('#cy-legends').html(legends_html);
		});
	}
	  
	},"json");
};

});
