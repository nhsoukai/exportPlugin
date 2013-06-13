<?php
/*
Plugin Name: Plugin labsticc
Description: Plugin d'export du contenu du site pour labo Labsticc
Author: Arnaud Fabre, Ahmed Oulabas, Soukaina Nait Hmid
Version: 1.0
*/

define('ABSOLUTE_PATH', plugin_dir_path(__FILE__));
define('ABSOLUTE_URL', plugin_dir_url(__FILE__));

add_action("admin_menu", "addExportPanel"); // Add plugin to wordpress admin menu

// Actions based context
if(isset($_POST['export'])){
	processExport();
} elseif (isset($_POST['gabarit_send'])) {
	processGabaritUpload();
} elseif(isset($_GET['gabarit_delete'])) {
	processGabaritDelete();
} elseif(isset($_POST['after_check'])){
	afterCheck();
}

function processExport(){
	/**
	 * Fonction called when form is submitted
	 **/
	global $wpdb;
    $wpdb->show_errors(); // Display sql errors

	$date_from = $_POST['date_from'];
	$date_to = $_POST['date_to'];
	
	if(empty($date_from) || empty($date_to)){
		$_SESSION['wp_labsticc_export']['error'] = 'Dates invalides';
		return;
	}

	if(!is_file(ABSOLUTE_PATH . 'gabarits/'.$_POST['gabarit'])){
		$_SESSION['wp_labsticc_export']['error'] = 'Gabarit invalides';
		return;
	}

	$order = $_POST['order'];

	$terms_ids = array();

	foreach ($_POST['pub'] as $id)
		$terms_ids[] = intval($id);

	// WP function that returns posts ids with terms (category) seleciton
	$posts_ids = get_objects_in_term($terms_ids, 'category');

	// Select all posts by ids between dates
	$sql_query = 'SELECT posts.ID, posts.post_title, posts.post_date, YEAR(posts.post_date) as year,
					     users.display_name as user_dn
					FROM '.$wpdb->posts.' as posts
						LEFT JOIN '.$wpdb->users.' as users
						ON `posts`.`post_author` = `users`.`id`
					WHERE posts.ID IN ('.implode(',', $posts_ids).')
					AND posts.post_date > STR_TO_DATE(%s, "%%d/%%m/%%Y") AND posts.post_date < STR_TO_DATE(%s, "%%d/%%m/%%Y")';
	$sql_prepared = $wpdb->prepare($sql_query, $date_from, $date_to);
	$posts_result = $wpdb->get_results($sql_prepared);

	// Organise posts by id :
	$posts = array();
	$posts_ids = array();
	foreach ($posts_result as $post) {
		$posts[intval($post->ID)] = $post;
		$posts_ids[] = intval($post->ID);
	}

	if(empty($posts_ids)){
		$_SESSION['wp_labsticc_export']['error'] = 'Aucun résultat pour les critères spécifiés';
		return;
	}

	/** 
	 * Select terms related to posts 
	 * terms contains category, tags and authors (due to "co author plus" plugin 
	 * that uses terms for multi-author functionality)
	 * The relation between post and terms is "Many to Many"
	 * that means a term has many post and a post has many term
	 * this relation is subtle to treat, a way is to
	 * proceed to one query per entry which could be a
	 * great pain if there is a lot of entries.
	 * Here the solution is a complexe sql query that return
	 * an entry per couple post - term.
	 * For processing we have to organise the results by term_id
	 * and by post_id.
	 *
 	 *
	 * This query has an intensive use of joins due to
	 * the wordpress database structure
	 * term_relationships table links post and term_taxonomy
	 * term_taxonomy table links term_taxonomy and taxonomy
	 * terms table contains information about a term
	 * finally we link users table to terms table using term name and user_login
	**/

	$sql_terms = 'SELECT * FROM '.$wpdb->term_relationships.' as relations

				  INNER JOIN '.$wpdb->term_taxonomy.' as term_taxonomy
				  ON relations.term_taxonomy_id = term_taxonomy.term_taxonomy_id
				  	INNER JOIN '.$wpdb->terms.' as terms
				  	ON term_taxonomy.term_id = terms.term_id
				  		LEFT JOIN '.$wpdb->users.' as users
				  		ON terms.name = users.user_login

				  WHERE relations.object_id IN ('.implode(',', $posts_ids).')
				  AND term_taxonomy.taxonomy IN ("author", "category")

				  ORDER BY terms.term_id, relations.object_id';

	// Terms contains a entry for each coouple post - term
	$terms = $wpdb->get_results($sql_terms);
	
	// Organize terms by post_id
	$terms_by_post_id = array();
	foreach ($terms as $term) {
		$terms_by_post_id[$term->object_id][] = $term;	
	}

	// Organize terms by term_id
	$terms_by_id = array();
	foreach ($terms as $term) {
		$terms_by_id[intval($term->term_id)][] = $term;
	}

	$data = array(
		'order' => $order,
		'terms_by_id' => $terms_by_id,
		'terms_by_post_id' => $terms_by_post_id,
		'posts' => $posts,
		'gabarit' => $_POST['gabarit']
	);

	if($_POST['format'] == 'odt')
		_exportOdt($data);
	elseif($_POST['format'] == 'html_odt'){
		$_SESSION['data_display'] = array();
		$_SESSION['data_order'] = $order;
		$_SESSION['data_gabarit'] = $_POST['gabarit'];

		$order = explode(',', $order);
		// For each category by the order specified
		foreach ($order as $term_id) {
			$term_id = intval($term_id);
			$category_name = $terms_by_id[$term_id][0]->name;
			$_SESSION['data_display'][$category_name] = array();

			// For each publication in this category 
			foreach ($terms_by_id[$term_id] as $term) {
				// Get authors
				$authors = array();
				foreach ($terms_by_post_id[(int) $term->object_id] as $term2) {
					if($term2->taxonomy == 'author') {
						$authors[] = $term2->display_name;
					}

				}
				// In case of Co Author is not used then set the user by post_author
				if(count($authors) == 0)
					$authors = array($posts[(int) $term->object_id]->user_dn);
		
				$_SESSION['data_display'][$category_name][$term->object_id] = array(
						'authors' => implode(', ', $authors),
						'pubtitle' => $posts[ (int) $term->object_id]->post_title,
						'year' => $posts[ (int) $term->object_id]->year,
					);
			}
		
		}


	}
}

