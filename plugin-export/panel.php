<h1>Plugin Wordpress d'exports des publications Lab-Sticc</h1>
<?php
if(!empty($_SESSION['wp_labsticc_export']['error']))
	echo '<div id="message" class="error">'.$_SESSION['wp_labsticc_export']['error'].'</div>';	
elseif(!empty($_SESSION['wp_labsticc_export']['msg']))
	echo '<div id="message" class="updated">'.$_SESSION['wp_labsticc_export']['msg'].'</div>';
?>

<?php
	// Affichage de controle
	if(isset($_SESSION['data_display'])){
		echo '<form method="post" id="after_check">';
			foreach ($_SESSION['data_display'] as $category_name => $posts) {
			?>
				<h3 class="select_all_none" data-unchecked="false"><?=$category_name?></h3>
				<div>
				<?php
				foreach ($posts as $post_id => $post) {
					echo '<input type="checkbox" name="'.$post_id.'" checked="checked" id="post_id_'.$post_id.'" />';
					echo '<label for="post_id_'.$post_id.'">';
					echo $post['authors'].', '.$post['pubtitle'].'. '.$post['year'];
					echo '</label>';
					echo '<br />';
				}
				?>
				</div>
			<?php
		}
		?>
			<input type="hidden" name="order" value="<?=$_SESSION['data_order']?>" />
			<input type="hidden" name="gabarit" value="<?=$_SESSION['data_gabarit']?>" />
			<input type="submit" value="export odt" name="after_check" />
		</form>
		<?php
	} else {
?>

<div class="form">
	<form id="plugin_form" method="post">

	<fieldset>
		<h3>Date</h3>
		<label for="date_from">De</label>
		<input type="text" class="datepicker" name="date_from" id="date_from" />
	
		<label for="date_to">À</label>
		<input type="text" class="datepicker" name="date_to" id="date_to" />
		
		<div class="clear_float"></div>
					
		<div class="checkbox_div float">
			<h3>Types des publications</h3>
			<a href="#" class="select_all">Tout selectionner</a> <a href="#" class="select_none">Rien selectionner</a>
			<br /><br />
			<?php 
				// Display all categories
				foreach ($res_categories as $category) {
					echo '<input type="checkbox" data-name="'.$category->name.'" name= "pub[]" value="'.$category->term_id.'" id="category_'.$category->term_id.'" checked="checked" />';
					echo '<label for="category_'.$category->term_id.'">'.$category->name.'</label><br />';
				} 
			?>
		</div>

		<div class="float">
			<h3>Ordre</h3>
			<ul id="sortable">
				<?php
				foreach ($res_categories as $category) {
					echo '<li data-id="'.$category->term_id.'" class="ui-state-default sortable">'.$category->name.'</li>';
				} 
			?>
			</ul>		
		</div>

		<div class="clear_float"></div>
		
		<h3>Gabarit odt</h3>
		<p>Voir la page de configuration pour ajouter des gabarits</p>
		<select name="gabarit">
			<?php
			foreach ($gabarits_name as $name) {
				echo '<option value="'.$name.'">'.$name.'</option>';
			}
			?>
		</select>
		<label for="gabarit">Gabarit de sortie</label>


		<h3>Export</h3>
		<select name="format" id="format">
			<option value="odt" selected="selected">Odt</option>
			<option value="html_odt">Odt avec previsualisation</option>
		</select>
		<label for="format">Format d'export</label>
	</fieldset>
	
	<fieldset class="submit">
		<input type="hidden" name="order" value="" id="order" />
		<input type="submit" value="Exporter" name="export" />
	</fieldset>	
	</form>
</div>

<?php
}
?>
