<?php
/*
Plugin Name: Notificare
Plugin URI: http://notifica.re/apps/wordpress
Description: Get notified on comments and approve or mark as spam with a simple push of a button from your phone
Version: 0.1.3
Author: silentjohnny
License: 

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met: Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
/**
 * A Singleton class for the Notificare Wordpress plugin
 */
class NotificarePlugin {
	
	/**
	 * Prefix for table
	 */
	const TABLE_PREFIX = 'notificare_';
	
	/**
	 * Name of table for tokens
	 */
	const TOKEN_TABLE = 'token';
	
	/**
	 * Name of this plugin, to be used as identifier or slug
	 */
	const PLUGIN_NAME = 'notificare_wp_plugin';
	
	/**
	 * Max length of content to send in notification
	 */
	const MAX_CONTENT_LENGTH = 140;
	
	/**
	 * The Singleton instance
	 * @var {NotificarePlugin}
	 */
	static private $instance = null;
	
	/**
	 * @var {DBHandle} Our DB handle
	 */
	protected $dbh;
	
	/**
	 * @var {String} Table name
	 */
	protected $tableName;

	/**
	 * @var {String} Where to find templates
	 */
	protected $templateDir;
	
	/**
	 * @var {String} Where to find i18n files
	 */
	protected $localeDir;
	
	/**
	 * Constructor
	 * @param {String} Where to find templates
	 * @param {String} Where to find i18n files
	 */
	public function __construct( $templateDir, $localeDir ) {
		$this->dbh = $GLOBALS['wpdb'];
		$this->tableName = $this->dbh->prefix . self::TABLE_PREFIX . self::TOKEN_TABLE;
		$this->templateDir = $templateDir;
		$this->localeDir = $localeDir;
		// i18n
		load_plugin_textdomain( self::PLUGIN_NAME, false, $this->localeDir );
		
		// Hook up to the admin menu
		add_action( 'admin_menu', array( $this, 'addOptionsPage' ) );
		add_action( 'update_option_' . self::PLUGIN_NAME . '_permalink', array( $this, 'flushRewriteRules' ) );
	    
		// Hook up to the post_comment 
		add_action( 'comment_post', array( $this, 'handleComment' ) );
		
		// Hook up to requests coming in
		add_filter( 'query_vars', array( $this, 'addQueryVars' ) );
		add_action( 'template_redirect', array( $this, 'handleCallback' ) );
	}
	
	/**
	 * Create or retrieve the Singleton instance
	 * @param {String} Where to find templates
	 * @param {String} Where to find i18n files
	 * @return {NotificarePlugin}
	 */
	static public function getInstance( $templateDir, $localeDir ) {
		if ( !isset( self::$instance ) ) {
			self::$instance = new NotificarePlugin( $templateDir, $localeDir );
		}
		return self::$instance;
	}

	/*
	 * Handling new comments
	 */

	/**
	 * Truncate content to MAX_CONTENT_LENGTH so it won't spam the Notificare API
	 * @param {String} Original content
	 * @return {String} Truncated content
	 */
	protected function truncateContent( $content ) {
		if ( strlen( $content ) > self::MAX_CONTENT_LENGTH ) {
			$content = substr( $content, 0, self::MAX_CONTENT_LENGTH - 3 ) . '...'; 
		}
		return $content;
	}
	
	/**
	 * Generate new tokens or use existing ones
	 * @param {int} The comment ID
	 * @return {Object} The tokens
	 */
	protected function generateTokens($comment_id) {

	    // Do we have tokens for this comment ID already?
	    // If yes, just retrieve them back
	    $tokens = $this->dbh->get_row( $this->dbh->prepare( "SELECT comment_id, approve_token, trash_token, spam_token, delete_token FROM {$this->tableName} WHERE comment_id=%d", $comment_id ), ARRAY_A );

	    if ( $tokens ) {
			return $tokens;
		} else {
			// Generate unique tokens
		    $data = array(
		        'comment_id'    => $comment_id,
		        'trash_token' => sha1(uniqid(rand(), true)),
		        'approve_token' => sha1(uniqid(rand(), true)),
		        'spam_token'    => sha1(uniqid(rand(), true)),
				'delete_token'  => sha1(uniqid(rand(), true))
		    );

		    // try inserting the tokens into database
		    // if the insert fails, return FALSE, else return the token array
		    if ( $this->dbh->insert( $this->tableName, $data ) ) {
		        return $data;
		    } else {
		    	return false;
			}
		}
	}
	
