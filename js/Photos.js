var esSrchData = {
            aggs: {
                makes: {
                    terms: {
			field: "Model"
			}
                }
            },
        }; 
///photos/_search?search_type=count&pretty" -d '{ "aggs" : { "makes" : { "terms" : { "field" : "Model"} }}}'

function loadData() {

	$.ajax({url: 'http://elasticsearch-thejml.rhcloud.com/photos/_search?search_type=count',
            type: 'POST',
            //contentType: 'application/json; charset=UTF-8',
            crossDomain: true,
            dataType: 'json',
            data: JSON.stringify(esSrchData),
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
