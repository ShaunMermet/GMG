<?php
	
	//Value in arc of 1 meter at lat 40 Degree
	$meterLat = 0.000009;
	$meterLong = 0.000012;

	$newFolderPath =  '../'.str_replace(".","_","img/".microtime(true));
	mkdir($newFolderPath);
	echo "create folder : ".$newFolderPath."<br>";
	$satelittePath = $newFolderPath."/satellite";
	mkdir($satelittePath);
	echo "create folder : ".$newFolderPath."/satellite"."<br>";
	$streetViewPath = $newFolderPath."/streetView";
	mkdir($streetViewPath);
	echo "create folder : ".$newFolderPath."/StreetView"."<br>";

	$txtpath = $newFolderPath."/README.txt";
	$txtfile = fopen($txtpath, "w") or die("Unable to open file!");
	$info = "The satellite pictures are named 
XX_YYYYYY_ZZ_VVVVVV_SS_SAT.jpg
XX.YYYYYY latitude
ZZ.VVVVVV longitude
SS scale 1 to 21 but the best seems to be 19
SAT just a tag to mean it's a Satellite image


The satellite pictures are named 
XX_YYYYYY_ZZ_VVVVVV_HHH_SV.jpg
XX.YYYYYY latitude
ZZ.VVVVVV longitude
HHH heading Accepted values are from 0 to 360 (both values indicating North, with 90 indicating East, and 180 South)
SV just a tag to mean it's a StreetView image


Satellite images are in the satellite folder
StreetView images are in the StreetView folder


The columns of the data folder :

Latitude
Longitude
Scale  1 to 21 but the best seems to be 19
SatellitePicture
GridFlag 1 for pictures from the original grid, 0 for the others (picture of the road closest to the grid)
StreetViewPictureHeading0
StreetViewPictureHeading45
StreetViewPictureHeading90
StreetViewPictureHeading135
StreetViewPictureHeading180
StreetViewPictureHeading225
StreetViewPictureHeading270
StreetViewPictureHeading315

The software will record satellite image in a grid shape setup by the interval parameter.
It will then search for a StreetView point available in the 50m and add it as a new GPS point in the list with the StreetView pictures associated

