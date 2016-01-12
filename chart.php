<?php $canvas_w = 800; $canvas_h = 500; include( "data.php" );?><!doctype html>
<html>
<head>
<title>Refugees in Arizona 2002-2015</title>
<style>
body, html { width:100%; height:100%; padding:0; margin:0; overflow: hidden; background-color: silver }
.container { /* padding: 25px */ }
#chart_div { background-color: white }

/*
@media screen and (max-width:700px) {
    svg tspan { font-size:20px }
}
*/
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
var paper = Raphael("chart_div", chart_w, chart_h);
// var paper = ScaleRaphael("chart_div", chart_w, chart_h);
// paper.changeSize(chart_w, chart_h, false, false );// [,center=true, clipping=false])


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
    this.e = {
        arcs: [],
        labels: {},
        blocks: []
    };

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

    this.new_width = 0;
    this.new_height = 0;

    this.padding = {
        top: 0,
        bottom: 0,
        left: 0,
        right: 0
    };

    // used to halt events during longer animations
    this.busy = false;
    this.a_country_is_highlighted = {
        state: false,
        handler_on: null,
        handler_off: null
    };

    this.styles = {
        block: {
            on: { "stroke-width": 1, "fill": "#677f70", "stroke": "gray", "cursor": "auto" },
            off: { "stroke-width": 1, "fill": "silver", "stroke": "gray", "cursor": "pointer" }
        },
        labels: {
            years: { "font-family": "Arial", "font-size": "15", "font-weight": "bold", "fill": "black" },
            selected_country: { "font-family": "Arial", "text-anchor": "end", "font-size": "25", "font-weight": "bold", "fill": "black" },
            selected_country_counts: { "font-family": "Arial", "text-anchor": "end", "font-size": "15", "font-weight": "bold", "fill": "gray" }
        },
        chart_arc: { stroke: "#9bbfa9", "stroke-width": 20 },
        chart_arc_az: { stroke: "#677f70", "stroke-width": 20 },
        chart_arc_on: { stroke: "#00ce00", "stroke-width": 20 },
        chart_arc_label: { "font-family": "Arial", "font-size": "14", "fill": "gray" },
        chart_arc_label_important: { "font-family": "Arial", "font-size": "14", "fill": "black", "font-weight": "bold" },
        chart_arc_label_arc: { "font-family": "Arial", "font-size": "14", "fill": "gray" }
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
    // this.rainbow = new Rainbow();
    // this.rainbow.setNumberRange(0, this.max_x);
    // this.rainbow.setSpectrum("#ff0000", "#000000");
    var that = this;

    var counter = 0;

    var available_colors = [
        "#FF1873", "#0DFFF2", "#FFCD19", "#A757AB", "#7CFF82", "#FF5D51", "#38C6E2",
        "#EBFF13", "#37FFC7"
    ];

    for( var country in this.range_y )(function(country, data, counter) {
        var rainbow = new Rainbow();
        rainbow.setNumberRange(0, data.max);

        // todo: add 62 more color ranges
        if( typeof available_colors[63-counter] == "undefined" ) {
            rainbow.setSpectrum("#000000", "#ff0000");
        } else {
            rainbow.setSpectrum("#000000", available_colors[63-counter]);
        }

        var country_blocks = that.filterCountry(country);
        for( var i = 0; i < country_blocks.length; i++) {
            country_blocks[i].e.attr({ "fill": "#" + rainbow.colorAt(country_blocks[i].count) });
        }

    })(country, this.range_y[country], counter++);
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

chart.prototype.highlightCountry = function(that, point, is_on) {

    if( is_on === true ) {
        that.displayCountryText(point);

        that.e.labels.selected_country.show();
        that.e.labels.selected_country_counts.show();
        that.e.labels.selected_country_counts2.show();
    } else {
        that.e.labels.selected_country.hide();
        that.e.labels.selected_country_counts.hide();
        that.e.labels.selected_country_counts2.hide();
    }

    var x = that.filterCountry(point.country);
    for( var i = 0; i < x.length; i++ ) {
        if( is_on === true ) {
            x[i].e.stop().animate(that.styles.block.on, 300, "<>");
        } else {
            x[i].e.stop().animate(that.styles.block.off, 100, "<>");
        }
    }

}

chart.prototype.countFromCountry = function(country) {
    var x = this.filterCountry(country);
    var count = 0;
    for( var i = 0; i < x.length; i++ ) {
        count += x[i].count;
    }
    return( count );
}

chart.prototype.displayCountryText = function(point) {
    this.e.labels.selected_country.attr("text", point.country);
    this.e.labels.selected_country.toFront().show();

    var delim = " ";
    if( this.new_width < 710 ) {
        delim = "\n";
    }

    this.e.labels.selected_country_counts.attr(
        "text",
        this.comma(point.count) + " refugees admitted" +
        delim + "to Arizona in " + point.year
    );
    this.e.labels.selected_country_counts.toFront().show();

    this.e.labels.selected_country_counts2.attr(
        "text",
        this.comma(this.countFromCountry(point.country)) +
        " refugees admitted" + delim + "2002-2015"
    );
    this.e.labels.selected_country_counts2.toFront().show();

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

    this.range_x[point.year].current += h;


    var column_margin = 8;
    var e = paper.rect( x + column_margin, y, w - (column_margin * 2), h);
    e.__should_be_height = h;
    e.attr( this.styles.block.off );

    var that = this;

    e.mouseover(function() {
        if( that.busy === true ) return;

        // if something is currently "highlighted", reapply the effect
        if( that.a_country_is_highlighted.state === true ) {
            that.a_country_is_highlighted.handler_off();
        }

        // moved to highlight routine
        // that.displayCountryText(point);
        that.highlightCountry(that, point, true);
    });

    e.mouseout(function() {
        if( that.busy === true ) return;

        that.highlightCountry(that, point, false);

        if( that.a_country_is_highlighted.state === true ) {
            that.a_country_is_highlighted.handler_on();
        }

    });

    e.click(function() {
        if( that.busy === true ) return;

        // was a previous country highlighted?  if so, unhighlight it
        if( that.a_country_is_highlighted.state === true ) {
            that.a_country_is_highlighted.handler_off();
        }

        // now "highlight" this one
        that.a_country_is_highlighted = {
            state: true,
            handler_on: function() {
                that.highlightCountry(that, point, true);
            },
            handler_off: function() {
                that.highlightCountry(that, point, false);
            }
        }

        // experimental event
        that.sortYearsByCountry(point.country, function(sorted_item) {
            that.displayCountryText(point);
        });
    });

    point.e = e;

    this.storeOriginalGeometryBlock(e);
    this.e.blocks.push(e);
}

chart.prototype.plotAll = function() {
    for( var cursor in this.xy ) {
        this.plotBlock(this.xy[cursor]);
    }
}

chart.prototype.storeOriginalGeometryBlock = function(block) {
    block.__geometry = {
        ox: block.attr("x"),
        oy: block.attr("y"),
        ow: block.attr("width"),
        oh: block.attr("height")
    }
}

chart.prototype.storeOriginalGeometryLabel = function(label, type) {
    label.__geometry = {
        ox: label.attr("x"),
        oy: label.attr("y")
    }
    label.__type = type;
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

    for( var year in this.range_x ) {
        var i = year - 2002;


        var x = this.padding.left + (w * i);
        var y = bottom + 20;

        this.e.labels["year_" + year] = paper.text( x + (w/2), y, year ).attr(this.styles.labels.years);
        this.storeOriginalGeometryLabel(this.e.labels["year_" + year], "year");
        this.e.labels["year_" + year].__year = year;
    }

    this.e.labels.selected_country = paper.text(rightmost, this.padding.top, "");
    this.e.labels.selected_country.attr( this.styles.labels.selected_country );
    this.storeOriginalGeometryLabel(this.e.labels.selected_country, "country");

    this.e.labels.selected_country_counts = paper.text(rightmost, this.padding.top + 30, "");
    this.e.labels.selected_country_counts.attr( this.styles.labels.selected_country_counts );
    this.storeOriginalGeometryLabel(this.e.labels.selected_country_counts, "country");

    this.e.labels.selected_country_counts2 = paper.text(rightmost, this.padding.top + 60, "");
    this.e.labels.selected_country_counts2.attr( this.styles.labels.selected_country_counts );
    this.storeOriginalGeometryLabel(this.e.labels.selected_country_counts2, "country");

}

// arizona compared to rest of the country
chart.prototype.plotArizona = function() {

    var that = this;

    var radius = 80;

    this.e.arcs = [];
    var delta = 0;
    var anchor_x = 125;
    var anchor_y = 100;

    this.e.labels.arc_center_label = paper.text(anchor_x, anchor_y, "");
    this.e.labels.arc_center_label.attr( this.styles.chart_arc_label_arc );
    this.storeOriginalGeometryLabel(this.e.labels.arc_center_label, "arc");

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

        delta += (d );

        var b = temp.getBBox();
        var temp2 = paper.text(b.cx, b.cy, point.state );

        temp2.attr(that.styles.chart_arc_label);
        if( i == 6 ) {
            temp2.attr(that.styles.chart_arc_label_important);
            temp.attr(that.styles.chart_arc_az);
        }

        var data = point;

        var temp3 = paper.set();
        temp3.push( temp );
        temp3.push( temp2 );

        temp3.mouseover(function() {
            temp.attr(that.styles.chart_arc_on);
            that.e.labels.arc_center_label.attr("text", that.comma(point.individuals)  + "\nRefugees" );
        }).mouseout(function() {
            if( i == 6 ) {
                temp.attr(that.styles.chart_arc_az);
            } else {
                temp.attr(that.styles.chart_arc);
            }
            that.e.labels.arc_center_label.attr("text", "" );
        });

        that.e.arcs.push(temp);

    })(data_usa.top_states[i], i);
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

            var temp_h = sorted[b].e.__should_be_height;

            sorted[b].e.attr("y", this.height - this.padding.bottom - offset - temp_h);
            // sorted[b].e.animate({ y: this.height - this.padding.bottom - offset - temp_h}, 1100, "<>");
            offset += temp_h;
        }
    }
}

