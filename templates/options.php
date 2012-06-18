<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Notificare settings', NotificarePlugin::PLUGIN_NAME); ?></h2>

	<form method="post">
	<?php settings_fields(NotificarePlugin::PLUGIN_NAME); ?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="applicationkey"><?php _e('Application Key', NotificarePlugin::PLUGIN_NAME) ?></label></th>
				<td>
					<input name="applicationkey" type="text" id="applicationkey" value="<?php echo get_option(NotificarePlugin::PLUGIN_NAME . '_applicationkey'); ?>" class="regular-text" />
					<span class="description"><?php _e('Get an application key from Notificare Dashboard', NotificarePlugin::PLUGIN_NAME) ?></span></td>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="usertoken"><?php _e('User Token', NotificarePlugin::PLUGIN_NAME) ?></label></th>
				<td>
					<input name="usertoken" type="text" id="usertoken"  value="<?php echo get_option(NotificarePlugin::PLUGIN_NAME . '_usertoken'); ?>" class="regular-text" />
					<span class="description"><?php _e('Get a user token from Notificare Dashboard', NotificarePlugin::PLUGIN_NAME) ?></span>
				</td>
			</tr>
			<tr valign="top">
				<td>
					<label>
					<input name="permalink" type="checkbox" id="permalink" value="1" <?php if ( get_option(NotificarePlugin::PLUGIN_NAME . '_permalink') == '1' ) { ?> checked="checked" <?php } ?> />
					<?php _e('Use Permalink', NotificarePlugin::PLUGIN_NAME) ?>
					</label>
				</td>
			</tr>
		</table>
	<?php submit_button(); ?>
	</form>
</div>
