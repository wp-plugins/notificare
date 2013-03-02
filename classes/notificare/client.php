<?php
/*
Plugin Name: Notificare
Plugin URI: http://app.notifica.re/services/wordpress
Description: Get notified on comments and approve or mark as spam with a simple push of a button from your phone
Version: 0.4.6
Author: silentjohnny
License: 

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met: Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Notificare API Client class
 */
class NotificareClient {
	
	/**
	 * Base URL of the API
	 */
	const URI = 'https://apps.notifica.re';

	/**
	 * The service URI
	 */
	const SERVICE = '/hooks/wordpress';
	
	/**
	 * Applicationkey, generated in Notificare dashboard
	 */
	protected $applicationKey;
	
	/**
	 * User Token, generated in Notificare dashboard
	 */
	protected $userToken;
	
	/**
	 * Contructor
	 * @param {String} The application key
	 * @param {String} The user token
	 */
	public function __construct( $applicationKey, $userToken ) {
		$this->applicationKey = $applicationKey;
		$this->userToken = $userToken;
	}
	
	/**
	 * Send a notification to Notificare API
	 * @param {Object} The payload to send
	 * @return {Object|WP_Error} The response
	 */
	public function sendNotification( $payload ) {
		$url = self::URI . self::SERVICE . "/{$this->applicationKey}?token={$this->userToken}";
		return wp_remote_post( $url, array( 'body' => $payload ) );
	}
}