function afterCheck(){
	/**
	 * Fonction called after check form is submitted
	 **/
	global $wpdb;
	$order = $_POST['order'];

	$posts_id = array();
	foreach ($_POST as $post_id => $value) {
		if(is_integer($post_id) && $value == "on")
			$posts_ids[] = $post_id;
	}

	// Select all posts by ids between dates
	$sql_query = 'SELECT posts.ID, posts.post_title, posts.post_date, YEAR(posts.post_date) as year,
					     users.display_name as user_dn
					FROM '.$wpdb->posts.' as posts
						LEFT JOIN '.$wpdb->users.' as users
						ON `posts`.`post_author` = `users`.`id`
					WHERE posts.ID IN ('.implode(',', $posts_ids).')';
	$sql_prepared = $wpdb->prepare($sql_query, $date_from, $date_to);
	$posts_result = $wpdb->get_results($sql_prepared);

	$posts = array();
	$posts_ids = array();
	foreach ($posts_result as $post) {
		$posts[intval($post->ID)] = $post;
		$posts_ids[] = intval($post->ID);
	}

	$sql_terms = 'SELECT * FROM '.$wpdb->term_relationships.' as relations

				  INNER JOIN '.$wpdb->term_taxonomy.' as term_taxonomy
				  ON relations.term_taxonomy_id = term_taxonomy.term_taxonomy_id
				  	INNER JOIN '.$wpdb->terms.' as terms
				  	ON term_taxonomy.term_id = terms.term_id
				  		LEFT JOIN '.$wpdb->users.' as users
				  		ON terms.name = users.user_login

				  WHERE relations.object_id IN ('.implode(',', $posts_ids).')
				  AND term_taxonomy.taxonomy IN ("author", "category")

				  ORDER BY terms.term_id, relations.object_id';

	// Terms contains a entry for each coouple post - term
	$terms = $wpdb->get_results($sql_terms);
	
	// Organize terms by post_id
	$terms_by_post_id = array();
	foreach ($terms as $term) {
		$terms_by_post_id[$term->object_id][] = $term;	
	}

	// Organize terms by term_id
	$terms_by_id = array();
	foreach ($terms as $term) {
		$terms_by_id[intval($term->term_id)][] = $term;
	}

	$data = array(
		'order' => $order,
		'terms_by_id' => $terms_by_id,
		'terms_by_post_id' => $terms_by_post_id,
		'posts' => $posts,
		'gabarit' => $_POST['gabarit']
	);

	_exportOdt($data);
}