The preview only count the point on the grid.
For each point of the grid there is potentially 1 to 10 requests to Google API
The limit of requests is 25000 every 24h so it's better to keep the original number of grid points < 2500 (for now, improvements will come later)
";
	fwrite($txtfile, $info);

	$txtpath = $newFolderPath."/data.txt";
	$txtfile = fopen($txtpath, "w") or die("Unable to open file!");

	foreach ($_GET as $key => $val) {
	    echo ($key.", ".$val."<br>");
	    $line = $key.", ".$val;
	    fwrite($txtfile, $line);
	    fwrite($txtfile, "\n");
	}
	fwrite($txtfile, "\n");
	echo '<br>';
	$gpsLatTL = $_GET['latTL'];
	$gpsLongTL = $_GET['longTL'];
	$gpsLatBR = $_GET['latBR'];
	$gpsLongBR = $_GET['longBR'];


	$scale = $_GET['scale'];
	$interval = $_GET['interval'];
	$maxImages = $_GET['maxImages'];
	if($maxImages<=0){
		$maxImages = 25000;
	}
	
	//tmp limit 
	//$maxImages = 1;

	if($_GET['format'] == 'png'){
		$fileFormat = $_GET['format'];
	}else{
		$fileFormat = 'jpg';
	}

	//calc nbr
	$longDiff = $gpsLongBR-$gpsLongTL;
	$latDiff = $gpsLatTL-$gpsLatBR;
	$nbrImagesLong = ceil($longDiff/($meterLong*$interval));
  	$nbrImagesLat = ceil($latDiff/($meterLat*$interval));
  	echo $nbrImagesLat.= ' lines';
	echo "<br>";
	echo $nbrImagesLong.=' columns';
  	echo "<br>";
  	$line = $nbrImagesLat;
  	fwrite($txtfile, $line);
    fwrite($txtfile, "\n");
    $line = $nbrImagesLong;
  	fwrite($txtfile, $line);
    fwrite($txtfile, "\n");
	
	$totalImages = $nbrImagesLat*$nbrImagesLong;
	echo ($totalImages.=" planned");
  	echo "<br>";
  	$line = $totalImages;
  	fwrite($txtfile, $line);
    fwrite($txtfile, "\n");
    fwrite($txtfile, "\n");
	
  	
	
	$intro = 'Latitude Longitude Scale SatellitePicture GridFlag StreetViewPictureHeading0 StreetViewPictureHeading45 StreetViewPictureHeading90 StreetViewPictureHeading135 StreetViewPictureHeading180 StreetViewPictureHeading225 StreetViewPictureHeading270 StreetViewPictureHeading315';
	fwrite($txtfile, $intro);
	fwrite($txtfile, "\n");
	fwrite($txtfile, "\n");
	
	$gpsLat = $gpsLatTL;
	$gpsLong = $gpsLongTL;
	$count = 0;
	//loop
	set_time_limit(0);
	while($gpsLat >= $gpsLatBR && $gpsLong <= $gpsLongBR && $count < $maxImages){
		$src = 'http://maps.google.com/maps/api/staticmap?center='.$gpsLat.','.$gpsLong.'&zoom='.$scale.'&size=640x640&scale=1&maptype=satellite&format='.$fileFormat;
		if($_GET['APIkey']){
			$src.="&key=".$_GET['APIkey'];
		}

		$filenameSat = str_replace(".","_",$gpsLat."_".$gpsLong."_".$scale."_SAT").".".$fileFormat;
		$filepathGrid = $satelittePath.'/'.$filenameSat;
		//echo $src."<br>";
		
		$image = false;
		$i = 0;
		while($image == false && $i < 10)
		{
			if($i > 0){echo "Retry<br>";}
		    $image = file_get_contents($src);
		    $i++;
		    usleep(10);
		}

		$fp  = fopen($filepathGrid, 'w+'); 

		fputs($fp, $image); 
		fclose($fp); 
		unset($image);
		$count ++;
		echo $count." - create file : ".$filepathGrid." on try ".$i."<br>";


		$src = 'https://maps.googleapis.com/maps/api/streetview/metadata?location='.$gpsLat.','.$gpsLong;
		if($_GET['APIkey']){
			$src.="&key=".$_GET['APIkey'];
		}
		
		$metadata = false;
		$j = 0;
		while($metadata == false && $j < 10)
		{
			if($j > 0){echo "Retry metadata<br>";}
		    $metadata = file_get_contents($src);
		    $j++;
		    usleep(10);
		}
		$result = json_decode($metadata, true);
		echo $result['status'];
		echo "<br>";
		if($result['status'] == 'OK'){
		//Loc has street view data, 
			$newGpsLat = $result['location']['lat'];
			$newGpsLong = $result['location']['lng'];
			$grid = 1;
			//if loc different than street view data
			//log grid point with  1 0 0 0 0 0 0 ...
			//Ask for new point (offgrid) 
			if($newGpsLat!= $gpsLat || $newGpsLong!= $gpsLong){
				//updating data.txt file
				///log grid point with  1 0 0 0 0 0 0 ...
				$line = $gpsLat." ".$gpsLong." ".$scale." ".$filenameSat." 1 0 0 0 0 0 0 0 0";
        		fwrite($txtfile, $line);
        		fwrite($txtfile, "\n");
        		//Ask for new point (offgrid)
        		$src = 'http://maps.google.com/maps/api/staticmap?center='.$newGpsLat.','.$newGpsLong.'&zoom='.$scale.'&size=640x640&scale=1&maptype=satellite&format='.$fileFormat;
				if($_GET['APIkey']){
					$src.="&key=".$_GET['APIkey'];
				}

				$filenameSat = str_replace(".","_",$newGpsLat."_".$newGpsLong."_".$scale."_SAT").".".$fileFormat;
				$filepathOffGrid = $satelittePath.'/'.$filenameSat;
				
				$image = false;
				$k = 0;
				while($image == false && $k < 10)
				{
					if($k > 0){echo "RetryOffGrid<br>";}
				    $image = file_get_contents($src);
				    $k++;
				    usleep(10);
				}

				$fp  = fopen($filepathOffGrid, 'w+'); 

				fputs($fp, $image); 
				fclose($fp); 
				unset($image);
				echo $count."bis - create file : ".$filepathOffGrid." on try ".$k."<br>";

				$grid = 0;
			}

			//updating data.txt file
			$line = $newGpsLat." ".$newGpsLong." ".$scale." ".$filenameSat." ".$grid;
			fwrite($txtfile, $line);
			//get sw data in all case with new loc
			for ($i = 0; $i < 8; $i++) {
				$heading = $i*45;
			    $filenameSW = str_replace(".","_",$newGpsLat."_".$newGpsLong."_".$heading."_SV").".".$fileFormat;
			    fwrite($txtfile, " ".$filenameSW);
				$filepath = $streetViewPath.'/'.$filenameSW;
				$src = 'https://maps.googleapis.com/maps/api/streetview?size=640x640&location='.$newGpsLat.','.$newGpsLong.'&heading='.$heading.'&pitch=0';
				if($_GET['APIkey']){
					$src.="&key=".$_GET['APIkey'];
				}

				$image = false;
				$l = 0;
				while($image == false && $l < 10)
				{
					if($l > 0){echo "RetrySW<br>";}
				    $image = file_get_contents($src);
				    $l++;
				    usleep(10);
				}

				$fp  = fopen($filepath, 'w+'); 

				fputs($fp, $image); 
				fclose($fp); 
				unset($image);

				echo $count."_".$i." - create file : ".$filepath." on try ".$l."<br>";
			}
			fwrite($txtfile, "\n");
        	
		}else{
			//Loc do not have street view data, log grid point
			echo 'No street view';
			echo "<br>";

			//updating data.txt file
			$line = $gpsLat." ".$gpsLong." ".$scale." ".$filenameSat." 1 0 0 0 0 0 0 0 0";
        	fwrite($txtfile, $line);
        	fwrite($txtfile, "\n");
		}
		

		$gpsLong = $gpsLong + $meterLong*$interval;
		if($gpsLong > $gpsLongBR){
			$gpsLong = $gpsLongTL;
			$gpsLat = $gpsLat -$meterLat*$interval;
		}
	}
	//Closing txt file with gps data
    fclose($txtfile);


	
	



	//35.864225
	//139.507488

	//35.850302
	//139.532219

	//35.867087, 139.502674
	//35.843975, 139.537244

	//Randy
	//36.338389, 138.621852
	//36.308583, 138.671219

	//36.358499, 138.601271
	//36.301785, 138.684018

    //February 2
	//36.338680 138.620590
	//36.320750 138.651834

	//Hirota-san
	//35.913320 139.504943
	//35.849201 139.639783
    //mid
	//35.8812605

    //				139.504943 		139.572363		139.639783
	//35.913320
	//35.8812605
	//35.849201
?>
