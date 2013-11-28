jQuery( document ).ready(function() {
var mapType = "";
console.log(jQuery("#cy-mini"));
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
		console.log(mapType);
		/*if (network_data)
			console.log(network_data.edges[0].data.id + network_data.edges[0].data.source + network_data.edges[0].data.target);*/
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
	  for (i=0;i<l;i++)
	  	network_data.nodes[i].data.img = params.imgUrl+network_data.nodes[i].data.icon+'_'+iconSize+'.png';
	  //console.log(network_data.nodes[1].data.img);
	  
	
	  options = {
	    showOverlay: false,
	    minZoom: 0.5,
	    maxZoom: 2,
	    layout: {
			name: 'arbor'
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
	          'height': iconSize+1,
	          'width': iconSize+1,
	          //'width': 'mapData(weight, 30, 80, 20, 50)',
	          //'height': 'mapData(height, 0, 200, 10, 45)',
	          'border-color': '#fff'
	        })
	      .selector(':selected')
	        .css({
	          'background-color': '#000',
	          'line-color': '#000',
	          'target-arrow-color': '#000',
	          'text-outline-color': '#000'
	        })
	      .selector('edge')
	        .css({
	          'width': 2,
	          'line-color': 'data(color)',
	          'target-arrow-color': 'data(color)',
	          'target-arrow-shape': 'triangle'
	        })
	    ,
	
	    elements: network_data,
	
	    ready: function(){
	      cy = this;
	      jQuery("#cy-loading").hide();
	      cy.panningEnabled(true);
	      /*cy.$('#'+params.post_id).position('y', 200);
	      cy.$('#'+params.post_id).position('x', 200);*/
	      /*jQuery("#cy").show();
	      cy.fit();*/
	      if (cy.nodes().length<10 || mapType=='full') {
	        cy.style()
	  		  .selector('node')
	      		.css({'content': 'data(name)'})
	      	  .selector('edge')
	        	.css({'width': 'mapData(relShare, 5, 50, 2, 5)'})
	  		  .update()
	  	  }
	  	  //cy.nodes().bind("mouseover", function(){alert(this._private.data.name)});
	  	  cy.nodes().bind({
			mouseover: function() {
			  var title = this._private.data.name;
			  	var p  = jQuery("#cy-"+mapType).position();
	        	jQuery("#whoowns_tooltip")
	        	.html(title)
	        	.css({
	        		left:p.left+this._private.position.x-50, 
	        		top:p.top+this._private.position.y-50
	        	})
	        	.show();
			},
			mouseout: function() {
				jQuery("#whoowns_tooltip")
	        	.hide();
			}/*,
			dblclick: function() {
				window.location.href = "http://localhost/grit/proprietariosdobrasil.org.br/wordpress/?p=" + this._private.data.id;
			}*/
		});
	  }
	};

	jQuery('#cy-'+mapType).cytoscape(options);
	  
	},"json");
};

});
