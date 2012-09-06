<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Notificare settings', NotificarePlugin::PLUGIN_NAME); ?></h2>

	<form method="post">
	<?php settings_fields(NotificarePlugin::PLUGIN_NAME); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="applicationkey"><?php _e('Service Key', NotificarePlugin::PLUGIN_NAME) ?></label></th>
				<td>
					<input name="applicationkey" type="text" id="applicationkey" value="<?php echo get_option(NotificarePlugin::PLUGIN_NAME . '_applicationkey'); ?>" class="regular-text" />
					<span class="description"><?php printf( __('Copy your service key from <a href="%s">Notificare Dashboard</a>', NotificarePlugin::PLUGIN_NAME), NotificarePlugin::DASHBOARD_URL ); ?></span></td>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="usertoken"><?php _e('User Token', NotificarePlugin::PLUGIN_NAME) ?></label></th>
				<td>
					<input name="usertoken" type="text" id="usertoken"  value="<?php echo get_option(NotificarePlugin::PLUGIN_NAME . '_usertoken'); ?>" class="regular-text" />
					<span class="description"><?php printf( __('Copy your user token from <a href="%s">Notificare Dashboard</a>', NotificarePlugin::PLUGIN_NAME), NotificarePlugin::DASHBOARD_URL ); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<td colspan="2">
					<label>
					<input name="permalink" type="checkbox" id="permalink" value="1" <?php if ( get_option(NotificarePlugin::PLUGIN_NAME . '_permalink') == '1' ) { ?> checked="checked" <?php } ?> />
					<?php _e('Use Permalink', NotificarePlugin::PLUGIN_NAME) ?>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<td colspan="2">
					<label>
					<input name="notify_spam" type="checkbox" id="notify_spam" value="1" <?php if ( get_option(NotificarePlugin::PLUGIN_NAME . '_notify_spam') == '1' ) { ?> checked="checked" <?php } ?> />
					<?php _e('Notify me of all comments, even if marked as spam', NotificarePlugin::PLUGIN_NAME) ?>
					</label>
				</td>
			</tr>
			</table>
	<?php submit_button(); ?>
	</form>
</div>
