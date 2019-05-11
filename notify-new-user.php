<?php
/* 
 * Plugin Name: Notify New User
 * Description: Envía un correo electrónico a un usuario para notificarle que su cuenta ha sido creada
 * Author: Tomás El Fakih
 * Version: 1.0
 */

/**
 * Manda una notificacion por correo al usuario, indicandole que su cuenta ha sido creada
 * @param int $user_id ID del usuario recien registrado
 * 
 * @author Tomas El Fakih
 */
function nnu_notify_user( $user_id ) {
	$user = get_userdata( $user_id );
	$admin_email = get_bloginfo('admin_email');
	$to = $user->user_email;
	$headers = 'Content-Type: text/html';
	$headers .= "\nFrom: $admin_email";
	error_log( 'Estoy en la funcion de notificacion' );
	$message = get_option( 'nnu_email_template' );
	$message = str_replace( '###USERNAME###', $user->display_name, $message );
	$subject = get_option( 'nnu_email_subject' );
	error_log(wp_mail($to, $subject, $message, $headers));
}
add_action( 'user_register', 'nnu_notify_user' );

/////////////////////////////////////////////////
//  Implementacion de la API de Configuracion  //
////////////////////////////////////////////////

/**
 * Añade una pagina en el menu de WordPress para configurar el plugin.
 * 
 * @author Tomas El Fakih
 */
function nnu_add_menu_page() {
	add_menu_page(
		'Opciones de Notificación de Usuario',
		'Notificar Usuario', 
		'manage_options', 
		'notify-new-user', 
		'nnu_display_settings'
	);
}
add_action( 'admin_menu', 'nnu_add_menu_page' );

/**
 * Muestra la pagina de configuracion
 * 
 * @author Tomás El Fakih
 */
function nnu_display_settings() {
	?>
	<div class="wrap">
	<h1>Opciones de Notificacion de Usuario</h1>
	<form method="post" action="options.php">
		<?php

			//add_settings_section callback is displayed here. For every new section we need to call settings_fields.
			settings_fields("nnu_settings");

			// all the add_settings_field callbacks is displayed here
			do_settings_sections("notify-new-user");

			// Add the submit button to serialize the options
			submit_button(); 

		?>          
	</form>
</div>
<?php
}

/**
 * Registra las opciones en la Settings API
 * 
 * @author Tomas El Fakih
 */
function nnu_settings_api_init() {
	// Registramos la seccion unica de configuraciones
	add_settings_section(
		'nnu_settings', 
		'Configuracion de Notificacion de usuario', 
		'nnu_display_settings_section', 
		'notify-new-user'
	);
	
	// Registramos un campo unico de configuraciones
	add_settings_field(
		'nnu_email_subject', 
		'Asunto del correo a enviar', 
		'nnu_email_subject_field', 
		'notify-new-user', 
		'nnu_settings'
	);
	
	add_settings_field(
		'nnu_email_template', 
		'Plantilla de correo a enviar', 
		'nnu_email_template_field', 
		'notify-new-user', 
		'nnu_settings'
	);
	
	// Registramos la configuracion
	register_setting( 
		'nnu_settings', 
		'nnu_email_template', 
		array( 
			'type'        => 'string',
			'description' => 'Controla el contenido del correo que sera mostrado al usuario',
			'default'	  => nnu_default_template()
		)
	);
	
	register_setting(
		'nnu_settings', 
		'nnu_email_subject', 
		array(
			'type'		  => 'string',
			'description' => 'Asunto con el que sera enviado el correo',
			'default'	  => nnu_default_subject()
		)
	);
}
add_action( 'admin_init', 'nnu_settings_api_init' );

/**
 * Muestra el campo para editar la plantilla
 * 
 * @author Tomas El Fakih
 */
function nnu_email_template_field() {
	wp_editor( 
		get_option( 'nnu_email_template' ),
		'nnu_email_template'
	);
}

/**
 * Muestra el campo para editar el asunto del correo
 * 
 * @author Tomas El Fakih
 */
function nnu_email_subject_field() {
	?>
	<input type="text" name="nnu_email_subject" value="<?php echo get_option( 'nnu_email_subject' ) ?>" />
	<?php
}

/**
 * Muestra HTML de ayuda para la seccion de configuracion
 * 
 * @author Tomas El Fakih
 */
function nnu_display_settings_section() {
	echo "En esta seccion se puede editar el HTML "
	. " del correo a enviar a los usuarios luego de se inscriban exitosamente";
}

/**
 * Genera una plantilla sencilla de correo, para usar como valor por defecto
 * 
 * @author Tomas El Fakih
 */
function nnu_default_template() {
	$blog_name = get_bloginfo( 'blogname' );
	$blog_url  = get_home_url();
	$default = sprintf('Bienvenido, ###USERNAME###! Gracias por crearte una cuenta en %1$s. '
					. 'Te invitamos a que accedas con las credenciales que usaste en el siguiente enlace: %2$s', 
					$blog_name, 
					esc_url( $blog_url )
				);
	
	return $default;
}

/**
 * Genera un asunto por defecto para el correo electronico enviado a los usuarios
 * 
 * @author Tomas El Fakih
 */
function nnu_default_subject() {
	$blog_name = get_bloginfo( 'blogname' );
	return sprintf( 'Bienvenido a %s', $blog_name );
}

