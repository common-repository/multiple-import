<div class="wrap">
<?php 
if($_POST['upload'] == "Import") {
    //retreive all the links found in the uploaded .txt file
    if ( is_uploaded_file($_FILES['uploadedfile']['tmp_name'])) {
        echo "<h2>Beginning to Import</h2>";
        $fileData = file_get_contents($_FILES['uploadedfile']['tmp_name']);
		
        $host = "([a-z\d][-a-z\d]*[a-z\d]\.)+[a-z][-a-z\d]*[a-z]";
        $port = "(:\d{l,})?";
        $path = "(\/[^?<>\#\"\s]+)?";
        $query = "(\?[^<>\#\"\s]+)?";
        preg_match_all("#((ht|f)tps?:\/\/{$host}{$port}{$path}{$query})#i", $fileData, $links);        
        // retreive all the blogs found in the system
        $blogs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
		
		$myFile = ABSPATH."wp-content/plugins/multipleimport/report.txt";
		$fh = fopen($myFile, 'a') or die("can't open file");
		$stringData = "Importing the following blog(s):\n";
		fwrite($fh, $stringData);	
		
        foreach ($blogs as &$blog) {
            $blog_info = $wpdb->get_row("SELECT * FROM $wpdb->blogs WHERE blog_id=".$blog);
            // compare the links found in the text file and the blogs in the system
            // and then store all the matching blog id's in array $import_blog_ids[]
            //$links[0] contains the full path of the links found in the txt file
            if(in_array('http://'.$blog_info->domain.$blog_info->path, $links[0]))
            {
                $import_blog_ids[] = $blog_info->blog_id;
				$import_blog_links[] = 'http://'.$blog_info->domain.$blog_info->path;
            }
        }
        unset($blog);

	echo 'You are importing the following blogs:<br />';
	foreach ($import_blog_links as &$import_blog_link) {
		echo '<ul>';
		echo '<li>'.$import_blog_link.'</li>';
		echo '</ul>';
		$stringData = $import_blog_link."\n";
		fwrite($fh, $stringData);
	}
	unset($import_blog_link);        
	fclose($fh);
        //get tag and categories filter options
	$filter_posttags = explode(',', $_POST['post-tags']);
	$filter_postcats = explode(',', $_POST['post-cats']);
	$filter_pagetags = explode(',', $_POST['page-tags']);
	$filter_pagecats = explode(',', $_POST['page-cats']);    
	// check if the post filters are on, import all posts if filters are off
	if ( empty($_POST['post-tags']) && empty($_POST['post-cats']) ) {
		$import_all_post = 1;
		// echo "<p>All the post filters are off, import all posts!</p>";
	} else {
		$import_all_post = 0;
		// echo "<p>At least one post filter is on, do not import all posts!</p>";
	}
	// check if the page filters are on, import all pages if filters are on
	if ( empty($_POST['page-tags']) ) {
		// echo "<p>The page filter is off, import all pages!</p>";
		$import_all_page = 1;
	} else {
		//echo "<p>The page filter is on, do not import all pages!</p>";
		$import_all_page = 0;
	}
	session_start();
	$_SESSION["import_blog_ids"] = $import_blog_ids;
	$_SESSION["import_blog_links"] = $import_blog_links;
	$_SESSION["post-tags"] = $_POST['post-tags'];
	$_SESSION["post-cats"] = $_POST['post-cats'];
	$_SESSION["page-tags"] = $_POST['page-tags'];
	$_SESSION["page-tags"] = $_POST['page-cats'];
	$_SESSION["import-post"] = $_POST["import-post"];
	$_SESSION["attachment"] = $_POST['attachment'];
	$_SESSION['import-page'] = $_POST['import-page'];
?>
<form method="POST" action="?page=multipleimport/beginimport.php">
	<input class="button" type="submit" value="Next" />
</form>
<?php
    } else {
		echo "<h2>Import Error</h2>";
		echo "No files selected! Please try again.";
    }
?>
<?php } else { ?>
<script type="text/javascript">
function showHide(id){
   el = document.getElementById(id);
   el.style.display = (el.style.display != 'block')? 'block' : 'none';
}
</script>
<form enctype="multipart/form-data" method="POST">
    <h2>Multiple Import (<a href="/wp-content/plugins/multipleimport/report.txt">Import Log</a>, <a href="?page=multipleimport/clearreport.php">Clear</a>)</h2>
    <p>Please upload a .txt file which contains all  the links you wish to import.</p>
    <p>
        <input="hidden" name="MAX_FILE_SIZE" value="100000" />
        <label for="uploadedfile">Choose a file to upload:</label>
        <input id="uploadedfile" name="uploadedfile" type="file" />
    </p>
    <h2>Import Setting</h2>
    <p>
        <input id="import-post" type="checkbox" name="import-post" onclick="showHide('post-tab')" checked />
        <label for="import-post">Import posts</label>
    </p>
    <table style="display:block" id="post-tab" class="form-table">
        <tbody>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="post-tags">Only import posts with tags</label>
                </th>
                <td>
                    <input name="post-tags" id="post-tags" type="text" />
                    <span class="howto">Seperate tags with commas, blank will import all</span>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="post-cats">Only import posts with categories</label>
                </th>
                <td>
                    <input name="post-cats" id="post-cats" type="text" />
                    <span class="howto">Seperate categories with commas, blank will import all</span>
                </td>
            </tr>
        </tbody>
    </table>
    <p>
        <input id="import-page" type="checkbox" name="import-page" onclick="showHide('page-tab')" checked />
        <label for="import-page">Import pages</label>
    </p>
    <table style="display:block" id="page-tab" class="form-table">
        <tbody>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="page-tags">Only import pages with tags</label>
                </th>
                <td>
                    <input name="page-tags" id="page-tags" type="text" />
                    <span class="howto">Seperate tags with commas, blank will import all</span>
                </td>
            </tr>
        </tbody>
    </table>
    <h2>Other Options</h2>
    <p>
        <input id="import-attachment" type="checkbox" name="attachment" />
        <label for="import-attachment">Download and import file attachments</label>
    </p>
    <!--  <p>
        <input id="import-author" type="checkbox" name="author" />
        <label for="import-author">Keep entry author information (This function is currently disabled)</label>
    </p> -->
    <p class="submit">
        <input class="button" type="submit" name="upload" value="Import" />
    </p>
</form>
<?php } ?>
</div>