	/**
	 * Build a callback URL
	 * @param {String} The action
	 * @param {String} The comment ID
	 * @param {String} The token
	 * @return {String} The URL
	 */
	protected function buildCallbackURL( $action, $comment_id, $token ) {
		if ( get_option( self::PLUGIN_NAME . '_permalink' ) == '1') {
			return site_url( sprintf( '/notificare/%s?comment_id=%s&token=%s', $action, $comment_id, $token ) );
		} else {
			return site_url( sprintf( '/index.php?notificare_action=%s&comment_id=%s&token=%s', $action, $comment_id, $token ) );
		}
	}
	
	/**
	 * Hook function for comment post
	 *
	 * Send a notification when somebody posts a comment.
	 * @param {int} The ID of the comment
	 */
	public function handleComment( $comment_id ) {
		$tokens = $this->generateTokens( $comment_id );
		$comment = get_comment( $comment_id );
		$post = get_post( $comment->comment_post_ID );
		$payload = array(
			'hook' => 'comment_post',
			'post_title' => $post->post_title,
			'comment_content' => $this->truncateContent( $comment->comment_content ),
			'comment_author' => $comment->comment_author,
			'post_url' => get_permalink( $comment->comment_post_ID ),
			'approve_url' => $this->buildCallbackURL( 'approve', $comment_id, $tokens['approve_token'] ),
			'spam_url' => $this->buildCallbackURL( 'spam', $comment_id, $tokens['spam_token'] ),
			'delete_url' => $this->buildCallbackURL( 'delete', $comment_id, $tokens['delete_token'] ),
			'trash_url' => $this->buildCallbackURL( 'trash', $comment_id, $tokens['trash_token'] )
		);
		$client = new NotificareClient( get_option( self::PLUGIN_NAME . '_applicationkey' ), get_option( self::PLUGIN_NAME . '_usertoken' ) );
		$client->sendNotification( $payload );
	}
	
	/*
	 * Handling the request
	 */
	
	/**
	 * Flush the rewrite rules
	 */
	public function flushRewriteRules() {
		if ( get_option( self::PLUGIN_NAME . '_permalink' ) == '1') {
			add_filter( 'rewrite_rules_array', array( 'NotificarePlugin', 'addRewriteRules' ) );
		}
		$wp_rewrite = $GLOBALS['wp_rewrite'];
		$wp_rewrite->flush_rules();
	}
	
	/**
	 * Add our query parameters
	 * WordPress Filter
	 */
	public function addQueryVars( $query_vars ) {
		$my_query_vars = array(
			'notificare_action',
			'comment_id',
			'token'
		);
		return array_merge( $my_query_vars, $query_vars );
	}

	/**
	 * Send JSON output
	 * @param {String}
	 */
	protected function sendJSON( $data ) {
		if ( isset( $data['code'] ) ) {
			header( "HTTP/1.0 {$data['code']}" );
		}
		$charset = get_option( 'blog_charset' );
		header( "Content-Type: application/json; charset={$charset}" );
		if ( function_exists( 'json_encode' ) ) {
	      echo json_encode( $data );
	    } else {
	      $json = new Services_JSON();
	      echo $json->encode( $data );
	    }
	}
	
	/**
	 * Do the actual moderation
	 * @param {String} The action (approve or spam)
	 * @param {int} The comment ID
	 * @param {String} The token
	 */
	protected function moderate( $action, $comment_id, $token ) {
	    // First get the stored token from database (if any)
 		$sth = $this->dbh->prepare( "SELECT `{$action}_token` FROM `{$this->tableName}` WHERE comment_id=%d", $comment_id );
	    $stored_token = $this->dbh->get_var( $sth );

	    // then compare it with the sent token. If mismatch, send error.
	    if ( strcmp( $token, $stored_token ) ) { 
			return array(
	        	'message' => __( 'Validation failed', self::PLUGIN_NAME),
				'code' => '400 Bad Request'
			);
	    }

	    // does the comment exist?
	    if ( !$comment = get_comment( $comment_id ) )
	    {
			return array(
	        	'message' => __( 'Comment not found', self::PLUGIN_NAME),
				'code' => '404 Not Found'
			);
	    }

	    switch ( $action )
	    {
	        case 'delete':
	            wp_delete_comment( $comment_id, true );
	            return array( 
					'message' => __( 'Comment deleted', self::PLUGIN_NAME )
				);
	            break;
	        case 'approve':
	            if ( 1 == $comment->comment_approved )
	            {
					return array(
	                	'message' => __( 'Comment already approved', self::PLUGIN_NAME )
					);
	            }
	            else
	            {
	                wp_set_comment_status( $comment_id, $action );
	                return array( 
						'message' => __( 'Comment approved', self::PLUGIN_NAME )
					);
	            }
	            break;
	        case 'spam':
	            wp_set_comment_status( $comment_id, $action );
	            return array(
					'message' => __( 'Comment marked as spam', self::PLUGIN_NAME )
				);
	            break;
	        case 'trash':
	            wp_trash_comment( $comment_id );
	            return array(
		 			'message' => __( 'Comment moved to trash', self::PLUGIN_NAME )
				);
	            break;
	        default:
	            return array(
	            	'message' => __( 'Unknown action', self::PLUGIN_NAME ),
					'code' => '400 Bad Request'
	            );
	    }
	}
	
