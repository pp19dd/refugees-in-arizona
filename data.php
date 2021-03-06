<?php

$temp = explode("\n", trim(file_get_contents("data.txt")));
$years = range(2002, 2015);

$all_values = array();

foreach( $temp as $k => $v ) {
	$t = explode(",", $v );

    # pull out country name
	$c = array_shift($t);

    # rest should be all integers
    foreach( $t as $k2 => $v2 ) {
        $t[$k2] = intval($v2);
    }

    # make the years more ... json friendly
	$y = array();
	foreach( $t as $k2 => $v2 ) {
        $point = array(
            "year" => $years[$k2],
            "count" => intval(trim($t[$k2])),
        );

        $y[] = $point;

        $all_values[] = $point["count"];
	}

    # and add some aggregates
	$temp[$k] = array(
		"country" => $c,
        "placement" => $y
	);
}

$data = $temp;

function get_sum($placement) {
	$c = 0;
	foreach( $placement as $v ) {
		$c += $v["count"];
	}
	return( $c );
}

usort($data, function($a, $b) {
	$sum_a = get_sum($a["placement"]);
	$sum_b = get_sum($b["placement"]);

	if( $sum_a === $sum_b ) return(0);
	if( $sum_a > $sum_b ) return(1);
	return(-1);
});

#echo "<PRE>";print_r( $data ); die;
