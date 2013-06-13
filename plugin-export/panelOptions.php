<?php
	$url = admin_url('admin.php?page=labsticcexport-options');
?>
<h1>Plugin Wordpress d'exports des publications Lab-Sticc</h1>
<h2>Configuration</h2>
<?php
	if(!empty($_SESSION['wp_labsticc_export']['error']))
		echo '<div id="message" class="error">'.$_SESSION['wp_labsticc_export']['error'].'</div>';	
	elseif(!empty($_SESSION['wp_labsticc_export']['msg']))
		echo '<div id="message" class="updated">'.$_SESSION['wp_labsticc_export']['msg'].'</div>';
?>
<div class="form">
	<h3>Ajouter un gabarit Odt</h3>
	<p>
		Pour adapter le rendu à vos besoins vous pouvez ajouter
		de nouveaux gabarits odt via ce formulaire.<br />
		Vous pouvez vous inspirer du gabarit par défaut pour 
		la syntaxe à suivre.<br />
		<?php echo '<a href="'.ABSOLUTE_URL .'gabarits/default.odt">Gabarit par défaut</a>'; ?>
	</p>
	<form id="gabarit" method="post" enctype="multipart/form-data">
		<input type="text" name="name" id="gabarit_name" /> <label for="gabarit_name">Nom du gabarit</label><br />
		<input type="file" name="gabarit" id="gabarit_input" /> <label for="gabarit_input">Gabarit Odt</label><br />
		<input type="submit" name="gabarit_send" value="Envoyer" />
	</form>

	<h3>Supprimer un gabarit</h3>
	<ul>
	<?php 
	foreach ($gabarits_name as $gabarit_name) {
		echo '<li><a href="'.$url.'&gabarit_delete='.$gabarit_name.'" class="confirm">'.$gabarit_name.'</a></li>';
	}
	?>
	</ul>
</div>


