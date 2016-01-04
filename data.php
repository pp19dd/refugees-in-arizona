<?php

$data = explode("\n", trim(file_get_contents("data.txt")));
$years = range(2002, 2015);

$all_values = array();

foreach( $data as $k => $v ) {
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
            "count" => trim($t[$k2]),
        );

        $y[] = $point;

        $all_values[] = $point["count"];
	}

    # and add some aggregates
	$data[$k] = array(
		"country" => $c,
        "placement" => $y,
        "total" => array_sum($t),
        "max" => max($t),
        "min" => min($t)
	);
}
