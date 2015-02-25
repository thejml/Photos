var esDeviceDataQuery = { aggs: { makes: { terms: { field: "Model" } } }, }; 
var esActivityDataQuery = { aggs: { time: { date_histogram: { field: "DateTime", "interval": "week", "min_doc_count":0} }}}; 
var geoLocationQuery = { query: { filtered: { query: {match_all : {}}, filter: { "geo_distance": { "distance": "200km", "location" : [37.31,-76.73] } } } } };
var elasticsearchServerURL = "http://elasticsearch-thejml.rhcloud.com/photos/";

function gMapInitialize() {
	var mapOptions = { center: { lat: 37, lng: -76.3}, zoom: 7 };
	var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
	
	$.ajax({url: elasticsearchServerURL+'_search',
            	type: 'POST',
            	//contentType: 'application/json; charset=UTF-8',
            	crossDomain: true,
            	dataType: 'json',
            	data: JSON.stringify(geoLocationQuery),
            	success: function(response) {
                var returnData = response.hits.hits;
            	if (returnData.length > 0) {
                    for (var i = 0; i < returnData.length; i++) {
	    		var agg=returnData[i];
			var myLatlng = new google.maps.LatLng(agg._source.location[0],agg._source.location[1]);
			var marker = new google.maps.Marker({position: myLatlng, map: map, title:""});
                    }
            	} 
	}});
}

function googleMapLocation() {
      google.maps.event.addDomListener(window, 'load', gMapInitialize);
}


function loadDeviceChart() {

	$.ajax({url: elasticsearchServerURL+'_search?search_type=count',
            type: 'POST',
            //contentType: 'application/json; charset=UTF-8',
            crossDomain: true,
            dataType: 'json',
            data: JSON.stringify(esDeviceDataQuery),
            success: function(response) {
                var returnData = response.hits.hits;
                var output = [];
                var temp = [];
		var colors = ["#F7464A","#46BFBD","#FDB45C"];
		var highlight = ["#FF5A5E","#5AD3D1","#FFC870"];
                var source = null;
                var content = '';
		var agg=response.aggregations.makes.buckets;
                if (agg.length > 0) {
                    for (var i = 0; i < agg.length; i++) {
                        label = agg[i].key;
			label.replace("_"," ");
			value = agg[i].doc_count;
                        output[i]={'label':label,'value':value,'highlight':highlight[i],'color':colors[i]};
                    }
        	    // Get context with jQuery - using jQuery's .get() method.
        	    var ctx = $("#deviceChart").get(0).getContext("2d");
        	    // This will get the first returned node in the jQuery collection.
        	    var deviceChartObject = new Chart(ctx).Doughnut(output);
                } 
            },
            error: function(jqXHR, textStatus, errorThrown) {
                var jso = jQuery.parseJSON(jqXHR.responseText);
                error_note('section', 'error', '(' + jqXHR.status + ') ' + errorThrown + ' --<br />' + jso.error);
            }
        });
};


function loadActivityChart() {

	$.ajax({url: elasticsearchServerURL+'_search?search_type=count',
            type: 'POST',
            //contentType: 'application/json; charset=UTF-8',
            crossDomain: true,
            dataType: 'json',
            data: JSON.stringify(esActivityDataQuery),
            success: function(response) {
                var returnData = response.hits.hits;
                var output = [];
                var temp = [];
		var labelText = [];
		var agg=response.aggregations.time.buckets;
                if (agg.length > 0) {
                    for (var i = 0; i < agg.length; i++) {
                        label = agg[i].key_as_string;
			value = agg[i].doc_count;
			temp[i]=value;
			labelText[i]='';
                    }
		output={ labels:labelText,
			 datasets: [ {
            			fillColor: "rgba(151,187,205,0.5)",
            			strokeColor: "rgba(151,187,205,0.8)",
            			highlightFill: "rgba(151,187,205,0.75)",
            			highlightStroke: "rgba(151,187,205,1)",
                    		data:temp }] };
        	    // Get context with jQuery - using jQuery's .get() method.
        	    var ctx = $("#activityChart").get(0).getContext("2d");
        	    // This will get the first returned node in the jQuery collection.
        	    var deviceChartObject = new Chart(ctx).Line(output,{ bezierCurve: true });
                } 
            },
            error: function(jqXHR, textStatus, errorThrown) {
                var jso = jQuery.parseJSON(jqXHR.responseText);
                error_note('section', 'error', '(' + jqXHR.status + ') ' + errorThrown + ' --<br />' + jso.error);
            }
        });
};
