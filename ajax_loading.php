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
$came_from = false;
if ($page == 'timeline') {
    $came_from = true;
}
switch ($page) {
    case 'home':
        include('sources/home.php');
        break;
    case 'welcome':
        include('sources/welcome.php');
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
}
if (empty($wo['content'])) {
    include('sources/404.php');
}
if (empty($wo['title'])) {
    $data['title'] = $wo['config']['siteTitle'];
}
$data['url'] = '';
$actual_link = "http://$_SERVER[HTTP_HOST]";
$data['title'] = Wo_Secure($wo['title']);
$data['page'] = $wo['page'];
$data['welcome_page'] = 0;
$data['is_css_file'] = 0;
$data['css_file_header'] = '';
$data['welcome_url'] = Wo_SeoLink('index.php?link1=welcome');
if ($wo['page'] == 'welcome') {
    $data['welcome_page'] = 1;
}
if ($wo['page'] == 'timeline' && $wo['loggedin'] == true && $wo['config']['css_upload'] == 1 && !empty($wo['user_profile'])) {
    if (!empty($wo['user_profile']['css_file']) && file_exists($wo['user_profile']['css_file'])) {
      $data['is_css_file'] = 1;
      $data['css_file'] = '<link rel="stylesheet" class="styled-profile" href="' . Wo_GetMedia($wo['user_profile']['css_file']) . '">';
      $data['css_file_header'] = $wo['css_file_header'];
    } 
}

$data['is_footer'] = 0;
if (in_array($wo['page'], $wo['footer_pages'])) {
    $data['is_footer'] = 1;
}
$url = '';
if (!empty($_POST['url'])) {
    $url = $_POST['url'];
}
$data['redirect'] = 0;
if ($wo['redirect'] == 1) {
    $data['redirect'] = 1;
}
$data['url'] = Wo_SeoLink('index.php' . $url);
echo $wo['content'];
?>
<input type="hidden" id="json-data" value='<?php echo htmlspecialchars(json_encode($data));?>'>