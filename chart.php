<?php $canvas_w = 800; $canvas_h = 500; include( "data.php" );?><!doctype html>
<html>
<head>
<title>Refugees in Arizona 2002-2015</title>
<style>
body, html { width:100%; height:100%; padding:0; margin:0; overflow: hidden; background-color: white }
.container { /* padding: 25px */ }
#chart_div { width:<?php echo $canvas_w ?>px; height: <?php echo $canvas_h ?>px; background-color: white }
</style>
<script src="raphael-min.js"></script>
<script src="scale.raphael.js"></script>
<script src="rainbowvis.js"></script>
</head>
<body>
    <div class="container">
        <div id="chart_div"></div>
    </div>
<script>
// defined by container
var chart_w = <?php echo $canvas_w ?>;
var chart_h = <?php echo $canvas_h ?>;

// canvas
// var paper = Raphael("chart_div", chart_w, chart_h);
var paper = ScaleRaphael("chart_div", chart_w, chart_h);
paper.changeSize(chart_w, chart_h, false, false );// [,center=true, clipping=false])

var windowAddEvent = window.attachEvent || window.addEventListener;

// thanks, scaleraphael!
function resizePaper(){
   var w = 0, h = 0;
   if(window.innerWidth) {
      w = window.innerWidth;
      h = window.innerHeight;
   }else if(document.documentElement &&
           (document.documentElement.clientWidth ||
            document.documentElement.clientHeight)) {
            w = document.documentElement.clientWidth;
            h = document.documentElement.clientHeight;
   }
   paper.changeSize(w, h, true, false);
}
resizePaper();
windowAddEvent("resize", resizePaper, false);

// data points
var data = <?php echo json_encode($data); ?>;

var data_usa = {
    top_states: [
        { state: "California", individuals: 93192 },
        { state: "Texas", individuals: 72442 },
        { state: "New York", individuals: 47605 },
        { state: "Florida", individuals: 43027 },
        { state: "Minnesota", individuals: 37594 },
        { state: "Washington", individuals: 36018 },
        { state: "Arizona", individuals: 33966 }
    ],
    total: {
        top_states: 363844,
        arizona: 33966
    }
};


// ===========================================================================
// borrowed from raphaeljs polar clock sample, modified to match this chart
// ===========================================================================
paper.customAttributes.arc = function(value, total, R, anchor_x, anchor_y) {
    var alpha = 360 / total * value;

    var a = (90 - alpha) * Math.PI / 180;
    var x = anchor_x + R * Math.cos(a);
    var y = anchor_y - R * Math.sin(a);
    var path;

        // color = "hsb(".concat(Math.round(R) / 200, ",", value / total, ", .75)"), path;
        // console.info( color );
    if (total == value) {
        path = [["M", anchor_x, anchor_y - R], ["A", R, R, 0, 1, 1, anchor_x-0.01, anchor_y - R]];
    } else {
        path = [["M", anchor_x, anchor_y - R], ["A", R, R, 0, +(alpha > 180), 1, x, y]];
    }

    return {path: path };
};

// ===========================================================================
// main container
// ===========================================================================
function chart() {
    this.xy = {};

    // misc raphaeljs objects
    this.e = {};

    // ranges
    this.range_x = {};
    this.range_y = {};

    this.count_x = 0;
    this.count_y = 0;

    // max
    this.max_x = 0;
    this.max_y = 0;

    // defaults: required
    this.width = 0;
    this.height = 0;

    this.padding = {
        top: 0,
        bottom: 0,
        left: 0,
        right: 0
    };

    // used to halt events during longer animations
    this.busy = false;

    this.styles = {
        block: {
            on: { "stroke-width": 1, "fill": "#677f70", "stroke": "gray", "cursor": "auto" },
            off: { "stroke-width": 1, "fill": "silver", "stroke": "gray", "cursor": "pointer" }
        },
        labels: {
            years: { "font-family": "Times New Roman", "font-size": "15", "font-weight": "bold", "fill": "black" },
            selected_country: { "font-family": "Times New Roman", "text-anchor": "end", "font-size": "25", "font-weight": "bold", "fill": "black" },
            selected_country_counts: { "font-family": "Times New Roman", "text-anchor": "end", "font-size": "15", "font-weight": "bold", "fill": "gray" }
        },
        chart_arc: { stroke: "#9bbfa9", "stroke-width": 20 },
        chart_arc_az: { stroke: "#677f70", "stroke-width": 20 },
        chart_arc_on: { stroke: "#00ce00", "stroke-width": 20 },
        chart_arc_label: { "font-family": "Times New Roman", "font-size": "14", "fill": "gray" },
        chart_arc_label_important: { "font-family": "Times New Roman", "font-size": "14", "fill": "black", "font-weight": "bold" },
        chart_arc_label_arc: { "font-family": "Times New Roman", "font-size": "14", "fill": "gray" }
    };
}

    /* "font-family": "Times New Roman" */

