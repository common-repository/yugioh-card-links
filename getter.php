<?php

	if( !isset($_GET['n']) )
		return;
	
	if( get_magic_quotes_gpc() )
		$_GET['n'] = stripslashes( $_GET['n'] );
		
	echo file_get_contents( "http://yugioh.tcgplayer.com/db/WP-CH.asp?CN=" . urlencode( $_GET['n'] ) );

?>