function _exportOdt($data){
	
	extract($data);
	$order = explode(',', $order);

	// Final export 
	require_once(ABSOLUTE_PATH . 'library_odt/odf.php');
	$odf = new odf(ABSOLUTE_PATH . 'gabarits/'.$gabarit);
	$odf->setVars('titre', 'Export publications labsticc', true, 'UTF-8');

	$categories = $odf->setSegment('categories');

	// For each category by the order specified
	foreach ($order as $term_id) {
		$term_id = intval($term_id);
		$categories->setVars('categorytitle', $terms_by_id[$term_id][0]->name, true, 'UTF-8');

		// For each publication in this category 
		foreach ($terms_by_id[$term_id] as $term) {
			// Get authors
			$authors = array();
			foreach ($terms_by_post_id[(int) $term->object_id] as $term2) {
				if($term2->taxonomy == 'author') {
					$authors[] = $term2->display_name;
				}

			}
			
			// In case of Co Author is not used then set the user by post_author
			if(count($authors) == 0)
				$authors = array($posts[(int) $term->object_id]->user_dn);

			// $categories->articles->code('CODE TEST');
			$categories->articles->authors( implode(', ', $authors));
			$categories->articles->pubtitle(utf8_decode($posts[ (int) $term->object_id]->post_title));
			$categories->articles->year($posts[ (int) $term->object_id]->year);
			$categories->articles->merge();
		}
		$categories->merge();
	}
	$odf->mergeSegment($categories);
	
	$odf->exportAsAttachedFile();
}

function processGabaritUpload(){
	if(pathinfo($_FILES['gabarit']['name'],PATHINFO_EXTENSION) != 'odt'){
		$_SESSION['wp_labsticc_export']['error'] = 'Fichier invalide (format odt)';
		return;
	}

	if(empty($_POST['name'])){
		$_SESSION['wp_labsticc_export']['error'] = 'Nom de gabarit vide';
		return;
	}
	$name = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $_POST['name']);
	$name = strtolower(trim($name, '-'));
	$name = preg_replace("/[\/_|+ -]+/", '-', $name);

	move_uploaded_file($_FILES['gabarit']['tmp_name'],  ABSOLUTE_PATH . 'gabarits/'.$name.'.odt');
}

function processGabaritDelete(){
	$gabarit = $_GET['gabarit_delete'];
	if(!file_exists( ABSOLUTE_PATH . 'gabarits/'.$gabarit)){
		$_SESSION['wp_labsticc_export']['error'] = 'Gabarit invalide';
		return;
	}
	if($gabarit == 'default.odt'){
		$_SESSION['wp_labsticc_export']['error'] = 'Vous ne pouvez pas supprimer ce gabarit';
		return;
	}


	unlink(ABSOLUTE_PATH . 'gabarits/'.$gabarit);
	$_SESSION['wp_labsticc_export']['msg'] = 'Gabarit supprimé';
}

function exportPanel(){
    /**
     * Main page 
     */
    global $wpdb;
    
    // $wpdb->show_errors(); // Display sql errors

    $sql_categories = 'SELECT term_id, name FROM '.$wpdb->terms.' WHERE term_id IN 
    				          (SELECT term_id 
    				          	FROM '.$wpdb->term_taxonomy.'
    				            WHERE `taxonomy` = "category" AND `count` > 0)';
    $res_categories = $wpdb->get_results($sql_categories);

    $gabarits_name = array();
	foreach (glob(ABSOLUTE_PATH.'gabarits/*.odt') as $gabarits) {
		$gabarits_name[] = pathinfo($gabarits, PATHINFO_BASENAME);
	}

    include('panel.php');
}

function exportPanelJsCss(){
	/**
	 * Add css and javascript
	 */
    $siteurl = get_option('siteurl');
    $css = array('style.css', 'jqueryui.css');
    $js = array('jquery-1.5.1.min.js', 'jquery-ui-1.8.14.custom.min.js', 'main.js');

    foreach($css as $e){
		$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/css/'.$e;
		echo '<link href="'.$url.'" rel="stylesheet" type="text/css" />';
	}

	foreach($js as $e){
		$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/js/'.$e;	
		echo '<script type="text/javascript" src="'.$url.'"></script>';
	}
}

function exportOptions(){
	$gabarits_name = array();
	foreach (glob(ABSOLUTE_PATH.'gabarits/*.odt') as $gabarits) {
		$gabarits_name[] = pathinfo($gabarits, PATHINFO_BASENAME);
	}

	include('panelOptions.php');
}

function addExportPanel(){
	/**
	 * Add plugin to wordpress menu, set
	 * the function to call.
	 */
	$hook = add_menu_page("Panel d'export",
			 "Export labsticc",
			 "administrator",
			 "labsticcexport",
			 "exportPanel");

	$hook2 = add_submenu_page("labsticcexport", 
			"Configuration export",
			"Configuration",
			"administrator",
			"labsticcexport-options",
			"exportOptions");
	
    // Custum css and js only for the plugin
 	add_action("admin_head-{$hook}", 'exportPanelJsCss' );
 	add_action("admin_head-{$hook2}", 'exportPanelJsCss' );
}


?>
