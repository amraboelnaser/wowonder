<?php 
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.wowonder.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com   
// +------------------------------------------------------------------------+
// | WoWonder - The Ultimate Social Networking Platform
// | Copyright (c) 2016 WoWonder. All rights reserved.
// +------------------------------------------------------------------------+
require_once('assets/init.php');

if ($wo['loggedin'] == true) {
    $update_last_seen = Wo_LastSeen($wo['user']['user_id']);
} else if (!empty($_SERVER['HTTP_HOST'])) {
    $server_scheme = @$_SERVER["HTTPS"];
    $pageURL = ($server_scheme == "on") ? "https://" : "http://";
    $http_url = $pageURL . $_SERVER['HTTP_HOST'];
    $url = parse_url($wo['config']['site_url']);
    if (!empty($url)) {
        if ($url['scheme'] == 'http') {
            if ($http_url != 'http://' . $url['host']) { 
               header('Location: ' . $wo['config']['site_url']);
               exit();
            }
        } else {
            if ($http_url != 'https://' . $url['host']) { 
               header('Location: ' . $wo['config']['site_url']);
               exit();
            }
        }
    }
}
if (!empty($_GET['ref']) && $wo['loggedin'] == false && !isset($_COOKIE['src'])) {
    $get_ip = get_ip_address();
    if (!isset($_SESSION['ref']) && !empty($get_ip)) {
        $_GET['ref'] = Wo_Secure($_GET['ref']);
        $ref_user_id = Wo_UserIdFromUsername($_GET['ref']);
        $user_date = Wo_UserData($ref_user_id);
        if (!empty($user_date)) {
            if (ip_in_range($user_date['ip_address'], '/24') === false && $user_date['ip_address'] != $get_ip) {
                $_SESSION['ref'] = $user_date['username'];
            }
        }
    }
}
if (!isset($_COOKIE['src'])) {
    @setcookie('src', '1', time() + 31556926, '/');
}
$page = '';
if ($wo['loggedin'] == true && !isset($_GET['link1'])) {
    $page = 'home';
} elseif (isset($_GET['link1'])) {
    $page = $_GET['link1'];
}
if ((!isset($_GET['link1']) && $wo['loggedin'] == false) || (isset($_GET['link1']) && $wo['loggedin'] == false && $page == 'home')) {
    $page = 'welcome';
}
if ($wo['config']['maintenance_mode'] == 1) {
    if ($wo['loggedin'] == false) {
        if ($page == 'admincp') {
           $page = 'welcome';
        } else {
            $page = 'maintenance';
        }
    } else {
        if (Wo_IsAdmin() === false) {
            $page = 'maintenance';
        }
    } 
}

switch ($page) {
    case 'maintenance':
        include('sources/maintenance.php');
        break;
    case 'video-call':
        include('sources/video.php');
        break;
    case 'home':
        include('sources/home.php');
        break;
    case 'welcome':
        include('sources/welcome.php');
        break;
    case 'register':
        include('sources/register.php');
        break;
    case 'confirm-sms':
        include('sources/confirm_sms.php');
        break;   
    case 'forgot-password':
        include('sources/forgot_password.php');
        break;    
    case 'reset-password':
        include('sources/reset_password.php');
        break;    
    case 'start-up':
        include('sources/start_up.php');
        break;
    case 'activate':
        include('sources/activate.php');
        break;
    case 'search':
        include('sources/search.php');
        break;
    case 'timeline':
        include('sources/timeline.php');
        break;
    case 'pages':
        include('sources/my_pages.php');
        break;
    case 'go-pro':
        include('sources/go_pro.php');
        break;
    case 'page':
        include('sources/page.php');
        break;
    case 'groups':
        include('sources/my_groups.php');
        break;
    case 'group':
        include('sources/group.php');
        break;
    case 'create-group':
        include('sources/create_group.php');
        break;
    case 'group-setting':
        include('sources/group_setting.php');
        break;
    case 'create-page':
        include('sources/create_page.php');
        break;
    case 'setting':
        include('sources/setting.php');
        break;
    case 'page-setting':
        include('sources/page_setting.php');
        break;
    case 'messages':
        include('sources/messages.php');
        break;
    case 'logout':
        include('sources/logout.php');
        break;
    case '404':
        include('sources/404.php');
        break;
    case 'post':
        include('sources/story.php');
        break;
    case 'game':
        include('sources/game.php');
        break;
    case 'games':
        include('sources/games.php');
        break;
    case 'new-game':
        include('sources/new_games.php');
        break;
    case 'admincp':
        include('sources/admin.php');
        break;
    case 'saved-posts':
        include('sources/savedPosts.php');
        break;
    case 'hashtag':
        include('sources/hashtag.php');
        break;
    case 'terms':
        include('sources/term.php');
        break;
    case 'albums':
        include('sources/my_albums.php');
        break;
    case 'album':
        include('sources/album.php');
        break;
    case 'create-album':
        include('sources/create_album.php');
        break;
    case 'contact-us':
        include('sources/contact.php');
        break;
    case 'user-activation':
        include('sources/user_activation.php');
        break;
    case 'upgraded':
        include('sources/upgraded.php');
        break;
    case 'oops':
        include('sources/oops.php');
        break;
    case 'boosted-pages':
        include('sources/boosted_pages.php');
        break;
    case 'boosted-posts':
        include('sources/boosted_posts.php');
        break;
    case 'new-product':
        include('sources/new_product.php');
        break; 
    case 'edit-product':
        include('sources/edit_product.php');
        break;  
    case 'products':
        include('sources/products.php');
        break;   
    case 'my-products':
        include('sources/my_products.php');
        break;    
    case 'site-pages':
        include('sources/site_pages.php');
        break;
        
    /* API / Developers (will be available on future updates) 
    case 'oauth':
        include('sources/oauth.php');
        break;
    case 'graph':
        include('sources/graph.php');
        break;
    case 'graph-success':
        include('sources/graph_success.php');
        break;
    case 'app-setting':
        include('sources/app_setting.php');
        break;
    case 'developers':
        include('sources/developers.php');
        break;
    case 'create-app':
        include('sources/create_app.php');
        break;
    case 'app':
        include('sources/app_page.php');
        break;
    case 'apps':
        include('sources/apps.php');
        break;*/
    
}
if (empty($wo['content'])) {
    include('sources/404.php');
}

echo Wo_Loadpage('container');

mysqli_close($sqlConnect);
unset($wo);
?>