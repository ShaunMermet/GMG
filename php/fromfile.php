<?php
	$filename = $_GET['filename'];
	$handle = fopen("../data/$filename", "r");
	if ($handle) {
		$latMin = 90;
		$latMax = - 90;
		$lngMin = 180;
		$lngMax = - 180;
		$counter = 0;
	    while (($line = fgets($handle)) !== false) {
	        //echo $line.'<br>';
	        $doubledots = explode(':', $line);
	        $coma = explode(',',$doubledots[1]);
	        //echo $coma[0].' '.$coma[1].'<br>';
	        if ($coma[0] < $latMin){$latMin = $coma[0];}
	        if ($coma[0] > $latMax){$latMax = $coma[0];}
	        if ($coma[1] < $lngMin){$lngMin = $coma[1];}
	        if ($coma[1] > $lngMax){$lngMax = $coma[1];}
	        $counter ++;
	    }
	    echo $latMax.' '.$lngMin.'<br>';
        echo $latMin.' '.$lngMax.'<br>';
        echo '<br>';
        echo $counter.' line(s)';
	    fclose($handle);
	} else {
	    // error opening the file.
	}
?>