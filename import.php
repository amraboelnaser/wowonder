<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.wowonder.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com   
// +------------------------------------------------------------------------+
// | WoWonder - The Ultimate Social Networking Platform
// | Copyright (c) 2015 WoWonder. All rights reserved.
// +------------------------------------------------------------------------+
error_reporting(0);
require_once( "assets/import/vendor/hybridauth/hybridauth/hybridauth/Hybrid/Auth.php" );
require_once( "assets/import/vendor/hybridauth/hybridauth/hybridauth/Hybrid/Endpoint.php" );
Hybrid_Endpoint::process();