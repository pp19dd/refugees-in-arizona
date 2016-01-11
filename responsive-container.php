<!doctype html>
<html>
<head>
<title>Refugees in Arizona 2002-2015</title>
<style>
body, html { width:100%; height:100%; padding:0; margin:0; background-color: silver }

.content {
    margin: auto;
    width: 800px;
    background-color: gray;
}

#chart_ra { width: 800px; height: 400px; }

@media screen and (max-width:800px) {
    #chart_ra { width: 600px; height: 300px; }
}

@media screen and (max-width:600px) {
    #chart_ra { width: 500px; height: 250px; }
}

@media screen and (max-width:500px) {
    #chart_ra { width: 400px; height: 800px; }
}

</style>
<script src="raphael-min.js"></script>
<script src="scale.raphael.js"></script>
<script src="rainbowvis.js"></script>
</head>
<body>

<div class="content">
    <p><?php for( $i = 0; $i < 100; $i++ ) { ?>This is a sentence. <?php } ?></p>
    <iframe id="chart_ra" frameborder="0" src="chart.php"></iframe>
    <p><?php for( $i = 0; $i < 100; $i++ ) { ?>This is a sentence. <?php } ?></p>
</div>

</body>

</html>
