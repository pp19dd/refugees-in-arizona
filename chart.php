<?php include( "data.php" );?><!doctype html>
<html>
<head>
<title>Refugees in Arizona 2002-2015</title>
<style>
body, html { width:100%; height:100%; padding:0; margin:0; overflow: hidden; background-color: gray }
#chart_div { width:800px; margin:25px; background-color: white }
</style>
<script src="raphael-min.js"></script>
<script src="rainbowvis.js"></script>
</head>
<body>
<div id="chart_div"></div>
<script>
// defined by container
var chart_w = 800;
var chart_h = 400;

// canvas
var paper = Raphael("chart_div", chart_w, chart_h);

// data points
var data = <?php echo json_encode($data); ?>;

function block_country(year, country, count) {
    this.year = year;
    this.country = country;
    this.count = count;
}

block_country.prototype.draw = function(data, init_x) {

}


// init requires year, country, count
function block_year(year, countries) {

    this.countries = [];

}

// ===========================================================================
// main container
// ===========================================================================
function chart() {
    this.xy = {};

    // ranges
    this.range_x = {};
    this.range_y = {};

    this.count_x = 0;
    this.count_y = 0;

    // max
    this.max_x = 0;
    this.max_y = 0;
}

chart.prototype.setWidth = function(w) {
    this.width = w;
}

chart.prototype.setHeight = function(h) {
    this.height = h;
}

// needed to plot a comprehensive chart
chart.prototype.computeMax = function() {
    for( var year in this.range_x ) {
        if( this.range_x[year].sum > this.max_y ) {
            this.max_y = this.range_x[year].sum;
        }
    }
}

chart.prototype.add = function(country, year, count) {
    if( typeof this.range_x[year] == "undefined" ) {
        this.range_x[year] = { min: Infinity, max: 0, sum: 0, current: 0 }
        this.count_x++;
    };

    if( typeof this.range_y[country] == "undefined" ) {
        this.range_y[country] = { min: Infinity, max: 0, sum: 0, current:0 };
        this.count_y++;
    }

    var cursor = year + "-" + country;
    this.xy[cursor] = {
        year: year,
        country: country,
        count: count,
        e: null
    };

    if( count < this.range_x[year].min ) this.range_x[year].min = count;
    if( count > this.range_x[year].max ) this.range_x[year].max = count;
    this.range_x[year].sum += count;

    if( count < this.range_y[country].min ) this.range_y[country].min = count;
    if( count > this.range_y[country].max ) this.range_y[country].max = count;
    this.range_y[country].sum += count;
}

chart.prototype.filterYear = function(year) {
    var r = [];
    for( var cursor in this.xy ) {
        if( this.xy[cursor].year == year ) r.push( this.xy[cursor] );
    }
    return( r );
}

chart.prototype.filterCountry = function(country) {
    var r = [];
    for( var cursor in this.xy ) {
        if( this.xy[cursor].country == country ) r.push( this.xy[cursor] );
    }
    return( r );
}

// given a year, country, count, plot it somewhere
chart.prototype.plotBlock = function(point) {

    var i = point.year - 2002;
    var w = this.width / this.count_x;
    var h = (this.height / this.max_y) * point.count;
    var x = w * i;
    var y = 0;

    console.info( x, y, w, h );
    paper.rect( x, y, w, h);
    // var h = this.height / //point.count

}

chart.prototype.plotAll = function() {
    for( var cursor in this.xy ) {
        this.plotBlock(this.xy[cursor]);
    }
}

var arizona = new chart();
arizona.setWidth( 800 );
arizona.setHeight( 400 );

for( var i = 0; i < data.length; i++ ) {
    for( var j = 0; j < data[i].placement.length; j++ ) {

        // don't want empty objects b/c they're always drawn
        if( data[i].placement[j].count === 0 ) continue;

        arizona.add(
            data[i].country,
            data[i].placement[j].year,
            data[i].placement[j].count
        );
    }
}

arizona.computeMax();
arizona.plotAll();

// console.dir( arizona.range_x );
// console.dir( arizona.range_y );

/*
    this.init = init;

    this.data = init.data.data;

    this.paper = init.paper;
    this.chart_w = init.chart_w;
    this.chart_h = init.chart_h;

    this.block_w = this.chart_w / this.init.total_years;
    this.block_i = this.init.i;
    this.half_w = this.block_w / 2;

    // color ranges
    this.rainbow = new Rainbow();
    this.rainbow.setNumberRange(0, this.chart_h);
    this.rainbow.setSpectrum("#ff0000", "#000000", "#00ff00");
    // rainbow.colourAt(item.rows);

    this.countries = [];

    this.used_up = 0;
    this.draw();
}

block_year.prototype.draw_country = function(data, init_x) {
    if( data.count === 0 ) return;

    var x = init_x + 5;
    var w = this.block_w - 10;
    var h = (this.chart_h / year_max) * data.count;

    //console.info( x, w, h );
    // this.e = this.paper.rect( x, this.chart_h - this.used_up, w, h);

    var color = this.rainbow.colourAt(h);


    var e = this.paper.rect( x, this.used_up, w, h);
    e.__color = "#" + color;
    e.__country = data.country;
    e.attr({ fill: e.__color, stroke: 'white', "stroke-width":"0.5" });
    e.mouseover(function() {
        highlight_all( data.country, true );
    });

    e.mouseover(function() {
        highlight_all( data.country, false );
    });

    this.countries.push( e );

    //var
    // console.info( data );
    this.used_up += h;
}

block_year.prototype.highlight = function(country, enable) {
    //console.info( country, this.data[0].country );

    for( var i = 0; i < this.data.length; i++ ) {
        if( this.data[i].country == country ) {
            // this.e.hide();
        }
    }
}

block_year.prototype.draw = function() {

    // year label
    var x = this.block_i * this.block_w;
    this.paper.text( x + this.half_w, 10, this.data[0].year );

    for( var i = 0; i < this.data.length; i++ ) {
        this.draw_country( this.data[i], x );
    }
}


var blocks = [];

var count = 0;
for( var y in data.by_year )(function(data, i) {
    blocks.push( new block_year({
        i: i,
        total_years: 14,
        data: data,
        chart_w: chart_w,
        chart_h: chart_h,
        paper: paper
    }) );
})(data.by_year[y], blocks.length)

function highlight_all( country, enable) {
    for( var i = 0; i < blocks.length; i++ )(function(block, country, enable) {
        block.highlight( country, enable );
    })(blocks[i], country, enable)
}
*/


</script>
</body>
</html>