chart.prototype.setWidth = function(w) {
    this.width = w;
}

chart.prototype.setHeight = function(h) {
    this.height = h;
}

chart.prototype.setPadding = function(obj) {
    this.padding.top = obj.top;
    this.padding.bottom = obj.bottom;
    this.padding.left = obj.left;
    this.padding.right = obj.right;
}

chart.prototype.comma = function(num) {
    var num = parseInt(num);
    var readable = num.toString().replace(/\B(?=(\d{3})+\b)/g, ",");
    return(readable);
}


// needed to plot a comprehensive chart
chart.prototype.computeMax = function() {

    // used to draw the tallest year
    for( var year in this.range_x ) {
        if( this.range_x[year].sum > this.max_y ) {
            this.max_y = this.range_x[year].sum;
        }
    }

    // used for computing country gradients
    for( var country in this.range_y ) {
        if( this.range_y[country].sum > this.max_x ) {
            this.max_x = this.range_y[country].sum;
        }
    }
}

chart.prototype.computeGradient = function() {
    this.rainbow = new Rainbow();
    this.rainbow.setNumberRange(0, this.max_x);
    this.rainbow.setSpectrum("#ff0000", "#000000");
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

// TODO: consolidate mouseout/mouseover events
chart.prototype.highlightCountry = function() {

}

chart.prototype.countFromCountry = function(country) {
    var x = this.filterCountry(country);
    var count = 0;
    for( var i = 0; i < x.length; i++ ) {
        count += x[i].count;
    }
    return( count );
}

// given a year, country, count, plot it somewhere
chart.prototype.plotBlock = function(point) {

    var ph = this.padding.left + this.padding.right;
    var pv = this.padding.top + this.padding.bottom;

    var i = point.year - 2002;
    var w = ((this.width - ph) / this.count_x);
    var h = ((this.height - pv) / this.max_y) * point.count;
    var x = this.padding.left + (w * i);

    // y is inverted because graph starts from bottom
    var bottom = this.height - this.padding.bottom;
    var y = bottom - h - this.range_x[point.year].current;
    // var y = this.padding.top + (this.height - this.padding2) - h - this.range_x[point.year].current;

    this.range_x[point.year].current += h;


    var column_margin = 8;
    var e = paper.rect( x + column_margin, y, w - (column_margin * 2), h);
    e.__should_be_height = h;
    // var e = paper.rect( x, y, w, h);
    // e.attr( { "stroke-width": 0.5, "fill": "white"});
    e.attr( this.styles.block.off );

    var that = this;
    e.mouseover(function() {
        if( that.busy === true ) return;

        that.e.selected_country.attr("text", point.country);
        that.e.selected_country.show();

        that.e.selected_country_counts.attr("text", that.comma(point.count) + " refugees admitted to Arizona in " + point.year);
        that.e.selected_country_counts.show();

        that.e.selected_country_counts2.attr("text", that.comma(that.countFromCountry(point.country)) + " refugees admitted 2002-2015");
        that.e.selected_country_counts2.show();

        var x = that.filterCountry(point.country);
        for( var i = 0; i < x.length; i++ ) {
            // x[i].e.attr({fill: "crimson", stroke: "crimson"});
            // x[i].e.toFront().stop().animate({fill: "orange", stroke: "orange"}, 300, "<>");
            x[i].e.toFront().stop().animate(that.styles.block.on, 300, "<>");
        }
    });

    e.mouseout(function() {
        if( that.busy === true ) return;

        that.e.selected_country.hide();
        that.e.selected_country_counts.hide();
        that.e.selected_country_counts2.hide();

        var x = that.filterCountry(point.country);
        for( var i = 0; i < x.length; i++ ) {
            // x[i].e.attr({fill: "white", stroke: "black"});
            // x[i].e.stop().animate({fill: "white", stroke: "black"}, 300, "<>");
            x[i].e.stop().animate(that.styles.block.off, 100, "<>");
        }
    });

    e.click(function() {
        if( that.busy === true ) return;

        // experimental event
        that.sortYearsByCountry(point.country, function() {
            that.e.selected_country.hide();
            that.e.selected_country_counts.hide();
            that.e.selected_country_counts2.hide();

            var x = that.filterCountry(point.country);
            for( var i = 0; i < x.length; i++ ) {
                // x[i].e.attr({fill: "white", stroke: "black"});
                // x[i].e.stop().animate({fill: "white", stroke: "black"}, 300, "<>");
                x[i].e.stop().animate(that.styles.block.off, 100, "<>");
            }
        });
    })
    //e.attr({ opacity: 0});

    //var x = Raphael.animation( { opacity: 1 }, 300);
    //e.animate({ opacity: 1}, 500, "<>").delay(100);
    //e.animate(x.delay(500 + (100 * i)));

    point.e = e;
    //e.attr({ fill: "#" + this.rainbow.colourAt(point.count) } );


}

chart.prototype.plotAll = function() {
    for( var cursor in this.xy ) {
        this.plotBlock(this.xy[cursor]);
    }
}

chart.prototype.plotLabels = function() {

    var that = this;

    // padding horizontal, vertical
    var ph = this.padding.left + this.padding.right;
    var pv = this.padding.top + this.padding.bottom;

    var w = ((this.width - ph) / this.count_x);

    // y is inverted because graph starts from bottom
    var bottom = this.height - this.padding.bottom;
    var rightmost = this.width - this.padding.right;
    //var y = bottom - h - this.range_x[point.year].current;

    for( var year in this.range_x ) {
        var i = year - 2002;


        var x = this.padding.left + (w * i);
        var y = bottom + 20;

        paper.text( x + (w/2), y, year ).attr(this.styles.labels.years);

    }

    this.e.selected_country = paper.text(rightmost, this.padding.top, "");
    this.e.selected_country.attr( this.styles.labels.selected_country );

    this.e.selected_country_counts = paper.text(rightmost, this.padding.top + 30, "");
    this.e.selected_country_counts.attr( this.styles.labels.selected_country_counts );

    this.e.selected_country_counts2 = paper.text(rightmost, this.padding.top + 60, "");
    this.e.selected_country_counts2.attr( this.styles.labels.selected_country_counts );

    // arizona compared to rest of the country

    var radius = 80;

    this.e.arcs = [];
    var delta = 0;
    var anchor_x = 125;
    var anchor_y = 100;

    this.e.arc_center_label = paper.text(anchor_x, anchor_y, "");
    this.e.arc_center_label.attr( this.styles.chart_arc_label_arc );

    for( var i = 0; i < data_usa.top_states.length; i++)(function(point, i) {

        var temp = paper.path().attr(that.styles.chart_arc);

        var a = point.individuals;
        var b = data_usa.total.top_states;
        var c = (a / b) * 100;
        var d = (360 * c) / 100;

        var cushion = 3000;

        // d is a running angle
        temp.attr({ arc: [a - cushion, b, radius, anchor_x, anchor_y]});
        temp.attr({transform: "R" + parseInt(delta) + "," + anchor_x + "," + anchor_y });

        // delta += (d + 3);
        delta += (d );

        var b = temp.getBBox();
        var temp2 = paper.text(b.cx, b.cy, point.state );

        temp2.attr(that.styles.chart_arc_label);
        if( i == 6 ) {
            temp2.attr(that.styles.chart_arc_label_important);
            temp.attr(that.styles.chart_arc_az);
        }

        var data = point;
        /*
        temp.mouseover(function() {
            this.attr(that.styles.chart_arc_on);
        });
        temp.mouseout(function() {
            this.attr(that.styles.chart_arc);
        });*/

        var temp3 = paper.set();
        temp3.push( temp );
        temp3.push( temp2 );

        temp3.mouseover(function() {
            temp.attr(that.styles.chart_arc_on);
            that.e.arc_center_label.attr("text", that.comma(point.individuals)  + "\nRefugees" );
        }).mouseout(function() {
            if( i == 6 ) {
                temp.attr(that.styles.chart_arc_az);
            } else {
                temp.attr(that.styles.chart_arc);
            }
            that.e.arc_center_label.attr("text", "" );
        });

        that.e.arcs.push(temp);

    })(data_usa.top_states[i], i);

    /*
    this.e.circle_state = paper.path().attr( this.styles.chart_arc );
    this.e.circle_state.attr({arc: [10, 100, radius, 100, 100]});

    var radius = 50;
    this.e.circle_state = paper.path().attr( this.styles.chart_arc );
    this.e.circle_state.attr({arc: [90, 100, radius, 100, 200]});
*/
    //var sec = paper.path().attr(param).attr({arc: [30, 60, R]}).attr({transform:"r90"});

}

// sorts smallest to largest
chart.prototype.sortYear = function(objects) {
    var sorted = objects.sort(function(a,b) {
        if( a.count > b.count ) return( 1 );
        return( 0 );
        //return( parseFloat(b.count) - parseFloat(b.count) );
    });
    return( sorted );
}

// sorts smallest to largest, but start with selected country up top
chart.prototype.sortCountry = function(objects, country) {
    var sorted = objects.sort(function(a,b) {
        if( country == a.country ) return( 1 );
        if( country == b.country ) return( -1 );
        if( a.count > b.count ) return( 1 );
        return( 0 );
        //return( parseFloat(b.count) - parseFloat(b.count) );
    });
    return( sorted );
}

chart.prototype.sortYearsBySize = function() {
    for( year in this.range_x ) {
        // sort year
        var x = this.filterYear(year);
        var sorted = this.sortYear(x);

        // reposition rects based on sort order
        var offset = 0;
        for( var b = 0; b < sorted.length; b++ ) {

            //var new_y = sorted[b].e.attr("height");
            // var temp_h = sorted[b].e.attr("height");
            var temp_h = sorted[b].e.__should_be_height;

            sorted[b].e.attr("y", this.height - this.padding.bottom - offset - temp_h);
            // sorted[b].e.animate({ y: this.height - this.padding.bottom - offset - temp_h}, 1100, "<>");
            offset += temp_h;
        }
    }
}

chart.prototype.sortYearsByCountry = function(country, after_animation) {
    var that = this;

    for( year in this.range_x ) {
        // sort year
        var x = this.filterYear(year);
        var sorted = this.sortCountry(x, country);

        // reposition rects based on sort order
        var offset = 0;
        for( var b = 0; b < sorted.length; b++ ) {

            //var new_y = sorted[b].e.attr("height");
            // var temp_h = sorted[b].e.attr("height");
            var temp_h = sorted[b].e.__should_be_height;

            that.busy = true;
            // sorted[b].e.attr("y", this.height - this.padding.bottom - offset - temp_h);
            sorted[b].e.animate({ y: this.height - this.padding.bottom - offset - temp_h}, 300, "<>", function() {
                that.busy = false;
                after_animation();
            });
            offset += temp_h;
        }
    }
}

var arizona = new chart();
arizona.setWidth(chart_w);
arizona.setHeight(chart_h);
arizona.setPadding({
    top: 10, bottom: 40, left: 10, right: 10
});

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
arizona.computeGradient();
arizona.plotAll();
arizona.plotLabels();
arizona.sortYearsBySize();


</script>
</body>
</html>
