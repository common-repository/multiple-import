<?php
	$myFile = ABSPATH."wp-content/plugins/multipleimport/report.txt";
	$fh = fopen($myFile, 'w+') or die("can't open file");
	$stringData = "";
	fwrite($fh, $stringData);
	fclose($fh);
	header('Location: ?page=multipleimport/multipleimportform.php');	
?>