// mod: account for scaling
chart.prototype.sortYearsByCountry = function(country, after_animation) {
    var that = this;

    for( year in this.range_x ) {
        // sort year
        var x = this.filterYear(year);
        var sorted = this.sortCountry(x, country);

        // reposition rects based on sort order
        var offset = 0;
        for( var b = 0; b < sorted.length; b++ )(function(block_to_sort) {
            var temp_h = block_to_sort.e.__should_be_height;
            that.busy = true;
            var logical_y = that.height - that.padding.bottom - offset - temp_h;
            var new_y = that.doResizeHelper(logical_y, that.height, that.new_height);

            // replace geometry y with logical_y
            block_to_sort.e.__geometry.oy = logical_y;

            block_to_sort.e.animate({ y: new_y}, 300, "<>", function() {
                that.busy = false;
                after_animation(block_to_sort);
            });
            offset += temp_h;

        })(sorted[b]);
    }
}

chart.prototype.doResizeHelper = function(x, x1, x2, p) {
    return((x * x2) / x1);
}

// resize blocks, labels and arcs
chart.prototype.doResize = function(w, h) {

    // optimization: resize already done, event misfire
    if( this.new_width === w && this.new_height === h ) {
        return;
    }

    // resize raphael canvas
    paper.setSize(w,h);
    var that = this;

    // blocks
    for( var i = 0; i < this.e.blocks.length; i++ )(function(block) {
        block.attr({
            x: that.doResizeHelper(block.__geometry.ox, that.width, w),//x: (w * 500) / block.__geometry.ox,
            y: that.doResizeHelper(block.__geometry.oy, that.height, h, "block"),
            width: that.doResizeHelper(block.__geometry.ow, that.width, w),
            height: that.doResizeHelper(block.__geometry.oh, that.height, h, "block")
        });
    })(this.e.blocks[i]);

    for( var s in this.e.labels)(function(label) {
        label.attr({
            x: that.doResizeHelper(label.__geometry.ox, that.width, w),
            y: that.doResizeHelper(label.__geometry.oy, that.height, h)
        })

        if( (w < 550 && label.__type == "year") ) {//} && label.__year % 2 ) {
            label.attr({"transform": "r-90"});
        } else {
            label.attr({"transform": "r0"});
        }
    })(this.e.labels[s]);

    // for reference
    this.new_width = w;
    this.new_height = h;
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
arizona.plotAll();
arizona.plotLabels();
arizona.plotArizona();
// arizona.computeGradient();

// pre-sorted in the datastream
// arizona.sortYearsBySize();

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
    arizona.doResize(w,h);
}
resizePaper();
windowAddEvent("resize", resizePaper, false);



</script>
</body>
</html>
