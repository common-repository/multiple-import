<div class="wrap">
<?php
	//initialize the PHP 
	session_start();
	set_time_limit(0);
	if ( !is_null($_SESSION["import_blog_ids"]) ) {
		$import_blog_ids = $_SESSION["import_blog_ids"];
	}
	$import_this_blog = array_shift($import_blog_ids);
	
	$myFile = ABSPATH."wp-content/plugins/multipleimport/report.txt";
	$fh = fopen($myFile, 'a') or die("can't open file");	
	
	$stringData = "Importing blog ID: ".$import_this_blog."\n";	
	echo "<h2>Importing blog ID:".$import_this_blog."</h2>";
	fwrite($fh, $stringData);	
	echo "<ol>";

	if ( $_SESSION['attachment'] ) {
		$attachments = $wpdb->get_results("SELECT * FROM wp_".$import_this_blog."_posts WHERE post_type='attachment'");
		foreach ( $attachments as &$attachment ) {
			echo '<li>Checking attachment <i>'.$attachment->post_title.'</i></li>';
			$upload = wp_upload_dir($post->post_date);
			$filename = basename($attachment->guid);
			$upload = wp_upload_bits($filename,0,'',$attachment->post_date);
			
			//var_dump($upload);
			if ( $upload['error'] ) {
				echo $upload['error'];
			} 
			$headers = wp_get_http($attachment->guid, $upload['file']);
			$mimetype = wp_check_filetype($filename);
			$attachment_data = array(
				'post_title' => substr(str_replace("”","",str_replace("“", "", $attachment->post_title)),0,10),
				'post_content' => $attachment->post_content,
				'post_status' => $attachment->post_status,
				'guid' => $upload['url'],
				'post_mime_type' => $mimetype['type']
			);
			echo "<p>Importing attachment <i>".$attachment->post_title."</i>...</p>";
			$att_id = wp_insert_attachment($attachment_data);
			$att_data = wp_generate_attachment_metadata($att_id, $upload['file']);
			wp_update_attachment_metadata( $att_id,  $att_data );
			
			$attachment_links[] = array( $upload['url'], $attachment->guid);
		}
		unset($attachment);
	}
	

	$posts = $wpdb->get_results("SELECT * FROM wp_".$import_this_blog."_posts WHERE post_type='post' OR post_type='page'");
	foreach ($posts as &$post) {  
		//var_dump($post);              
		$new_post = array();
		$new_post['post_title'] = $post->post_title;
		$new_post['post_content'] = $post->post_content;
		
		foreach ($attachment_links as &$attachment_link) {
			$new_post['post_content'] = str_replace($attachment_link[1],$attachment_link[0],$new_post['post_content']);
		}
		unset($attachment_link);
		
		$new_post['post_excerpt'] = $post->post_excerpt;
		$new_post['post_date'] = $post->post_date;
		$new_post['post_status'] = $post->post_status;
		$new_post['post_author'] = $post->post_author;
		$new_post['post_type'] = $post->post_type;
                
		//get all the term taxonomy ids for this post
		$post_term_taxonomy_ids = $wpdb->get_col("SELECT term_taxonomy_id
			FROM wp_".$import_this_blog."_term_relationships
			WHERE object_id=".$post->ID);

		foreach($post_term_taxonomy_ids as &$post_term_taxonomy_id) {
			$term_taxonomy = $wpdb->get_row("SELECT term_id, taxonomy 
				FROM wp_".$import_this_blog."_term_taxonomy  
				WHERE term_taxonomy_id=".$post_term_taxonomy_id);

			//get term name
			$term_name = $wpdb->get_row("SELECT name, term_id FROM wp_".$import_this_blog."_terms 
				WHERE term_id=".$term_taxonomy->term_id);
	
			if($term_taxonomy->taxonomy == 'category') {
				$postcats[] = $term_name->name;
			} elseif ($term_taxonomy->taxonomy == 'post_tag') {
				$posttags[] = $term_name->name;
			}
		}
		unset($post_term_taxonomy_id);
		
		//assign tags to post
		$new_post['tags_input'] = $posttags;
		
		//check if this post or page pass the filter
		echo "<li>Checking ".$post->post_type." <i>".$post->post_title."</i></li>";
        $filter_posttags = explode(',', $_SESSION['post-tags']);
        $filter_postcats = explode(',', $_SESSION['post-cats']);
        $filter_pagetags = explode(',', $_SESSION['page-tags']);
        $filter_pagecats = explode(',', $_SESSION['page-cats']);    		
		if ( $post->post_type == 'page' ) {
			//echo "<p>this is a page, do some page check!</p>";
			if ( is_null($_SESSION['import-page']) ) {
				$import_this_post = 0;
				//echo "<p>import page checkbox is unchecked, do not import this page</p>";
			} else {
				//echo "<p>import page checkbox is checked, do some more filter check!</p>";
				if ( $import_all_page ) {
					//echo "<p>All filters are off, import this page!</p>";
					$import_this_post = 1;
				} else {
					//echo "<p>a filter is on, check if this page pass filter or not!</p>";
					//echo "<p>first check tag filter, and then check category filter</p>";
					if ( empty($_SESSION['page-tags']) ) {
						//echo "<p>page tag filter is off! nothing to check!</p>";
						$import_this_post = 1;
					} else {
						//echo "<p>page tag filter is on! do some check!</p>";
						if ( !is_null($posttags) ) {
							//echo "<p>this page has some tags, check if they match!</p>";
							foreach ( $posttags as &$posttag ) {
								if ( in_array($posttag, $filter_pagetags)) {
									//echo "<p>this page has a selected tag!</p>";
									$import_this_post = 1;
								} else {
									//echo "<p>this page doesn't have a selected tag!</p>";
									$import_this_post = 0;
								}
							}
						} else {
							//echo "<p>this page has no tags! nothing to check!</p>";
							$import_this_post = 0;
						}
					}
				}
			}
		} elseif ( $post->post_type == 'post' ) {
			//echo "<p>this is a post, do some post check!</p>";
			if ( is_null($_SESSION['import-post']) ) {
				//echo "<p>import post checkbox is unchecked, do not import this post</p>";
				$import_this_post = 0;
			} else {
				//echo "<p>import post checkbox is checked, do some more filter check!</p>";
				if ( $import_all_post ) {
					//echo "<p>All filters are off, import this post!</p>";
					$import_this_post = 1;
				} else {
					//echo "<p>a filter is on, check if this post pass filter or not!</p>";
					//echo "<p>first check tag filter, and then check category filter</p>";
					if ( empty($_SESSION['post-tags']) ) {
						//echo "<p>post tag filter is off! nothing to check!</p>";
						$pass_post_tags = 1;
					} else {
						//echo "<p>post tag filter is on! do some check!</p>";
						if ( !is_null($posttags) ) {
							//echo "<p>this post has some tags, check if they match!</p>";
							foreach ( $posttags as &$posttag ) {
								if ( in_array($posttag, $filter_posttags)) {
									//echo "<p>this post has a selected tag!</p>";
									$pass_post_tags = 1;
								} else {
									//echo "<p>this post doesn't have a selected tag!</p>";
									$pass_post_tags = 0;
								}
							}
						} else {
							//echo "<p>this post has no tags! nothing to check!</p>";
							$pass_post_tags = 0;
						}
					}
					if ( empty($_SESSION['post-cats']) ) {
						//echo "<p>post cat filter is off! nothing to check!</p>";
						$pass_post_cats = 1;
					} else {
						//echo "<p>post cat filter is on! do some check!</p>";
						if ( !is_null($postcats) ) {
							//echo "<p>this post has some tags, check if they match!</p>";
							foreach ( $postcats as &$postcat ) {
								if ( in_array($postcat, $filter_postcats)) {
									//echo "<p>this post has a selected cat!</p>";
									$pass_post_cats = 1;
								} else {
									//echo "<p>this post doesn't have a selected cat!</p>";
									$pass_post_cats = 0;
								}
							}
						} else {
							//echo "<p>this post has no cats! nothing to check!</p>";
							$import_this_post = 0;
						}
					}
					if ( $pass_post_cats && $pass_post_tags ) {
						$import_this_post = 1;
						//echo "<p>Both filters passed!</p>";
					} else {
						$import_this_post = 0;
						//echo "<p>One filter failed!</p>";
					}
				}
			}
		}
	
		if( $import_this_post == 1 && ( $post->post_type == 'page' || $post->post_type == 'post' ) ) {
			//insert the new post
			echo "<p>Importing ".$post->post_type." <i>".$post->post_title."</i>...</p>";		
			$new_post_id = wp_insert_post($new_post);
			$import_count++;
			if ( count($postcats) != 0 ) {
				wp_create_categories($postcats, $new_post_id);
			}
		} else {
			echo "<p>".$post->post_type." ".$post->post_title."</i> is not imported</p>";
		}		
		unset($new_post);
		unset($posttags);
		unset($postcats);
	}            
	unset($post);
	unset($attachment_links);
	
	$stringData = '# of successful import post:'.$import_count."\n";
	fwrite($fh, $stringData);
echo '</ol>';
// javascript to reload this page
?>

<?php
	
	if ( empty($import_blog_ids) ) {
		session_destroy();
		echo '<h3>All done. <a href="'.get_bloginfo('url').'">Have fun!</a></h3>';
		$stringData = "All done. Have fun!\n";
		fwrite($fh, $stringData);
		fclose($fh);
	} else {
		$_SESSION["import_blog_ids"] = $import_blog_ids;
		?>
<form name="myform" method="POST" action="">
	<input class="button" type="submit" value="Next" />
</form>
<script type="text/javascript" language="javascript">
var reloadTimer = null;
window.onload = function()
{
    setReloadTime(0); // In this example we'll use 5 seconds.
}
function setReloadTime(secs)
{
    if (arguments.length == 1) {
        if (reloadTimer) clearTimeout(reloadTimer);
        reloadTimer = setTimeout("setReloadTime()", Math.ceil(parseFloat(secs) * 1000));
    }
    else {
        document.myform.submit();
    }
}
</script>  
		<?php
	}
?>
</div>