	/**
	 * Handle callback POST from Notificare
	 */
	public function handleCallback() {
		$action = get_query_var( 'notificare_action' );
		if ( $action ) {
			$token = get_query_var( 'token' );
			$comment_id = get_query_var( 'comment_id' );
		
			if ( ( 'approve' == $action || 'spam' == $action ) && $token && $comment_id ) {
				$result = $this->moderate( $action, $comment_id, $token );
				$this->sendJSON( $result );
			} else {
				$this->sendJSON( 
					array( 
						'code' => '400 Bad Request',
						'message' => __( 'Unknown action', self::PLUGIN_NAME )
					) 
				);
			}
			exit();
		}
	}
		

	/*
	 * Admin stuff
	 */

	/**
	 * Add a settings menu to the admin dashboard
	 */
	public function addOptionsPage() {
		add_options_page( 'Notificare Plugin Options', 'Notificare', 'manage_options', self::PLUGIN_NAME, array( $this, 'renderOptionsPage' ) );
	}

	/**
	 * Render the page where we can enter settings
	 */
	public function renderOptionsPage() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		if ( isset( $_REQUEST['applicationkey'] ) ) {
			update_option( self::PLUGIN_NAME . '_applicationkey', $_REQUEST['applicationkey'] );
		}
		if ( isset( $_REQUEST['usertoken'] ) ) {
			update_option( self::PLUGIN_NAME . '_usertoken', $_REQUEST['usertoken'] );
		}
		if ( isset( $_REQUEST['permalink'] ) ) {
			update_option( self::PLUGIN_NAME . '_permalink', $_REQUEST['permalink'] );
		} else {
			update_option( self::PLUGIN_NAME . '_permalink', '0' );
		}
		include( $this->templateDir . '/options.php' );
	}
	
	/*
	 * Setup stuff
	 */
	
	/**
	 * Add rewrite rules
	 * @param {Array} The existing rules
	 * @return {Array} Rules added
	 */
	static public function addRewriteRules($rules) {
		$my_rules = array(
			"notificare/(.+)\$" => 'index.php?notificare_action=$matches[1]'
		);
		return array_merge( $my_rules, $rules );
	}
	
	/**
	 * Activate the plugin, add tables and options
	 */
	static public function activate() {
		add_option( self::PLUGIN_NAME . '_applicationkey' );
		add_option( self::PLUGIN_NAME . '_usertoken' );
		add_option( self::PLUGIN_NAME . '_permalink', '0' );

		$dbh = $GLOBALS['wpdb'];
		$tableName = $dbh->prefix . self::TABLE_PREFIX . self::TOKEN_TABLE;
		if ( !$dbh->query( "SHOW TABLES FROM `{$dbh->dbname}` LIKE '{$tableName}'" ) )
	    {
	        $dbh->query( "CREATE TABLE `{$tableName}` (
	          `id` int(11) NOT NULL AUTO_INCREMENT,
	          `comment_id` int(11) NOT NULL,
	          `approve_token` char(40) COLLATE utf8_unicode_ci NOT NULL,
	          `trash_token` char(40) COLLATE utf8_unicode_ci NOT NULL,
	          `spam_token` char(40) COLLATE utf8_unicode_ci NOT NULL,
	          `delete_token` char(40) COLLATE utf8_unicode_ci NOT NULL,
	          PRIMARY KEY (`id`)
	        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci" );
	    }
	}
	
	/**
	 * Deactivate the plugin, flush rewrite rules
	 */
	static public function deactivate() {
		// Remove the rewrite rule on deactivation
		$wp_rewrite = $GLOBALS['wp_rewrite'];
		$wp_rewrite->flush_rules();
	}

	/**
	 * Uninstall the plugin, remove tables and options
	 */
	static public function uninstall() {
		delete_option( self::PLUGIN_NAME . '_applicationkey' );
		delete_option( self::PLUGIN_NAME . '_usertoken' );
		delete_option( self::PLUGIN_NAME . '_permalink' );
		
		// Remove the rewrite rule on deactivation
		$wp_rewrite = $GLOBALS['wp_rewrite'];
		$wp_rewrite->flush_rules();
		
		$dbh = $GLOBALS['wpdb'];
		$tableName = $dbh->prefix . self::TABLE_PREFIX . self::TOKEN_TABLE;
		$dbh->query( "DROP TABLE IF EXISTS `{$tableName}`" );	    
	}
	
}