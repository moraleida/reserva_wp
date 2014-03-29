<?php

add_action( 'admin_menu', 'reserva_wp_settings' );
add_action( 'admin_enqueue_scripts', 'reserva_wp_admin_scripts' );
add_action( 'wp_ajax_reserva_wp_edit_object', 'reserva_wp_edit_object' );

/**
* Settings scripts and styles
* TODO: enqueue sem symlinks, mover tag script pro arquivo proprio
*/
function reserva_wp_admin_scripts() {

	wp_register_script( 'rwp_admin', plugins_url( '/js/admin.js', __FILE__ ), array('jquery') );
	wp_register_script( 'rwp_validation', plugins_url( '/js/jquery.validate.min.js', __FILE__ ), array('jquery') );

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'rwp_validation' );
	wp_enqueue_script( 'rwp_admin' );

	wp_localize_script( 'jquery', 'reserva_wp' ,array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

/**
* Settings option screens
*/
function reserva_wp_settings() { 
	add_menu_page( 'Reserva WP', 'Reserva WP', 'edit_posts', 'reserva_wp', 'reserva_wp_settings_page' );
	add_submenu_page( 'reserva_wp', 'Reserva WP Objects', 'Objects', 'edit_posts', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	add_submenu_page( 'reserva_wp', 'Reserva WP Object Categories', 'Categories', 'edit_posts', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	add_submenu_page( 'reserva_wp', 'Reserva WP Object Data', 'Meta Data', 'edit_posts', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	add_submenu_page( 'reserva_wp', 'Reserva WP Object Status', 'Status', 'edit_posts', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	add_submenu_page( 'reserva_wp', 'Reserva WP Transactions', 'Transactions', 'edit_posts', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	add_submenu_page( 'reserva_wp', 'Reserva WP Results', 'Results', 'edit_posts', 'reserva_wp_settings', 'reserva_wp_settings_page' );
	add_submenu_page( 'reserva_wp', 'Reserva WP Settings', 'Settings', 'edit_posts', 'reserva_wp_settings', 'reserva_wp_settings_page' );
}

/**
* Create / edit objects function
* TODO: edit objects
*/
function reserva_wp_edit_object($post) {

	// delete_option( 'reserva_wp_objects' );
	$types = get_option( 'reserva_wp_objects' );

	// If deleting
	if($_POST['ajax']) {
		
		unset( $types[$_POST['name']] );

		$bool = update_option( 'reserva_wp_objects', $types );

        header( "Content-Type: application/json" );
		echo json_encode($bool);
        exit;

	} elseif ( 

		// server side validation
		!empty( $post['rwp_name'] ) && isset( $post['rwp_name'] ) &&
		!empty( $post['rwp_objlabel'] ) && isset( $post['rwp_objlabel'] ) &&
		!empty( $post['rwp_singlabel'] ) && isset( $post['rwp_singlabel'] ) &&
		!empty( $post['rwp_description'] ) && isset( $post['rwp_description'] )
		) {

		unset($post['rwp_action']);
		unset($post['rwp_nonce_']);
		unset($post['_wp_http_referer']);

		

		if($post['rwp_action'] == 'create') {
			$types[$post['rwp_name']] = $post;
			update_option( 'reserva_wp_objects', $types );
		} else {
			// in case the name has changed we just wipe it out and replace with the new info
			if(!empty( $post['rwp_orig_name'] ) && isset( $post['rwp_orig_name'] ))
				unset( $types[$post['rwp_orig_name']] );

			$types[$post['rwp_name']] = $post;
			update_option( 'reserva_wp_objects', $types );
		}

		

		return true;

		} else {
			return new WP_Error('incomplete', __("Existem campos incompletos no formulário"));
		}
	
}

/**
* Create / edit objects page
* TODO: client side validation
*/
function reserva_wp_settings_page() {

	if( !empty($_POST) && check_admin_referer( 'rwp_create_object', 'rwp_nonce_' ) )
		reserva_wp_edit_object($_POST);
	?>
	<h1><?php _e('Reserva WP', 'reservawp'); ?></h1>
	<h3><?php _e('Criar um novo tipo de objeto', 'reservawp'); ?></h3>
	<style type="text/css">
		.rwp_form label { display: block; }
		.rwp_form input { margin-left: 10px; }
	</style>

	<form action="" method="post" class="rwp_form">
		<fieldset class="main">
			<?php _e('Defina abaixo as características principais do objeto', 'reservawp'); ?>
			<label for="rwp_name"><?php _e('Nome do Objeto', 'reservawp'); ?><input type="text" name="rwp_name" id="rwp_name" /></label>
			<label for="rwp_objlabel"><?php _e('Título do Objeto (plural)', 'reservawp'); ?><input type="text" name="rwp_objlabel" id="rwp_objlabel" /></label>
			<label for="rwp_singlabel"><?php _e('Título do Objeto (singular)', 'reservawp'); ?><input type="text" name="rwp_singlabel" id="rwp_singlabel" /></label>
			<label for="rwp_description"><?php _e('Descrição do objeto', 'reservawp'); ?><textarea name="rwp_description" id="rwp_description"></textarea></label>
			<input type="hidden" id="rwp_orig_name" name="rwp_orig_name" value="" />
		</fieldset>
			
		<input type="hidden" id="rwp_action" name="rwp_action" value="create" />
		<?php wp_nonce_field( 'rwp_create_object', 'rwp_nonce_' ); ?>
		<input type="submit" id="rwp_submit" class="button-primary" value="<?php _e('Criar objeto', 'reservawp'); ?>">
		<input style="display: none;" id="rwp_edit_cancel" type="button" class="button-primary" value="<?php _e('Cancelar edição', 'reservawp'); ?>" />
	</form>
<?php

	reserva_wp_list_objects();
}

function reserva_wp_list_objects() {
	
	$types = get_option( 'reserva_wp_objects' ); 
	?>

	<hr>
	<h3><?php _e('Editar objetos', 'reservawp'); ?></h3>
	<table>
		<tr>
			<th><?php _e('Nome', 'reservawp'); ?></th>
			<th><?php _e('Título (plural)', 'reservawp'); ?></th>
			<th><?php _e('Título (singular)', 'reservawp'); ?></th>
			<th><?php _e('Descrição', 'reservawp'); ?></th>
			<th></th>
			<th></th>
		</tr>
	
<?php foreach ($types as $t) : $tp = get_post_type_object( $t['rwp_name'] ); ?>

	<tr class="rwp_object <?php echo $tp->name; ?>">
		<td class="rwp_name"><?php echo $tp->name; ?></td>
		<td class="rwp_objlabel"><?php echo $tp->label; ?></td>
		<td class="rwp_singlabel"><?php echo $tp->singular_label; ?></td>
		<td class="rwp_description"><?php echo $tp->description; ?></td>
		<td><input rel="<?php echo $tp->name; ?>" type="button" class="button-primary rwp_edit_object" value="<?php _e('Editar', 'reservawp'); ?>"></td>
		<td><input rel="<?php echo $tp->name; ?>" type="button" class="button-primary rwp_delete_object" value="<?php _e('Deletar', 'reservawp'); ?>"></td>
	</tr>	

<?php endforeach; ?>
	</table>
<?php
}
?>
