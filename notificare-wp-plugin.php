<?php
/*
Plugin Name: Notificare
Plugin URI: http://notifica.re/apps/wordpress
Description: Get notified on comments and approve or mark as spam with a simple push of a button from your phone
Version: 0.1.1
Author: silentjohnny
License: 

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met: Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

@include_once dirname( __FILE__ ) . '/classes/notificare/client.php';
@include_once dirname( __FILE__ ) . '/classes/notificare/plugin.php';

/**
 * Initialization, get singleton instance of the plugin
 */
function notificare_wp_plugin_init() {
	$plugin = NotificarePlugin::getInstance( dirname( __FILE__ ) . '/templates', basename( dirname( __FILE__ ) ) . '/languages');
}

/**
 * Call plugin activation
 */
function notificare_wp_plugin_activate() {
	NotificarePlugin::activate();
}

/**
 * Call plugin deactivation
 */
function notificare_wp_plugin_deactivate() {
	NotificarePlugin::deactivate();
}

// Add initialization and activation hooks
add_action( 'init', 'notificare_wp_plugin_init' );
register_activation_hook( __FILE__, 'notificare_wp_plugin_activate' );
register_deactivation_hook( __FILE__, 'notificare_wp_plugin_deactivate' );
