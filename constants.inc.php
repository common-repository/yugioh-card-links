<?php

	define( 'YUGIOHCARDREF_VERSION', '1.0.0' );
	define( 'YUGIOHCARDREF_PLUGIN_NAME', 'yugiohcardref' );
	define( 'YUGIOHCARDREF_TABLE_NAME', YUGIOHCARDREF_PLUGIN_NAME . '_card_names' );
	define( 'YUGIOHCARDREF_DIRECTORY', dirname(__FILE__) . '/' );
	define( 'YUGIOHCARDREF_TINYMCE_PLUGIN_DIRECTORY', 'wp-includes/js/tinymce/plugins/yugioh_tinymce/' );
	define( 'YUGIOHCARDREF_OPTION_NAME', YUGIOHCARDREF_PLUGIN_NAME . '_version' );
	define( 'YUGIOHCARDREF_NONCE_NAME', YUGIOHCARDREF_PLUGIN_NAME . '_nonce' );
	define( 'YUGIOHCARDREF_BASE_URL', '<a class="yugiohcardref_rollover" href="http://store.tcgplayer.com/Products.aspx?GameName=Yugioh&Name=' );
	define( 'YUGIOHCARDREF_GETTER_URL', '/yugioh-card-links/getter.php?n=' );

?>