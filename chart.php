<?php
include( "data.php" );
?>
<!doctype html>
<html>
<head>
<title>Refugees in Arizona 2002-2015</title>
<style>
body, html { width:100%; height:100%; padding:0; margin:0; overflow: hidden; }
#chart_div { width: 100%; height:100%; }
</style>
<script src="raphael-min.js"></script>
</head>
<body>
<div id="chart_div"></div>
<script>
// defined by container
var chart_w = 800;
var chart_h = 400;

// defined by data values
var min = <?php echo min($all_values); ?>;
var max = <?php echo max($all_values); ?>;

// canvas
var paper = Raphael("chart_div", chart_w, chart_h);

var offsets_for_year = <?php echo json_encode(array_flip($years)) ?>;


// data points
var data = <?php echo json_encode($data); ?>;
/*
function plot_year( country, year, count ) {



    if( country != "Afghanistan") return;

    var i = year - 2002;
    var w = (chart_w / 13);
    var x = w * i;
    var h = (chart_h / max) * count;
    var y = chart_h - h;

    var o = paper.rect( x, y, w, h);
    o.attr({ fill: "silver" });
    o.mouseover(function() {
        console.info( country + " " + year + " " + count );
    });
}

function plot_country( country, placement ) {
    for( var j = 0; j < placement.length; j++ ) {
        plot_year( country, placement[j].year, placement[j].count );
    }
}

for( var i = 0; i < data.length; i++ )(function(country, placement) {
    plot_country( country, placement );
})(data[i].country, data[i].placement);
*/

function year(init) {
    this.init = init;
    this.countries = [];
}



// init requires year, country, count
function block_year(init) {
    this.year = init.year;
    this.country = init.country;
    this.count = init.count;

    this.paper = init.paper;
    this.chart_w = init.chart_w;
    this.chart_h = init.chart_h;

    this.draw();
}

country_year.prototype.draw = function() {
    var w = this.chart_w / 13;
    var i = this.year - 2002;
    var x = i * w;
    var h =
}

var blocks = [];

for( var i = 0; i < data.length; i++ ) {
    for( var j = 0; j < data[i].placement.length; j++ ) {
        /*
        blocks.push( new country_year({
            chart_w: chart_w,
            chart_h: chart_h,
            paper: paper,
            year: data[i].placement[j].year,
            count: data[i].placement[j].count,
            country: data[i].country
        }) );
        */
    }
}

</script>
</body>
</html>
