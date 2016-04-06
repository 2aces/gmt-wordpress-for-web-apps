<?php

/**
 * Login
 */


	// Login form shortcode
	function wpwebapp_login_form() {

		if ( is_user_logged_in() ) {
			$form = '<p>' . __( 'You\'re already logged in.', 'wpwebapp' ) . '</p>';
		} else {

			// Prevent this content from caching
			define('DONOTCACHEPAGE', TRUE);

			// Variables
			global $current_user;
			get_currentuserinfo();
			$options = wpwebapp_get_theme_options();
			$error = wpwebapp_get_session( 'wpwebapp_login_error', true );
			$credentials = wpwebapp_get_session( 'wpwebapp_login_credentials', true );

			$form =
				( empty( $error ) ? '' : '<div class="' . esc_attr( $options['alert_error_class'] ) . '">' . $error . '</div>' ) .

				'<form class="wpwebapp-form" id="wpwebapp_login" name="wpwebapp_login" action="" method="post">' .

					'<label class="wpwebapp-form-label" for="wpwebapp_login_username">' . stripslashes( $options['login_username_label'] ) . '</label>' .
					'<input type="text" class="wpwebapp-form-input" id="wpwebapp_login_username" name="wpwebapp_login_username"  value="' . esc_attr( $credentials ) . '" required>' .

					'<label class="wpwebapp-form-label" for="wpwebapp_login_password">' . stripslashes( $options['login_password_label'] ) . '</label>' .
					'<input type="password" class="wpwebapp-form-input wpwebapp-form-password" id="wpwebapp_login_password" name="wpwebapp_login_password"  value="" required>' .

					'<label class="wpwebapp-form-label-checkbox"><input type="checkbox" class="wpwebapp-form-checkbox" id="wpwebapp_login_rememberme" name="wpwebapp_login_rememberme" value=""> ' . stripslashes( $options['login_rememberme_label'] ) . '</label>' .

					'<button class="wpwebapp-form-button ' . esc_attr( $options['login_submit_class'] ) . '">' . $options['login_submit_text'] . '</button>' .

					wp_nonce_field( 'wpwebapp_login_nonce', 'wpwebapp_login_process', true, false ) .

				'</form>';

		}

		return $form;

	}
	add_shortcode( 'wpwa_login', 'wpwebapp_login_form' );


	// Process login
	function wpwebapp_process_login() {

		// Verify data came from form
		if ( !isset( $_POST['wpwebapp_login_process'] ) || !wp_verify_nonce( $_POST['wpwebapp_login_process'], 'wpwebapp_login_nonce' ) ) return;

		// Variables
		$options = wpwebapp_get_theme_options();
		$referer = esc_url_raw( wpwebapp_get_url() );
		$username = isset( $_POST['wpwebapp_login_username'] ) ? $_POST['wpwebapp_login_username'] : '';
		$rememberme = isset( $_POST['wpwebapp_login_rememberme'] ) ? true : false;

		// Check that username is supplied
		if ( empty( $_POST['wpwebapp_login_username'] ) ) {
			wpwebapp_set_session( 'wpwebapp_login_error', $options['login_username_field_empty_error'] );
			wp_safe_redirect( $referer, 302 );
			exit;
		}

		// Check that password is provided
		if ( empty( $_POST['wpwebapp_login_password'] ) ) {
			wpwebapp_set_session( 'wpwebapp_login_error', $options['login_password_field_empty_error'] );
			wpwebapp_set_session( 'wpwebapp_login_credentials', $username );
			wp_safe_redirect( $referer, 302 );
			exit;
		}


		// If login is an email, get username
		if ( is_email( $_POST['wpwebapp_login_username'] ) ) {
			$user = get_user_by( 'email', $_POST['wpwebapp_login_username'] );
			$user = get_userdata( $user->ID );
			$_POST['wpwebapp_login_username'] = $user->user_login;
		}

		// Authenticate User
		$credentials = array(
			'user_login' => $_POST['wpwebapp_login_username'],
			'user_password' => $_POST['wpwebapp_login_password'],
			'remember' => $rememberme,
		);
		$login = wp_signon( $credentials );

		// If errors
		if ( is_wp_error( $login ) ) {
			wpwebapp_set_session( 'wpwebapp_login_error', $options['login_failed_error'] );
			wpwebapp_set_session( 'wpwebapp_login_credentials', $username );
			wp_safe_redirect( $referer, 302 );
			exit;
		}

		// Run custom WordPress action
		do_action( 'wpwebapp_after_login', $_POST['wpwebapp_login_username'] );

		// Redirect after login
		$redirect = isset( $_GET['referrer'] ) && !empty( $_GET['referrer'] ) ? esc_url_raw( $_GET['referrer'] ) : $options['login_redirect'];
		wp_safe_redirect( $redirect, 302 );
		exit;

	}
	add_action( 'init', 'wpwebapp_process_login' );