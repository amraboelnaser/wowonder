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
use Aws\S3\S3Client;
$f = '';
$s = '';
if (isset($_GET['f'])) {
    $f = Wo_Secure($_GET['f'], 0);
}
if (isset($_GET['s'])) {
    $s = Wo_Secure($_GET['s'], 0);
}
$hash_id = '';
if (!empty($_POST['hash_id'])) {
    $hash_id = $_POST['hash_id'];
} else if (!empty($_GET['hash_id'])) {
    $hash_id = $_GET['hash_id'];
} else if (!empty($_GET['hash'])) {
    $hash_id = $_GET['hash'];
} else if (!empty($_POST['hash'])) {
    $hash_id = $_POST['hash'];
}
$data = array();
if ($f == 'session_status') {
    if ($wo['loggedin'] == false) {
        $data = array(
            'status' => 200
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_welcome_users') {
    $html = '';
    foreach (Wo_WelcomeUsers() as $wo['user']) {
        $html .= Wo_LoadPage('welcome/user-list');
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'load_posts') {
    $load = Wo_LoadPage('home/load-posts');
    echo $load;
    exit();
}
if ($f == 'load_profile_posts') {
    if (!empty($_GET['user_id'])) {
        $wo['user_profile'] = Wo_UserData($_GET['user_id']);
        $load               = Wo_LoadPage('timeline/load-posts');
        echo $load;
        exit();
    }
}
if ($f == 'confirm_user') {
    if (isset($_POST['confirm_code']) && isset($_POST['user_id'])) {
        $confirm_code = $_POST['confirm_code'];
        $user_id      = $_POST['user_id'];
        if (empty($_POST['confirm_code'])) {
            $errors = $error_icon . $wo['lang']['please_check_details'];
        } else if (empty($_POST['user_id'])) {
            $errors = $error_icon . $wo['lang']['error_while_activating'];
        }
        $confirm_code = Wo_ConfirmUser($user_id, $confirm_code);
        if ($confirm_code === false) {
            $errors = $error_icon . $wo['lang']['wrong_confirmation_code'];
        }
        if (empty($errors) && $confirm_code === true) {
            $session = Wo_CreateLoginSession($user_id);
            $data                = array(
                'status' => 200
            );
            $_SESSION['user_id'] = $session;
            setcookie(
              "user_id",
              $session,
              time() + (10 * 365 * 24 * 60 * 60)
            );
            if (!empty($_POST['last_url'])) {
                $data['location'] = $_POST['last_url'];
            } else {
                $data['location'] = $wo['config']['site_url'];
            }
        }
    }
    header("Content-type: application/json");
    if (!empty($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'resned_code') {
    if (isset($_POST['user_id'])) {
        $user = Wo_UserData($_POST['user_id']);
        if (empty($user) || empty($_POST['user_id']) || empty($_POST['phone_number'])) {
            $errors = $wo['lang']['failed_to_send_code'];
        }
        if (!preg_match('/^\+?\d+$/', $_POST['phone_number'])) {
            $errors = $wo['lang']['worng_phone_number'];
        }
        if (Wo_PhoneExists($_POST['phone_number']) === true) {
            if ($user['phone_number'] != $_POST['phone_number']) {
                $errors = $wo['lang']['phone_already_used'];
            }
        }
        if (empty($errors)) {
            $random_activation = Wo_Secure(rand(11111, 99999));
            $message           = "Your confirmation code is: {$random_activation}";
            $user_id           = $_POST['user_id'];
            $query             = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `sms_code` = '{$random_activation}' WHERE `user_id` = {$user_id}");
            if ($query) {
                if (Wo_SendSMSMessage($_POST['phone_number'], $message) === true) {
                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['sms_has_been_sent']
                    );
                } else {
                    $errors = $wo['lang']['error_while_sending_sms'];
                }
            }
        }
    }
    header("Content-type: application/json");
    if (!empty($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'resned_code_ac') {
    if (isset($_SESSION['code_id'])) {
        $user = Wo_UserData($_SESSION['code_id']);
        if (empty($user) || empty($_SESSION['code_id']) || empty($user['phone_number'])) {
            $errors[] = $error_icon . $wo['lang']['failed_to_send_code'];
        }
        if (empty($errors)) {
            $random_activation = Wo_Secure(rand(11111, 99999));
            $message           = "Your confirmation code is: {$random_activation}";
            $user_id           = $user['user_id'];
            $query             = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `sms_code` = '{$random_activation}' WHERE `user_id` = {$user_id}");
            if ($query) {
                if (Wo_SendSMSMessage($user['phone_number'], $message) === true) {
                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['sms_has_been_sent']
                    );
                } else {
                    $errors[] = $error_icon . $wo['lang']['error_while_sending_sms'];
                }
            }
        }
    }
    header("Content-type: application/json");
    if (!empty($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'resned_ac_email') {
    if (isset($_SESSION['code_id'])) {
        $email   = 0;
        $phone   = 0;
        $user_id = $_SESSION['code_id'];
        $user    = Wo_UserData($_SESSION['code_id']);
        if (empty($user) || empty($_SESSION['code_id']) || (empty($_POST['phone_number']) && empty($_POST['email']))) {
            $errors[] = $error_icon . $wo['lang']['failed_to_send_code_fill'];
        }
        if (!empty($_POST['email'])) {
            if (Wo_EmailExists($_POST['email']) === true && $user['email'] != $_POST['email']) {
                $errors[] = $error_icon . $wo['lang']['email_exists'];
            }
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = $error_icon . $wo['lang']['email_invalid_characters'];
            }
            if (empty($errors)) {
                $email = 1;
                $phone = 0;
            }
        } else if (!empty($_POST['phone_number'])) {
            if (!preg_match('/^\+?\d+$/', $_POST['phone_number'])) {
                $errors[] = $error_icon . $wo['lang']['worng_phone_number'];
            }
            if (Wo_PhoneExists($_POST['phone_number']) === true) {
                if ($user['phone_number'] != $_POST['phone_number']) {
                    $errors[] = $error_icon . $wo['lang']['phone_already_used'];
                }
            }
            if (empty($errors)) {
                $email = 0;
                $phone = 1;
            }
        }
        if (empty($errors)) {
            if ($email == 1 && $phone == 0) {
                $wo['user']             = $_POST;
                $wo['user']['username'] = $user['username'];
                $body                   = Wo_LoadPage('emails/activate');
                $send_message_data      = array(
                    'from_email' => $wo['config']['siteEmail'],
                    'from_name' => $wo['config']['siteName'],
                    'to_email' => $_POST['email'],
                    'to_name' => $user['username'],
                    'subject' => $wo['lang']['account_activation'],
                    'charSet' => 'utf-8',
                    'message_body' => $body,
                    'is_html' => true
                );
                $query                  = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `email` = '" . Wo_Secure($_POST['email']) . "' WHERE `user_id` = {$user_id}");
                $send                   = Wo_SendMessage($send_message_data);
                if ($send) {
                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['email_sent_successfully']
                    );
                }
            } else if ($email == 0 && $phone == 1) {
                $random_activation = Wo_Secure(rand(11111, 99999));
                $message           = "Your confirmation code is: {$random_activation}";
                $user_id           = $_SESSION['code_id'];
                $phone_num         = Wo_Secure($_POST['phone_number']);
                $query             = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `phone_number` = '{$phone_num}' WHERE `user_id` = {$user_id}");
                $query             = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `sms_code` = '{$random_activation}' WHERE `user_id` = {$user_id}");
                if ($query) {
                    if (Wo_SendSMSMessage($_POST['phone_number'], $message) === true) {
                        $data = array(
                            'status' => 600,
                            'message' => $success_icon . $wo['lang']['sms_has_been_sent']
                        );
                    } else {
                        $errors[] = $error_icon . $wo['lang']['error_while_sending_sms'];
                    }
                }
            }
        }
    }
    header("Content-type: application/json");
    if (!empty($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'contact_us') {
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || empty($_POST['message'])) {
        $errors[] = $error_icon . $wo['lang']['please_check_details'];
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = $error_icon . $wo['lang']['email_invalid_characters'];
    }
    if (empty($errors)) {
        $first_name        = Wo_Secure($_POST['first_name']);
        $last_name         = Wo_Secure($_POST['last_name']);
        $email             = Wo_Secure($_POST['email']);
        $message           = Wo_Secure($_POST['message']);
        $name              = $first_name . ' ' . $last_name;
        $send_message_data = array(
            'from_email' => $wo['config']['siteEmail'],
            'from_name' => $name,
            'reply-to' => $email,
            'to_email' => $wo['config']['siteEmail'],
            'to_name' => $wo['config']['siteName'],
            'subject' => 'Contact us new message',
            'charSet' => 'utf-8',
            'message_body' => $message,
            'is_html' => false
        );
        $send              = Wo_SendMessage($send_message_data);
        if ($send) {
            $data = array(
                'status' => 200,
                'message' => $success_icon . $wo['lang']['email_sent']
            );
        } else {
            $errors[] = $error_icon . $wo['lang']['processing_error'];
        }
    }
    header("Content-type: application/json");
    if (!empty($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'login') {
    $data_ = array();
    $phone = 0;
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = Wo_Secure($_POST['username']);
        $password = Wo_Secure($_POST['password']);
        $result   = Wo_Login($username, $password);
        if ($result === false) {
            $errors[] = $error_icon . $wo['lang']['incorrect_username_or_password_label'];
        } else if (Wo_UserInactive($_POST['username']) === true) {
            $errors[] = $error_icon . $wo['lang']['account_disbaled_contanct_admin_label'];
        } else if (Wo_UserActive($_POST['username']) === false) {
            $_SESSION['code_id'] = Wo_UserIdForLogin($username);
            $data_               = array(
                'status' => 600,
                'location' => Wo_SeoLink('index.php?link1=user-activation')
            );
            $phone               = 1;
        }
        if (empty($errors) && $phone == 0) {
            $userid = Wo_UserIdForLogin($username);
            $ip = Wo_Secure(get_ip_address());
            $update = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `ip_address` = '{$ip}' WHERE `user_id` = '{$userid}'");
            $session = Wo_CreateLoginSession(Wo_UserIdForLogin($username));
            $_SESSION['user_id'] = $session;
            setcookie(
                "user_id",
                $session,
                time() + (10 * 365 * 24 * 60 * 60)
            );
            $data = array(
                'status' => 200
            );
            if (!empty($_POST['last_url'])) {
                $data['location'] = $_POST['last_url'];
            } else {
                $data['location'] = $wo['config']['site_url'];
            }
        }
    }
    header("Content-type: application/json");
    if (!empty($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else if (!empty($data_)) {
        echo json_encode($data_);
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'register') {
    $fields = Wo_GetWelcomeFileds();
    if (empty($_POST['email']) || empty($_POST['username']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
        $errors = $error_icon . $wo['lang']['please_check_details'];
    } else {
        $is_exist = Wo_IsNameExist($_POST['username'], 0);
        if (empty($_POST['phone_num']) && $wo['config']['sms_or_email'] == 'sms') {
            $errors = $error_icon . $wo['lang']['worng_phone_number'];
        }
        if (in_array(true, $is_exist)) {
            $errors = $error_icon . $wo['lang']['username_exists'];
        }
        if (Wo_CheckIfUserCanRegister($wo['config']['user_limit']) === false) {
            $errors = $error_icon . $wo['lang']['limit_exceeded'];
        }
        if (in_array($_POST['username'], $wo['site_pages'])) {
            $errors = $error_icon . $wo['lang']['username_invalid_characters'];
        }
        if (strlen($_POST['username']) < 5 OR strlen($_POST['username']) > 32) {
            $errors = $error_icon . $wo['lang']['username_characters_length'];
        }
        if (!preg_match('/^[\w]+$/', $_POST['username'])) {
            $errors = $error_icon . $wo['lang']['username_invalid_characters'];
        }
        if (!empty($_POST['phone_num'])) {
            if (!preg_match('/^\+?\d+$/', $_POST['phone_num'])) {
                $errors = $error_icon . $wo['lang']['worng_phone_number'];
            } else {
                if (Wo_PhoneExists($_POST['phone_num']) === true) {
                    $errors = $error_icon . $wo['lang']['phone_already_used'];
                }
            }
        }
        if (Wo_EmailExists($_POST['email']) === true) {
            $errors = $error_icon . $wo['lang']['email_exists'];
        }
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors = $error_icon . $wo['lang']['email_invalid_characters'];
        }
        if (strlen($_POST['password']) < 6) {
            $errors = $error_icon . $wo['lang']['password_short'];
        }
        if ($_POST['password'] != $_POST['confirm_password']) {
            $errors = $error_icon . $wo['lang']['password_mismatch'];
        }
        if ($config['reCaptcha'] == 1) {
            if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
                $errors = $error_icon . $wo['lang']['reCaptcha_error'];
            }
        }
        $gender = 'male';
        if (!empty($_POST['gender'])) {
            if ($_POST['gender'] != 'male' && $_POST['gender'] != 'female') {
                $gender = 'male';
            } else {
                $gender = $_POST['gender'];
            }
        }
        if (!empty($fields) && count($fields) > 0) {
            foreach ($fields as $key => $field) {
                if (empty($_POST[$field['fid']])) {
                    $errors = $error_icon . $field['name'] . ' is required';
                }
                if (mb_strlen($_POST[$field['fid']]) > $field['length']) {
                    $errors = $error_icon . $field['name'] . ' field max characters is ' . $field['length'];
                }
            }
        }
    }
    $field_data = array();
    if (empty($errors)) {
        if (!empty($fields) && count($fields) > 0) {
            foreach ($fields as $key => $field) {
                if (!empty($_POST[$field['fid']])) {
                    $name = $field['fid'];
                    if (!empty($_POST[$name])) {
                        $field_data[] = array(
                            $name => $_POST[$name]
                        );
                    }
                }
            }
        }
        $activate = ($wo['config']['emailValidation'] == '1') ? '0' : '1';
        $re_data  = array(
            'email' => Wo_Secure($_POST['email'], 0),
            'username' => Wo_Secure($_POST['username'], 0),
            'password' => Wo_Secure($_POST['password'], 0),
            'email_code' => Wo_Secure(md5($_POST['username']), 0),
            'src' => 'site',
            'gender' => Wo_Secure($gender),
            'lastseen' => time(),
            'active' => Wo_Secure($activate)
        );
        if (!empty($_SESSION['ref']) && $wo['config']['affiliate_type'] == 0) {
            $ref_user_id = Wo_UserIdFromUsername($_SESSION['ref']);
            if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                $re_data['referrer'] = Wo_Secure($ref_user_id);
                $re_data['src']      = Wo_Secure('Referrer');
                $update_balance      = Wo_UpdateBalance($ref_user_id, $wo['config']['amount_ref']);
                unset($_SESSION['ref']);
            }
        }
        if (!empty($_POST['phone_num'])) {
            $re_data['phone_number'] = Wo_Secure($_POST['phone_num']);
        }
        $register = Wo_RegisterUser($re_data);
        if ($register === true) {
            if ($activate == 1) {
                $data  = array(
                    'status' => 200,
                    'message' => $success_icon . $wo['lang']['successfully_joined_label']
                );
                $login = Wo_Login($_POST['username'], $_POST['password']);
                if ($login === true) {
                    $session = Wo_CreateLoginSession(Wo_UserIdFromUsername($_POST['username']));
                    $_SESSION['user_id'] = $session;
                    setcookie(
                      "user_id",
                      $session,
                      time() + (10 * 365 * 24 * 60 * 60)
                    );
                }
                $data['location'] = Wo_SeoLink('index.php?link1=start-up');
            } else if ($wo['config']['sms_or_email'] == 'mail') {
                $wo['user']        = $_POST;
                $body              = Wo_LoadPage('emails/activate');
                $send_message_data = array(
                    'from_email' => $wo['config']['siteEmail'],
                    'from_name' => $wo['config']['siteName'],
                    'to_email' => $_POST['email'],
                    'to_name' => $_POST['username'],
                    'subject' => $wo['lang']['account_activation'],
                    'charSet' => 'utf-8',
                    'message_body' => $body,
                    'is_html' => true
                );
                $send              = Wo_SendMessage($send_message_data);
                $errors            = $success_icon . $wo['lang']['successfully_joined_verify_label'];
            } else if ($wo['config']['sms_or_email'] == 'sms' && !empty($_POST['phone_num'])) {
                $random_activation = Wo_Secure(rand(11111, 99999));
                $message           = "Your confirmation code is: {$random_activation}";
                $user_id           = Wo_UserIdFromUsername($_POST['username']);
                $query             = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `sms_code` = '{$random_activation}' WHERE `user_id` = {$user_id}");
                if ($query) {
                    if (Wo_SendSMSMessage($_POST['phone_num'], $message) === true) {
                        $data = array(
                            'status' => 300,
                            'location' => Wo_SeoLink('index.php?link1=confirm-sms?code=' . Wo_Secure(md5($_POST['username']), 0))
                        );
                    } else {
                        $errors = $error_icon . $wo['lang']['failed_to_send_code_email'];
                    }
                }
            }
        }
        if (!empty($_SESSION['user_id']) && !empty($field_data)) {
            $user_id = Wo_GetUserFromSessionID($_SESSION['user_id']);
            $insert  = Wo_UpdateUserCustomData($user_id, $field_data, false);
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'recover') {
    if (empty($_POST['recoveremail'])) {
        $errors = $error_icon . $wo['lang']['please_check_details'];
    } else {
        if (!filter_var($_POST['recoveremail'], FILTER_VALIDATE_EMAIL)) {
            $errors = $error_icon . $wo['lang']['email_invalid_characters'];
        } else if (Wo_EmailExists($_POST['recoveremail']) === false) {
            $errors = $error_icon . $wo['lang']['email_not_found'];
        }
    }
    if (empty($errors)) {
        $user_recover_data         = Wo_UserData(Wo_UserIdFromEmail($_POST['recoveremail']));
        $subject                   = $config['siteName'] . ' ' . $wo['lang']['password_rest_request'];
        $user_recover_data['link'] = Wo_Link('index.php?link1=reset-password&code=' . $user_recover_data['user_id'] . '_' . $user_recover_data['password']);
        $wo['recover']             = $user_recover_data;
        $body                      = Wo_LoadPage('emails/recover');
        $send_message_data         = array(
            'from_email' => $wo['config']['siteEmail'],
            'from_name' => $wo['config']['siteName'],
            'to_email' => $_POST['recoveremail'],
            'to_name' => '',
            'subject' => $subject,
            'charSet' => 'utf-8',
            'message_body' => $body,
            'is_html' => true
        );
        $send                      = Wo_SendMessage($send_message_data);
        $data                      = array(
            'status' => 200,
            'message' => $success_icon . $wo['lang']['email_sent']
        );
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'reset_password') {
    if (isset($_POST['id'])) {
        if (Wo_isValidPasswordResetToken($_POST['id']) === false) {
            $errors = $error_icon . $wo['lang']['invalid_token'];
        } elseif (empty($_POST['id'])) {
            $errors = $error_icon . $wo['lang']['processing_error'];
        } elseif (empty($_POST['password'])) {
            $errors = $error_icon . $wo['lang']['please_check_details'];
        } elseif (strlen($_POST['password']) < 5) {
            $errors = $error_icon . $wo['lang']['password_short'];
        }
        if (empty($errors)) {
            $user_id  = explode("_", $_POST['id']);
            $password = Wo_Secure($_POST['password']);
            if (Wo_ResetPassword($user_id[0], $password) === true) {
                $_SESSION['user_id'] = Wo_CreateLoginSession($user_id[0]);
            }
            $data = array(
                'status' => 200,
                'message' => $success_icon . $wo['lang']['password_changed'],
                'location' => $wo['config']['site_url']
            );
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == "search") {
    $data = array(
        'status' => 200,
        'html' => ''
    );
    if ($s == 'recipients' AND $wo['loggedin'] == true && isset($_GET['query'])) {
        foreach (Wo_GetMessagesUsers($wo['user']['user_id'], $_GET['query']) as $wo['recipient']) {
            $data['html'] .= Wo_LoadPage('messages/messages-recipients-list');
        }
    }
    if ($s == 'normal' && isset($_GET['query'])) {
        foreach (Wo_GetSearch($_GET['query']) as $wo['result']) {
            $data['html'] .= Wo_LoadPage('header/search');
        }
    }
    if ($s == 'hash' && isset($_GET['query'])) {
        foreach (Wo_GetSerachHash($_GET['query']) as $wo['result']) {
            $data['html'] .= Wo_LoadPage('header/hashtags-result');
        }
    }
    if ($s == 'recent' && $wo['loggedin'] == true) {
        foreach (Wo_GetRecentSerachs() as $wo['result']) {
            $data['html'] .= Wo_LoadPage('header/search');
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == "get_search_filter") {
    $data = array(
        'status' => 200,
        'html' => ''
    );
    if (isset($_POST)) {
        foreach (Wo_GetSearchFilter($_POST) as $wo['result']) {
            $data['html'] .= Wo_LoadPage('search/result');
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == "update_announcement_views") {
    if (isset($_GET['id'])) {
        $UpdateAnnouncementViews = Wo_UpdateAnnouncementViews($_GET['id']);
        if ($UpdateAnnouncementViews === true) {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_more_hashtag_posts') {
    $html = '';
    if (isset($_POST['after_post_id'])) {
        $after_post_id = Wo_Secure($_POST['after_post_id']);
        foreach (Wo_GetHashtagPosts($_POST['hashtagName'], $after_post_id, 20) as $wo['story']) {
            $html .= Wo_LoadPage('story/content');
        }
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($wo['loggedin'] == false) {
    exit("Please login or signup to continue.");
}
if ($f == "get_more_following") {
    $html = '';
    if (isset($_GET['user_id']) && isset($_GET['after_last_id'])) {
        foreach (Wo_GetFollowing($_GET['user_id'], 'profile', 10, $_GET['after_last_id']) as $wo['UsersList']) {
            $html .= Wo_LoadPage('timeline/follow-list');
        }
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == "get_more_followers") {
    $html = '';
    if (isset($_GET['user_id']) && isset($_GET['after_last_id'])) {
        foreach (Wo_GetFollowers($_GET['user_id'], 'profile', 10, $_GET['after_last_id']) as $wo['UsersList']) {
            $html .= Wo_LoadPage('timeline/follow-list');
        }
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'check_username') {
    if (isset($_GET['username'])) {
        $usename = Wo_Secure($_GET['username']);
        if ($usename == $wo['user']['username']) {
            $data['status']  = 200;
            $data['message'] = $wo['lang']['available'];
        } else if (strlen($usename) < 5) {
            $data['status']  = 400;
            $data['message'] = $wo['lang']['too_short'];
        } else if (strlen($usename) > 32) {
            $data['status']  = 500;
            $data['message'] = $wo['lang']['too_long'];
        } else if (!preg_match('/^[\w]+$/', $_GET['username'])) {
            $data['status']  = 600;
            $data['message'] = $wo['lang']['username_invalid_characters_2'];
        } else {
            $is_exist = Wo_IsNameExist($_GET['username'], 0);
            if (in_array(true, $is_exist)) {
                $data['status']  = 300;
                $data['message'] = $wo['lang']['in_use'];
            } else {
                $data['status']  = 200;
                $data['message'] = $wo['lang']['available'];
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == "update_general_settings") {
    if (isset($_POST) && Wo_CheckSession($hash_id) === true) {
        if (empty($_POST['username']) OR empty($_POST['email'])) {
            $errors[] = $error_icon . ' Please Check the fields.';
        } else {
            $Userdata = Wo_UserData($_POST['user_id']);
            $age_data = '0000-00-00';
            if (!empty($Userdata['user_id'])) {
                if ($_POST['email'] != $Userdata['email']) {
                    if (Wo_EmailExists($_POST['email'])) {
                        $errors[] = $error_icon . $wo['lang']['email_exists'];
                    }
                }
                if ($_POST['username'] != $Userdata['username']) {
                    $is_exist = Wo_IsNameExist($_POST['username'], 0);
                    if (in_array(true, $is_exist)) {
                        $errors[] = $error_icon . $wo['lang']['username_exists'];
                    }
                }
                if (in_array($_POST['username'], $wo['site_pages'])) {
                    $errors[] = $error_icon . $wo['lang']['username_invalid_characters'];
                }
                if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $error_icon . $wo['lang']['email_invalid_characters'];
                }
                if (strlen($_POST['username']) < 5 || strlen($_POST['username']) > 32) {
                    $errors[] = $error_icon . $wo['lang']['username_characters_length'];
                }
                if (!preg_match('/^[\w]+$/', $_POST['username'])) {
                    $errors[] = $error_icon . $wo['lang']['username_invalid_characters'];
                }
                if (!empty($_POST['age_year']) || !empty($_POST['age_day']) || !empty($_POST['age_month'])) {
                    if (empty($_POST['age_year']) || empty($_POST['age_day']) || empty($_POST['age_month'])) {
                        $errors[] = $error_icon . $wo['lang']['please_choose_correct_date'];
                    } else {
                        $age_data = $_POST['age_year'] . '-' . $_POST['age_month'] . '-' . $_POST['age_day'];
                    }
                }
                $active = $Userdata['active'];
                if (!empty($_POST['active'])) {
                    if ($_POST['active'] == 'active') {
                        $active = 1;
                    } else {
                        $active = 2;
                    }
                    if ($active == $Userdata['active']) {
                        $active = $Userdata['active'];
                    }
                }
                $type = $Userdata['admin'];
                if (!empty($_POST['type']) && Wo_IsAdmin()) {
                    if ($_POST['type'] == 'admin') {
                        $type = 1;
                    } else if ($_POST['type'] == 'user') {
                        $type = 0;
                    } else if ($_POST['type'] == 'mod') {
                        $type = 2;
                    }
                    if ($type == $Userdata['admin']) {
                        $type = $Userdata['admin'];
                    }
                }
                $member_type = $Userdata['pro_type'];
                $member_pro  = $Userdata['is_pro'];
                $time        = $Userdata['pro_time'];
                if (!empty($_POST['pro_type']) && (Wo_IsAdmin() || Wo_IsModerator())) {
                    if ($_POST['pro_type'] == 'free') {
                        $member_type = 0;
                        $member_pro  = 0;
                        $down        = Wo_DownUpgradeUser($Userdata['user_id']);
                    } else if ($_POST['pro_type'] == 'star') {
                        $member_type = 1;
                        $member_pro  = 1;
                        $time        = time();
                    } else if ($_POST['pro_type'] == 'hot') {
                        $member_type = 2;
                        $member_pro  = 1;
                        $time        = time();
                    } else if ($_POST['pro_type'] == 'ultima') {
                        $member_type = 3;
                        $member_pro  = 1;
                        $time        = time();
                    } else if ($_POST['pro_type'] == 'vip') {
                        $member_type = 4;
                        $member_pro  = 1;
                        $time        = time();
                    }
                }
                $gender       = 'male';
                $gender_array = array(
                    'male',
                    'female'
                );
                if (!empty($_POST['gender'])) {
                    if (in_array($_POST['gender'], $gender_array)) {
                        $gender = $_POST['gender'];
                    }
                }
                if (empty($errors)) {
                    $Update_data = array(
                        'username' => $_POST['username'],
                        'email' => $_POST['email'],
                        'birthday' => $age_data,
                        'gender' => $gender,
                        'country_id' => $_POST['country'],
                        'active' => $active,
                        'admin' => $type,
                        'is_pro' => $member_pro,
                        'pro_type' => $member_type,
                        'pro_time' => $time
                    );
                    if (!empty($_POST['verified'])) {
                        if ($_POST['verified'] == 'verified') {
                            $Verification = 1;
                        } else {
                            $Verification = 0;
                        }
                        if ($Verification == $Userdata['verified']) {
                            $Verification = $Userdata['verified'];
                        }
                        $Update_data['verified'] = $Verification;
                    }
                    if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
                        $field_data = array();
                        if (!empty($_POST['custom_fields'])) {
                            $fields = Wo_GetProfileFields('general');
                            foreach ($fields as $key => $field) {
                                $name = $field['fid'];
                                if (!empty($_POST[$name])) {
                                    if (mb_strlen($_POST[$name]) > $field['length']) {
                                        $errors[] = $error_icon . $field['name'] . ' field max characters is ' . $field['length'];
                                    }
                                    $field_data[] = array(
                                        $name => $_POST[$name]
                                    );
                                }
                            }
                        }
                        if (!empty($field_data)) {
                            $insert = Wo_UpdateUserCustomData($_POST['user_id'], $field_data);
                        }
                        if (empty($errors)) {
                            $data = array(
                                'status' => 200,
                                'message' => $success_icon . $wo['lang']['setting_updated'],
                                'username' => Wo_SeoLink('index.php?link1=timeline&u=' . Wo_Secure($_POST['username']))
                            );
                        }
                    }
                }
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == "update_privacy_settings") {
    if (isset($_POST['user_id']) && Wo_CheckSession($hash_id) === true) {
        $message_privacy         = 0;
        $follow_privacy          = 0;
        $post_privacy            = 'ifollow';
        $showlastseen            = 0;
        $confirm_followers       = 0;
        $show_activities_privacy = 0;
        $status                  = 0;
        $visit_privacy           = 0;
        $birth_privacy           = 0;
        $array                   = array(
            '0',
            '1'
        );
        $array_2                 = array(
            '0',
            '1',
            '2'
        );
        $array_two               = array(
            'everyone',
            'ifollow',
            'nobody'
        );
        if (!empty($_POST['post_privacy'])) {
            if (in_array($_POST['post_privacy'], $array_two)) {
                $post_privacy = $_POST['post_privacy'];
            }
        }
        if (!empty($_POST['confirm_followers'])) {
            if (in_array($_POST['confirm_followers'], $array)) {
                $confirm_followers = $_POST['confirm_followers'];
            }
        }
        if (!empty($_POST['follow_privacy'])) {
            if (in_array($_POST['follow_privacy'], $array)) {
                $follow_privacy = $_POST['follow_privacy'];
            }
        }
        if (!empty($_POST['show_activities_privacy'])) {
            if (in_array($_POST['show_activities_privacy'], $array)) {
                $show_activities_privacy = $_POST['show_activities_privacy'];
            }
        }
        if (!empty($_POST['showlastseen'])) {
            if (in_array($_POST['showlastseen'], $array)) {
                $showlastseen = $_POST['showlastseen'];
            }
        }
        if (!empty($_POST['message_privacy'])) {
            if (in_array($_POST['message_privacy'], $array)) {
                $message_privacy = $_POST['message_privacy'];
            }
        }
        if (!empty($_POST['status'])) {
            if (in_array($_POST['status'], $array)) {
                $status = $_POST['status'];
            }
        }
        if (!empty($_POST['visit_privacy'])) {
            if (in_array($_POST['visit_privacy'], $array)) {
                $visit_privacy = $_POST['visit_privacy'];
            }
        }
        if (!empty($_POST['birth_privacy'])) {
            if (in_array($_POST['birth_privacy'], $array_2)) {
                $birth_privacy = $_POST['birth_privacy'];
            }
        }
        $userdata = Wo_UserData($_POST['user_id']);
        if ($wo['config']['pro'] == 1 && empty($_POST['showlastseen']) && empty($_POST['profileVisit'])) {
            if ($userdata['is_pro'] == 0) {
                $visit_privacy = 1;
                $showlastseen  = 1;
            }
        }
        $Update_data = array(
            'message_privacy' => $message_privacy,
            'follow_privacy' => $follow_privacy,
            'post_privacy' => $post_privacy,
            'showlastseen' => $showlastseen,
            'confirm_followers' => $confirm_followers,
            'show_activities_privacy' => $show_activities_privacy,
            'visit_privacy' => $visit_privacy,
            'birth_privacy' => $birth_privacy,
            'status' => $status
        );
        if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
            $data = array(
                'status' => 200,
                'message' => $success_icon . $wo['lang']['setting_updated']
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == "update_email_settings") {
    if (isset($_POST['user_id']) && Wo_CheckSession($hash_id) === true) {
        $e_liked             = 0;
        $e_shared            = 0;
        $e_wondered          = 0;
        $e_commented         = 0;
        $e_followed          = 0;
        $e_liked_page        = 0;
        $e_visited           = 0;
        $e_mentioned         = 0;
        $e_joined_group      = 0;
        $e_accepted          = 0;
        $e_profile_wall_post = 0;
        $array               = array(
            '0',
            '1'
        );
        if (!empty($_POST['e_liked'])) {
            if (in_array($_POST['e_liked'], $array)) {
                $e_liked = 1;
            }
        }
        if (!empty($_POST['e_shared'])) {
            if (in_array($_POST['e_shared'], $array)) {
                $e_shared = 1;
            }
        }
        if (!empty($_POST['e_wondered'])) {
            if (in_array($_POST['e_wondered'], $array)) {
                $e_wondered = 1;
            }
        }
        if (!empty($_POST['e_commented'])) {
            if (in_array($_POST['e_commented'], $array)) {
                $e_commented = 1;
            }
        }
        if (!empty($_POST['e_followed'])) {
            if (in_array($_POST['e_followed'], $array)) {
                $e_followed = 1;
            }
        }
        if (!empty($_POST['e_liked_page'])) {
            if (in_array($_POST['e_liked_page'], $array)) {
                $e_liked_page = 1;
            }
        }
        if (!empty($_POST['e_visited'])) {
            if (in_array($_POST['e_visited'], $array)) {
                $e_visited = 1;
            }
        }
        if (!empty($_POST['e_mentioned'])) {
            if (in_array($_POST['e_mentioned'], $array)) {
                $e_mentioned = 1;
            }
        }
        if (!empty($_POST['e_joined_group'])) {
            if (in_array($_POST['e_joined_group'], $array)) {
                $e_joined_group = 1;
            }
        }
        if (!empty($_POST['e_accepted'])) {
            if (in_array($_POST['e_accepted'], $array)) {
                $e_accepted = 1;
            }
        }
        if (!empty($_POST['e_profile_wall_post'])) {
            if (in_array($_POST['e_profile_wall_post'], $array)) {
                $e_profile_wall_post = 1;
            }
        }
        $Update_data = array(
            'e_liked' => $e_liked,
            'e_shared' => $e_shared,
            'e_wondered' => $e_wondered,
            'e_commented' => $e_commented,
            'e_followed' => $e_followed,
            'e_accepted' => $e_accepted,
            'e_mentioned' => $e_mentioned,
            'e_joined_group' => $e_joined_group,
            'e_liked_page' => $e_liked_page,
            'e_visited' => $e_visited,
            'e_profile_wall_post' => $e_profile_wall_post
        );
        if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
            $data = array(
                'status' => 200,
                'message' => $success_icon . $wo['lang']['setting_updated']
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_new_logged_user_details') {
    if (empty($_POST['new_password']) || empty($_POST['username']) || empty($_POST['repeat_new_password']) || Wo_CheckSession($hash_id) === false) {
        $errors[] = $error_icon . $wo['lang']['please_check_details'];
    } else {
        if ($_POST['new_password'] != $_POST['repeat_new_password']) {
            $errors[] = $error_icon . $wo['lang']['password_mismatch'];
        }
        if (strlen($_POST['new_password']) < 6) {
            $errors[] = $error_icon . $wo['lang']['password_short'];
        }
        if (strlen($_POST['username']) > 32) {
            $errors[] = $error_icon . $wo['lang']['username_characters_length'];
        }
        if (strlen($_POST['username']) < 5) {
            $errors[] = $error_icon . $wo['lang']['username_characters_length'];
        }
        if (!preg_match('/^[\w]+$/', $_POST['username'])) {
            $errors[] = $error_icon . $wo['lang']['username_invalid_characters'];
        }
        if (Wo_UserExists($_POST['username']) === true) {
            $errors[] = $error_icon . $wo['lang']['username_exists'];
        }
        if (empty($errors)) {
            $Update_data = array(
                'password' => md5($_POST['new_password']),
                'username' => $_POST['username']
            );
            if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
                $get_user = Wo_UserData($_POST['user_id']);
                $data     = array(
                    'status' => 200,
                    'message' => $success_icon . $wo['lang']['setting_updated'],
                    'url' => $get_user['url']
                );
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == "update_user_password") {
    if (isset($_POST['user_id']) && Wo_CheckSession($hash_id) === true) {
        $Userdata = Wo_UserData($_POST['user_id']);
        if (!empty($Userdata['user_id'])) {
            if ($_POST['user_id'] != $wo['user']['user_id']) {
                $_POST['current_password'] = 1;
            }
            if (empty($_POST['current_password']) OR empty($_POST['new_password']) OR empty($_POST['repeat_new_password'])) {
                $errors[] = $error_icon . $wo['lang']['please_check_details'];
            } else {
                if ($_POST['user_id'] == $wo['user']['user_id']) {
                    if (md5($_POST['current_password']) != $Userdata['password']) {
                        $errors[] = $error_icon . $wo['lang']['current_password_mismatch'];
                    }
                }
                if ($_POST['new_password'] != $_POST['repeat_new_password']) {
                    $errors[] = $error_icon . $wo['lang']['password_mismatch'];
                }
                if (strlen($_POST['new_password']) < 6) {
                    $errors[] = $error_icon . $wo['lang']['password_short'];
                }
                if (empty($errors)) {
                    $Update_data = array(
                        'password' => md5($_POST['new_password'])
                    );
                    if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
                        $user_id = Wo_Secure($_POST['user_id']);
                        $session_id = (!empty($_SESSION['user_id'])) ? $_SESSION['user_id'] : $_COOKIE['user_id'];
                        $session_id = Wo_Secure($session_id);
                        $mysqli = mysqli_query($sqlConnect, "DELETE FROM " . T_APP_SESSIONS . " WHERE `user_id` = '{$user_id}' AND `session_id` <> '{$session_id}'");
                        $data = array(
                            'status' => 200,
                            'message' => $success_icon . $wo['lang']['setting_updated']
                        );
                    }
                }
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == "update_profile_setting") {
    if (isset($_POST['user_id']) && Wo_CheckSession($hash_id) === true) {
        $Userdata = Wo_UserData($_POST['user_id']);
        if (!empty($Userdata['user_id'])) {
            if (!empty($_POST['website'])) {
                if (!filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
                    $errors[] = $error_icon . $wo['lang']['website_invalid_characters'];
                }
            }
            if (!empty($_POST['working_link'])) {
                if (!filter_var($_POST['working_link'], FILTER_VALIDATE_URL)) {
                    $errors[] = $error_icon . $wo['lang']['company_website_invalid'];
                }
            }
            if (!is_numeric($_POST['relationship']) || empty($_POST['relationship']) || $_POST['relationship'] > 4) {
                $_POST['relationship'] = '';
            }
            if (empty($errors)) {
                $Update_data = array(
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'website' => $_POST['website'],
                    'about' => $_POST['about'],
                    'working' => $_POST['working'],
                    'working_link' => $_POST['working_link'],
                    'address' => $_POST['address'],
                    'school' => $_POST['school'],
                    'relationship_id' => $_POST['relationship']
                );
                if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
                    $field_data = array();
                    if (!empty($_POST['custom_fields'])) {
                        $fields = Wo_GetProfileFields('profile');
                        foreach ($fields as $key => $field) {
                            $name = $field['fid'];
                            if (!empty($_POST[$name])) {
                                if (mb_strlen($_POST[$name]) > $field['length']) {
                                    $errors[] = $error_icon . $field['name'] . ' field max characters is ' . $field['length'];
                                }
                                $field_data[] = array(
                                    $name => $_POST[$name]
                                );
                            }
                        }
                    }
                    if (!empty($field_data)) {
                        $insert = Wo_UpdateUserCustomData($_POST['user_id'], $field_data);
                    }
                    if (empty($errors)) {
                        $data = array(
                            'status' => 200,
                            'first_name' => Wo_Secure($_POST['first_name']),
                            'last_name' => Wo_Secure($_POST['last_name']),
                            'message' => $success_icon . $wo['lang']['setting_updated']
                        );
                    }
                }
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == "update_socialinks_setting") {
    if (isset($_POST['user_id']) && Wo_CheckSession($hash_id) === true) {
        $Userdata = Wo_UserData($_POST['user_id']);
        if (!empty($Userdata['user_id'])) {
            if (empty($errors)) {
                $Update_data = array(
                    'facebook' => $_POST['facebook'],
                    'google' => $_POST['google'],
                    'linkedin' => $_POST['linkedin'],
                    'vk' => $_POST['vk'],
                    'instagram' => $_POST['instagram'],
                    'twitter' => $_POST['twitter'],
                    'youtube' => $_POST['youtube']
                );
                if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
                    $field_data = array();
                    if (!empty($_POST['custom_fields'])) {
                        $fields = Wo_GetProfileFields('social');
                        foreach ($fields as $key => $field) {
                            $name = $field['fid'];
                            if (!empty($_POST[$name])) {
                                if (mb_strlen($_POST[$name]) > $field['length']) {
                                    $errors[] = $error_icon . $field['name'] . ' field max characters is ' . $field['length'];
                                }
                                $field_data[] = array(
                                    $name => $_POST[$name]
                                );
                            }
                        }
                    }
                    if (!empty($field_data)) {
                        $insert = Wo_UpdateUserCustomData($_POST['user_id'], $field_data);
                    }
                    if (empty($errors)) {
                        $data = array(
                            'status' => 200,
                            'message' => $success_icon . $wo['lang']['setting_updated']
                        );
                    }
                }
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == "update_images_setting") {
    if (isset($_POST['user_id']) && Wo_CheckSession($hash_id) === true) {
        $Userdata = Wo_UserData($_POST['user_id']);
        if (!empty($Userdata['user_id'])) {
            if (isset($_FILES['avatar']['name'])) {
                if (Wo_UploadImage($_FILES["avatar"]["tmp_name"], $_FILES['avatar']['name'], 'avatar', $_FILES['avatar']['type'], $_POST['user_id']) === true) {
                    $Userdata = Wo_UserData($_POST['user_id']);
                }
            }
            if (isset($_FILES['cover']['name'])) {
                if (Wo_UploadImage($_FILES["cover"]["tmp_name"], $_FILES['cover']['name'], 'cover', $_FILES['cover']['type'], $_POST['user_id']) === true) {
                    $Userdata = Wo_UserData($_POST['user_id']);
                }
            }
            if (empty($errors)) {
                $Update_data = array(
                    'lastseen' => time()
                );
                if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
                    $userdata2 = Wo_UserData($_POST['user_id']);
                    $data      = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['setting_updated'],
                        'cover' => $userdata2['cover'],
                        'avatar' => $userdata2['avatar']
                    );
                }
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == "update_design_setting") {
    if (isset($_POST['user_id']) && Wo_CheckSession($hash_id) === true) {
        $Userdata = Wo_UserData($_POST['user_id']);
        if (!empty($Userdata['user_id'])) {
            $background_image_status = 0;
            if (isset($_FILES['background_image']['name'])) {
                if (Wo_UploadImage($_FILES["background_image"]["tmp_name"], $_FILES['background_image']['name'], 'background_image', $_FILES['background_image']['type'], $_POST['user_id']) === true) {
                    $background_image_status = 1;
                }
            }
            if (!empty($_POST['background_image_status'])) {
                if ($_POST['background_image_status'] == 'defualt') {
                    $background_image_status = 0;
                } else if ($_POST['background_image_status'] == 'my_background') {
                    $background_image_status = 1;
                } else {
                    $background_image_status = 0;
                }
            }
            $mediaFilename = $Userdata['css_file'];
            if (isset($_FILES['css_file']['name']) && $wo['config']['css_upload'] == 1) {
                $fileInfo      = array(
                    'file' => $_FILES["css_file"]["tmp_name"],
                    'name' => $_FILES['css_file']['name'],
                    'size' => $_FILES["css_file"]["size"],
                    'type' => $_FILES["css_file"]["type"],
                    'types' => 'css,CSS'
                );
                $media         = Wo_ShareFile($fileInfo);
                $mediaFilename = $media['filename'];
            }
            if (empty($errors)) {
                $Update_data = array(
                    'background_image_status' => $background_image_status,
                    'css_file' => $mediaFilename
                );
                $css_status  = 1;
                if (!empty($_POST['css_status'])) {
                    if ($_POST['css_status'] == 1) {
                        $Update_data['css_file'] = '';
                    } else if ($_POST['css_status'] == 2) {
                        $Update_data['css_file'] = $mediaFilename;
                    }
                }
                if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
                    $userdata2 = Wo_UserData($_POST['user_id']);
                    $data      = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['setting_updated']
                    );
                }
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'update_page_design_setting') {
    if (isset($_POST['page_id']) && Wo_CheckSession($hash_id) === true) {
        $Userdata = Wo_PageData($_POST['page_id']);
        if (!empty($Userdata['id'])) {
            $background_image_status = 0;
            if (isset($_FILES['background_image']['name'])) {
                if (Wo_UploadImage($_FILES["background_image"]["tmp_name"], $_FILES['background_image']['name'], 'page_background_image', $_FILES['background_image']['type'], $_POST['page_id'], 'page') === true) {
                    $background_image_status = 1;
                }
            }
            if (!empty($_POST['background_image_status'])) {
                if ($_POST['background_image_status'] == 'defualt') {
                    $background_image_status = 0;
                } else if ($_POST['background_image_status'] == 'my_background') {
                    $background_image_status = 1;
                } else {
                    $background_image_status = 0;
                }
            }
            if (empty($errors)) {
                $Update_data = array(
                    'background_image_status' => $background_image_status
                );
                if (Wo_UpdatePageData($_POST['page_id'], $Update_data)) {
                    $userdata2 = Wo_PageData($_POST['page_id']);
                    $data      = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['setting_updated']
                    );
                }
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'update_user_avatar_picture') {
    if (isset($_FILES['avatar']['name'])) {
        if (Wo_UploadImage($_FILES["avatar"]["tmp_name"], $_FILES['avatar']['name'], 'avatar', $_FILES['avatar']['type'], $_POST['user_id']) === true) {
            $img  = Wo_UserData($_POST['user_id']);
            $data = array(
                'status' => 200,
                'img' => $img['avatar'],
                'img_or' => $img['avatar_org'],
                'big_text' => $wo['lang']['looks_good'],
                'small_text' => $wo['lang']['looks_good_des']
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_user_cover_picture') {
    if (isset($_FILES['cover']['name'])) {
        if (Wo_UploadImage($_FILES["cover"]["tmp_name"], $_FILES['cover']['name'], 'cover', $_FILES['cover']['type'], $_POST['user_id']) === true) {
            $img  = Wo_UserData($_POST['user_id']);
            $data = array(
                'status' => 200,
                'img' => $img['cover'],
                'cover_or' => $img['cover_org'],
                'cover_full' => Wo_GetMedia($img['cover_full'])
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'set_admin_alert_cookie') {
    setcookie('profileAlert', '1', time() + 86000);
}
if ($f == 'delete_user_account') {
    if (isset($_POST['password'])) {
        if (md5($_POST['password']) != $wo['user']['password']) {
            $errors[] = $error_icon . $wo['lang']['current_password_mismatch'];
        }
        if (empty($errors)) {
            if (Wo_DeleteUser($wo['user']['user_id']) === true) {
                $data = array(
                    'status' => 200,
                    'message' => $success_icon . $wo['lang']['account_deleted'],
                    'location' => Wo_SeoLink('index.php?link1=logout')
                );
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'update_sidebar_users') {
    $html = '';
    foreach (Wo_UserSug(5) as $wo['UsersList']) {
        $wo['UsersList']['user_name'] = $wo['UsersList']['name'];
        if (!empty($wo['UsersList']['last_name'])) {
            $wo['UsersList']['user_name'] = $wo['UsersList']['first_name'];
        }
        $html .= Wo_LoadPage('sidebar/sidebar-user-list');
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_sidebar_groups') {
    $html = '';
    foreach (Wo_GroupSug(5) as $wo['GroupList']) {
        $html .= Wo_LoadPage('sidebar/sidebar-group-list');
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'follow_user') {
    if (isset($_GET['following_id']) && Wo_CheckMainSession($hash_id) === true) {
        if (Wo_IsFollowing($_GET['following_id'], $wo['user']['user_id']) === true || Wo_IsFollowRequested($_GET['following_id'], $wo['user']['user_id']) === true) {
            if (Wo_DeleteFollow($_GET['following_id'], $wo['user']['user_id'])) {
                $data = array(
                    'status' => 200,
                    'can_send' => 0,
                    'html' => '' //Wo_GetFollowButton($_GET['following_id'])
                );
            }
        } else {
            if (Wo_RegisterFollow($_GET['following_id'], $wo['user']['user_id'])) {
                $data = array(
                    'status' => 200,
                    'can_send' => 0,
                    'html' => '' //Wo_GetFollowButton($_GET['following_id'])
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'accept_follow_request') {
    if (isset($_GET['following_id'])) {
        if (Wo_AcceptFollowRequest($_GET['following_id'], $wo['user']['user_id'])) {
            $data = array(
                'status' => 200,
                'html' => Wo_GetFollowButton($_GET['following_id'])
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'delete_follow_request') {
    if (isset($_GET['following_id'])) {
        if (Wo_DeleteFollowRequest($_GET['following_id'], $wo['user']['user_id'])) {
            $data = array(
                'status' => 200,
                'html' => Wo_GetFollowButton($_GET['following_id'])
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_follow_requests') {
    $data     = array(
        'status' => 200,
        'html' => ''
    );
    $requests = Wo_GetFollowRequests();
    if (count($requests) > 0) {
        foreach ($requests as $wo['request']) {
            $data['html'] .= Wo_LoadPage('header/follow-requests');
        }
    } else {
        $data['message'] = $wo['lang']['no_new_requests'];
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_notifications') {
    $data          = array(
        'status' => 200,
        'html' => ''
    );
    $notifications = Wo_GetNotifications();
    if (count($notifications) > 0) {
        foreach ($notifications as $wo['notification']) {
            $data['html'] .= Wo_LoadPage('header/notifecation');
            if ($wo['notification']['seen'] == 0) {
                $query     = "UPDATE " . T_NOTIFICATION . " SET `seen` = " . time() . " WHERE `id` = " . $wo['notification']['id'];
                $sql_query = mysqli_query($sqlConnect, $query);
            }
        }
    } else {
        $data['message'] = $wo['lang']['no_new_notification'];
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_messages') {
    if (Wo_CheckMainSession($hash_id) === true) {
        $data     = array(
            'status' => 200,
            'html' => ''
        );
        $messages = Wo_GetMessagesUsers($wo['user']['user_id'], '', 4);
        if (count($messages) > 0) {
            foreach ($messages as $wo['message']) {
                $data['html'] .= Wo_LoadPage('header/messages');
            }
        } else {
            $data['message'] = $wo['lang']['no_more_message_to_show'];
        }
        $data['messages_url']  = Wo_SeoLink('index.php?link1=messages');
        $data['messages_text'] = $wo['lang']['see_all'];
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_data') {
    if (Wo_CheckMainSession($hash_id) === true) {
        $data['pop']           = 0;
        $data['status']        = 200;
        $data['notifications'] = Wo_CountNotifications(array(
            'unread' => true
        ));
        $data['html']          = '';
        $notifications         = Wo_GetNotifications(array(
            'type_2' => 'popunder',
            'unread' => true,
            'limit' => 1
        ));
        foreach ($notifications as $wo['notification']) {
            $data['html']              = Wo_LoadPage('header/notifecation');
            $data['icon']              = $wo['notification']['notifier']['avatar'];
            $data['title']             = $wo['notification']['notifier']['name'];
            $data['notification_text'] = $wo['notification']['type_text'];
            $data['url']               = $wo['notification']['url'];
            $data['pop']               = 200;
            if ($wo['notification']['seen'] == 0) {
                $query     = "UPDATE " . T_NOTIFICATION . " SET `seen_pop` = " . time() . " WHERE `id` = " . $wo['notification']['id'];
                $sql_query = mysqli_query($sqlConnect, $query);
            }
        }
        $data['messages'] = Wo_CountMessages(array(
            'new' => true
        ), 'interval');
        $data['calls']    = 0;
        $data['is_call']  = 0;
        $check_calles     = Wo_CheckFroInCalls();
        if ($check_calles !== false && is_array($check_calles)) {
            $wo['incall']                 = $check_calles;
            $wo['incall']['in_call_user'] = Wo_UserData($check_calles['from_id']);
            $data['calls']                = 200;
            $data['is_call']              = 1;
            $data['calls_html']           = Wo_LoadPage('modals/in_call');
        }
        $data['followRequests'] = Wo_CountFollowRequests();
        $data['notifications_sound'] = $wo['user']['notifications_sound'];
    }
    $data['count_num'] = 0;
    if ($_GET['check_posts'] == 'true') {
        if (!empty($_GET['before_post_id']) && isset($_GET['user_id'])) {
            $html              = '';
            $postsData         = array(
                'before_post_id' => $_GET['before_post_id'],
                'publisher_id' => $_GET['user_id'],
                'limit' => 20
            );
            $posts             = Wo_GetPosts($postsData);
            $count             = count($posts);
            $data['count']     = str_replace('{count}', $count, $wo['lang']['view_more_posts']);
            $data['count_num'] = $count;
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_lastseen') {
    if (Wo_CheckMainSession($hash_id) === true) {
        if (Wo_LastSeen($wo['user']['user_id']) === true) {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'messages') {
    if ($s == 'get_user_messages') {
        if (!empty($_GET['user_id']) AND is_numeric($_GET['user_id']) AND $_GET['user_id'] > 0 && Wo_CheckMainSession($hash_id) === true) {
            $html       = '';
            $user_id    = $_GET['user_id'];
            $can_replay = true;
            $recipient  = Wo_UserData($user_id);
            $messages   = Wo_GetMessages(array(
                'user_id' => $user_id
            ));
            if (!empty($recipient['user_id']) && $recipient['message_privacy'] == 1) {
                if (Wo_IsFollowing($wo['user']['user_id'], $recipient['user_id']) === false) {
                    $can_replay = false;
                }
            }
            foreach ($messages as $wo['message']) {
                $html .= Wo_LoadPage('messages/messages-text-list');
            }
            $data = array(
                'status' => 200,
                'html' => $html,
                'can_replay' => $can_replay,
                'view_more_text' => $wo['lang']['view_more_messages'],
                'video_call' => 0
            );
            if ($wo['config']['video_chat'] == 1) {
                if ($recipient['lastseen'] > time() - 60) {
                    $data['video_call'] = 200;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'send_message') {
        if (isset($_POST['user_id']) && Wo_CheckMainSession($hash_id) === true) {
            $html          = '';
            $media         = '';
            $mediaFilename = '';
            $mediaName     = '';
            if (isset($_FILES['sendMessageFile']['name'])) {
                $fileInfo      = array(
                    'file' => $_FILES["sendMessageFile"]["tmp_name"],
                    'name' => $_FILES['sendMessageFile']['name'],
                    'size' => $_FILES["sendMessageFile"]["size"],
                    'type' => $_FILES["sendMessageFile"]["type"]
                );
                $media         = Wo_ShareFile($fileInfo);
                $mediaFilename = $media['filename'];
                $mediaName     = $media['name'];
            }
            $messages = Wo_RegisterMessage(array(
                'from_id' => Wo_Secure($wo['user']['user_id']),
                'to_id' => Wo_Secure($_POST['user_id']),
                'text' => Wo_Secure($_POST['textSendMessage']),
                'media' => Wo_Secure($mediaFilename),
                'mediaFileName' => Wo_Secure($mediaName),
                'time' => time()
            ));
            if ($messages > 0) {
                $messages = Wo_GetMessages(array(
                    'message_id' => $messages,
                    'user_id' => $_POST['user_id']
                ));
                foreach ($messages as $wo['message']) {
                    $html .= Wo_LoadPage('messages/messages-text-list');
                }
                $data = array(
                    'status' => 200,
                    'html' => $html
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_previous_messages') {
        $html = '';
        if (!empty($_GET['user_id']) && !empty($_GET['before_message_id'])) {
            $user_id           = Wo_Secure($_GET['user_id']);
            $before_message_id = Wo_Secure($_GET['before_message_id']);
            $messages          = Wo_GetMessages(array(
                'user_id' => $user_id,
                'before_message_id' => $before_message_id
            ));
            if ($messages > 0) {
                foreach ($messages as $wo['message']) {
                    $html .= Wo_LoadPage('messages/messages-text-list');
                }
                $data = array(
                    'status' => 200,
                    'html' => $html
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_recipients') {
        $html = '';
        foreach (Wo_GetMessagesUsers($wo['user']['user_id']) as $wo['recipient']) {
            $html .= Wo_LoadPage('messages/messages-recipients-list');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_new_messages') {
        $html = '';
        if (isset($_GET['user_id']) && Wo_CheckMainSession($hash_id) === true) {
            $user_id = Wo_Secure($_GET['user_id']);
            if (!empty($user_id)) {
                $user_id  = $_GET['user_id'];
                $messages = Wo_GetMessages(array(
                    'after_message_id' => $_GET['message_id'],
                    'user_id' => $user_id
                ));
                if (count($messages) > 0) {
                    foreach ($messages as $wo['message']) {
                        $html .= Wo_LoadPage('messages/messages-text-list');
                    }
                    $data = array(
                        'status' => 200,
                        'html' => $html,
                        'sender' => $wo['user']['user_id']
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_message') {
        if (isset($_GET['message_id']) && Wo_CheckMainSession($hash_id) === true) {
            $message_id = Wo_Secure($_GET['message_id']);
            if (!empty($message_id) || is_numeric($message_id) || $message_id > 0) {
                if (Wo_DeleteMessage($message_id) === true) {
                    $data = array(
                        'status' => 200
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_conversation') {
        if (isset($_GET['user_id']) && Wo_CheckMainSession($hash_id) === true) {
            $user_id = Wo_Secure($_GET['user_id']);
            if (!empty($user_id) || is_numeric($user_id) || $user_id > 0) {
                if (Wo_DeleteConversation($user_id) === true) {
                    $data = array(
                        'status' => 200,
                        'message' => 'Conversation has been deleted.'
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_last_message_seen_status') {
        if (isset($_GET['last_id'])) {
            $message_id = Wo_Secure($_GET['last_id']);
            if (!empty($message_id) || is_numeric($message_id) || $message_id > 0) {
                $seen = Wo_SeenMessage($message_id);
                if ($seen > 0) {
                    $data = array(
                        'status' => 200,
                        'time' => $seen['time'],
                        'seen' => $seen['seen']
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
if ($f == 'admin_setting' AND (Wo_IsAdmin() || Wo_IsModerator())) {
    if ($s == 'update_social_login_setting' && Wo_CheckSession($hash_id) === true) {
        $googleLogin    = 0;
        $twitterLogin   = 0;
        $linkedinLogin  = 0;
        $facebookLogin  = 0;
        $VkontakteLogin = 0;
        $InstagramLogin = 0;
        if (!empty($_POST['googleLogin'])) {
            $googleLogin = 1;
        }
        if (!empty($_POST['twitterLogin'])) {
            $twitterLogin = 1;
        }
        if (!empty($_POST['linkedinLogin'])) {
            $linkedinLogin = 1;
        }
        if (!empty($_POST['facebookLogin'])) {
            $facebookLogin = 1;
        }
        if (!empty($_POST['VkontakteLogin'])) {
            $VkontakteLogin = 1;
        }
        if (!empty($_POST['instagramLogin'])) {
            $InstagramLogin = 1;
        }
        $facebookAppId  = '';
        $facebookAppKey = '';
        if (!empty($_POST['facebookAppId'])) {
            $facebookAppId = $_POST['facebookAppId'];
        }
        if (!empty($_POST['facebookAppKey'])) {
            $facebookAppKey = $_POST['facebookAppKey'];
        }
        $googleAppId  = '';
        $googleAppKey = '';
        if (!empty($_POST['googleAppId'])) {
            $googleAppId = $_POST['googleAppId'];
        }
        if (!empty($_POST['googleAppKey'])) {
            $googleAppKey = $_POST['googleAppKey'];
        }
        $twitterAppId  = '';
        $twitterAppKey = '';
        if (!empty($_POST['twitterAppId'])) {
            $twitterAppId = $_POST['twitterAppId'];
        }
        if (!empty($_POST['twitterAppKey'])) {
            $twitterAppKey = $_POST['twitterAppKey'];
        }
        $linkedinAppId  = '';
        $linkedinAppKey = '';
        if (!empty($_POST['linkedinAppId'])) {
            $linkedinAppId = $_POST['linkedinAppId'];
        }
        if (!empty($_POST['linkedinAppKey'])) {
            $linkedinAppKey = $_POST['linkedinAppKey'];
        }
        $VkontakteAppId  = '';
        $VkontakteAppKey = '';
        if (!empty($_POST['VkontakteAppId'])) {
            $VkontakteAppId = $_POST['VkontakteAppId'];
        }
        if (!empty($_POST['VkontakteAppKey'])) {
            $VkontakteAppKey = $_POST['VkontakteAppKey'];
        }
        $instagramAppId  = '';
        $instagramAppkey = '';
        if (!empty($_POST['instagramAppId'])) {
            $instagramAppId = $_POST['instagramAppId'];
        }
        if (!empty($_POST['instagramAppkey'])) {
            $instagramAppkey = $_POST['instagramAppkey'];
        }
        $AllLogin    = ($googleLogin == '0' && $twitterLogin == '0' && $linkedinLogin == '0' && $facebookLogin == '0' && $VkontakteLogin == '0' && $InstagramLogin == '0') ? 0 : 1;
        $saveSetting = false;
        $data_array  = array(
            'googleLogin' => $googleLogin,
            'twitterLogin' => $twitterLogin,
            'linkedinLogin' => $linkedinLogin,
            'facebookLogin' => $facebookLogin,
            'VkontakteLogin' => $VkontakteLogin,
            'instagramLogin' => $InstagramLogin,
            'AllLogin' => $AllLogin,
            'facebookAppId' => $facebookAppId,
            'facebookAppKey' => $facebookAppKey,
            'googleAppId' => $googleAppId,
            'googleAppKey' => $googleAppKey,
            'twitterAppId' => $twitterAppId,
            'twitterAppKey' => $twitterAppKey,
            'linkedinAppId' => $linkedinAppId,
            'linkedinAppKey' => $linkedinAppKey,
            'VkontakteAppId' => $VkontakteAppId,
            'VkontakteAppKey' => $VkontakteAppKey,
            'instagramAppId' => $instagramAppId,
            'instagramAppkey' => $instagramAppkey
        );
        foreach ($data_array as $key => $value) {
            $saveSetting = Wo_SaveConfig($key, $value);
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'reset_windows_app_keys') {
        $app_key    = sha1(microtime());
        $data_array = array(
            'widnows_app_api_key' => $app_key
        );
        foreach ($data_array as $key => $value) {
            $saveSetting = Wo_SaveConfig($key, $value);
        }
        if ($saveSetting === true) {
            $data['status']  = 200;
            $data['app_key'] = $app_key;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_ref_system') {
        $saveSetting = false;
        if (!empty($_POST['affiliate_type'])) {
            $_POST['affiliate_type'] = 1;
        } else {
            $_POST['affiliate_type'] = 0;
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'mark_as_paid') {
        if (!empty($_GET['id']) && Wo_CheckSession($hash_id)) {
            $get_payment_info = Wo_GetPaymentHistory($_GET['id']);
            if (!empty($get_payment_info)) {
                $id     = $get_payment_info['id'];
                $update = mysqli_query($sqlConnect, "UPDATE " . T_A_REQUESTS . " SET status = '1' WHERE id = {$id}");
                if ($update) {
                    $body              = Wo_LoadPage('emails/payment-sent');
                    $body              = str_replace('{name}', $get_payment_info['user']['name'], $body);
                    $body              = str_replace('{amount}', $get_payment_info['amount'], $body);
                    $body              = str_replace('{site_name}', $config['siteName'], $body);
                    $send_message_data = array(
                        'from_email' => $wo['config']['siteEmail'],
                        'from_name' => $wo['config']['siteName'],
                        'to_email' => $get_payment_info['user']['email'],
                        'to_name' => $get_payment_info['user']['name'],
                        'subject' => 'New Payment | ' . $wo['config']['siteName'],
                        'charSet' => 'utf-8',
                        'message_body' => $body,
                        'is_html' => true
                    );
                    $send_message      = Wo_SendMessage($send_message_data);
                    if ($send_message) {
                        $data['status'] = 200;
                    }
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'decline_payment') {
        if (!empty($_GET['id']) && Wo_CheckSession($hash_id)) {
            $get_payment_info = Wo_GetPaymentHistory($_GET['id']);
            if (!empty($get_payment_info)) {
                $id     = $get_payment_info['id'];
                $update = mysqli_query($sqlConnect, "UPDATE " . T_A_REQUESTS . " SET status = '2' WHERE id = {$id}");
                if ($update) {
                    $body              = Wo_LoadPage('emails/payment-declined');
                    $body              = str_replace('{name}', $get_payment_info['user']['name'], $body);
                    $body              = str_replace('{amount}', $get_payment_info['amount'], $body);
                    $body              = str_replace('{site_name}', $config['siteName'], $body);
                    $send_message_data = array(
                        'from_email' => $wo['config']['siteEmail'],
                        'from_name' => $wo['config']['siteName'],
                        'to_email' => $get_payment_info['user']['email'],
                        'to_name' => $get_payment_info['user']['name'],
                        'subject' => 'Payment Declined | ' . $wo['config']['siteName'],
                        'charSet' => 'utf-8',
                        'message_body' => $body,
                        'is_html' => true
                    );
                    $send_message      = Wo_SendMessage($send_message_data);
                    if ($send_message) {
                        $data['status'] = 200;
                    }
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_page') {
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['page_name']) && !empty($_POST['page_content']) && !empty($_POST['page_title'])) {
            $page_name    = Wo_Secure($_POST['page_name']);
            $page_content = Wo_Secure($_POST['page_content']);
            $page_title   = Wo_Secure($_POST['page_title']);
            $page_type    = 0;
            if (!empty($_POST['page_type'])) {
                $page_type = 1;
            }
            if (!preg_match('/^[\w]+$/', $page_name)) {
                $data = array(
                    'status' => 400,
                    'message' => 'Invalid page name characters'
                );
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
            $data_ = array(
                'page_name' => $page_name,
                'page_content' => $page_content,
                'page_title' => $page_title,
                'page_type' => $page_type
            );
            $add   = Wo_RegisterNewPage($data_);
            if ($add) {
                $data['status'] = 200;
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'edit_page') {
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['page_id']) && !empty($_POST['page_name']) && !empty($_POST['page_content']) && !empty($_POST['page_title'])) {
            $page_name    = $_POST['page_name'];
            $page_content = $_POST['page_content'];
            $page_title   = $_POST['page_title'];
            $page_type    = 0;
            if (!empty($_POST['page_type'])) {
                $page_type = 1;
            }
            if (!preg_match('/^[\w]+$/', $page_name)) {
                $data = array(
                    'status' => 400,
                    'message' => 'Invalid page name characters'
                );
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
            $data_ = array(
                'page_name' => $page_name,
                'page_content' => $page_content,
                'page_title' => $page_title,
                'page_type' => $page_type
            );
            $add   = Wo_UpdateCustomPageData($_POST['page_id'], $data_);
            if ($add) {
                $data['status'] = 200;
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_field') {
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['name']) && !empty($_POST['type']) && !empty($_POST['description'])) {
            $type              = Wo_Secure($_POST['type']);
            $name              = Wo_Secure($_POST['name']);
            $description       = Wo_Secure($_POST['description']);
            $registration_page = 0;
            if (!empty($_POST['registration_page'])) {
                $registration_page = 1;
            }
            $profile_page = 0;
            if (!empty($_POST['profile_page'])) {
                $profile_page = 1;
            }
            $length = 32;
            if (!empty($_POST['length'])) {
                if (is_numeric($_POST['length']) && $_POST['length'] < 1001) {
                    $length = Wo_Secure($_POST['length']);
                }
            }
            $placement_array = array(
                'profile',
                'general',
                'social',
                'none'
            );
            $placement       = 'profile';
            if (!empty($_POST['placement'])) {
                if (in_array($_POST['placement'], $placement_array)) {
                    $placement = Wo_Secure($_POST['placement']);
                }
            }
            $data_ = array(
                'name' => $name,
                'description' => $description,
                'length' => $length,
                'placement' => $placement,
                'registration_page' => $registration_page,
                'profile_page' => $profile_page,
                'active' => 1
            );
            if (!empty($_POST['options'])) {
                $options              = @explode("\n", $_POST['options']);
                $type                 = Wo_Secure(implode($options, ','));
                $data_['select_type'] = 'yes';
            }
            $data_['type'] = $type;
            $add           = Wo_RegisterNewField($data_);
            if ($add) {
                $data['status'] = 200;
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'edit_field') {
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['name']) && !empty($_POST['description']) && !empty($_POST['id'])) {
            $name              = Wo_Secure($_POST['name']);
            $description       = Wo_Secure($_POST['description']);
            $registration_page = 0;
            if (!empty($_POST['registration_page'])) {
                $registration_page = 1;
            }
            $profile_page = 0;
            if (!empty($_POST['profile_page'])) {
                $profile_page = 1;
            }
            $active = 0;
            if (!empty($_POST['active'])) {
                $active = 1;
            }
            $length = 32;
            if (!empty($_POST['length'])) {
                if (is_numeric($_POST['length']) && $_POST['length'] < 1001) {
                    $length = Wo_Secure($_POST['length']);
                }
            }
            $placement_array = array(
                'profile',
                'general',
                'social',
                'none'
            );
            $placement       = 'profile';
            if (!empty($_POST['placement'])) {
                if (in_array($_POST['placement'], $placement_array)) {
                    $placement = Wo_Secure($_POST['placement']);
                }
            }
            $data_ = array(
                'name' => $name,
                'description' => $description,
                'length' => $length,
                'placement' => $placement,
                'registration_page' => $registration_page,
                'profile_page' => $profile_page,
                'active' => $active
            );
            if (!empty($_POST['options'])) {
                $options              = @explode("\n", $_POST['options']);
                $type                 = implode($options, ',');
                $data_['select_type'] = 'yes';
            }
            $add = Wo_UpdateField($_POST['id'], $data_);
            if ($add) {
                $data['status'] = 200;
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_field') {
        if (Wo_CheckMainSession($hash_id) === true && !empty($_GET['id'])) {
            $delete = Wo_DeleteField($_GET['id']);
            if ($delete) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_page') {
        if (Wo_CheckMainSession($hash_id) === true && !empty($_GET['id'])) {
            $delete = Wo_DeleteCustomPage($_GET['id']);
            if ($delete) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'new_backup') {
        $b = Wo_Backup($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);
        if ($b) {
            $data['status'] = 200;
            $data['date']   = date('d-m-Y');
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_paypal') {
        $PayPal               = Wo_PayPal();
        $data['status']       = 200;
        $data['respond_code'] = $PayPal;
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_general_setting' && Wo_CheckSession($hash_id) === true) {
        $saveSetting       = false;
        $cacheSystem       = 0;
        $chatSystem        = 0;
        $emailValidation   = 0;
        $sms_or_email      = 'mail';
        $emailNotification = 0;
        $seoLink           = 0;
        $fileSharing       = 0;
        $useSeoFrindly     = 0;
        $message_seen      = 0;
        $message_typing    = 0;
        $user_lastseen     = 0;
        $deleteAccount     = 0;
        $profileVisit      = 0;
        $online_sidebar    = 0;
        $profile_privacy   = 0;
        $video_upload      = 0;
        $audio_upload      = 0;
        $css_upload        = 0;
        $smooth_loading    = 0;
        $games             = 0;
        $pages             = 0;
        $groups            = 0;
        $developers_page   = 0;
        $user_registration = 0;
        $maintenance_mode  = 0;
        $classified        = 0;
        if (!empty($_POST['classified'])) {
            $classified = 1;
        }
        if (!empty($_POST['cacheSystem'])) {
            $cacheSystem = 1;
        }
        if (!empty($_POST['profile_privacy'])) {
            $profile_privacy = 1;
        }
        if (!empty($_POST['online_sidebar'])) {
            $online_sidebar = 1;
        }
        if (!empty($_POST['video_upload'])) {
            $video_upload = 1;
        }
        if (!empty($_POST['audio_upload'])) {
            $audio_upload = 1;
        }
        if (!empty($_POST['chatSystem'])) {
            $chatSystem = 1;
        }
        if (!empty($_POST['emailValidation'])) {
            $emailValidation = 1;
        }
        if (!empty($_POST['emailNotification'])) {
            $emailNotification = 1;
        }
        if (!empty($_POST['seoLink'])) {
            $seoLink = 1;
        }
        if (!empty($_POST['fileSharing'])) {
            $fileSharing = 1;
        }
        if (!empty($_POST['useSeoFrindly'])) {
            $useSeoFrindly = 1;
        }
        if (!empty($_POST['message_seen'])) {
            $message_seen = 1;
        }
        if (!empty($_POST['message_typing'])) {
            $message_typing = 1;
        }
        if (!empty($_POST['user_lastseen'])) {
            $user_lastseen = 1;
        }
        if (!empty($_POST['deleteAccount'])) {
            $deleteAccount = 1;
        }
        if (!empty($_POST['profileVisit'])) {
            $profileVisit = 1;
        }
        if (!empty($_POST['sms_or_email'])) {
            $sms_or_email = $_POST['sms_or_email'];
        }
        if (!empty($_POST['css_upload'])) {
            $css_upload = $_POST['css_upload'];
        }
        if (!empty($_POST['smooth_loading'])) {
            $smooth_loading = $_POST['smooth_loading'];
        }
        if (!empty($_POST['games'])) {
            $games = $_POST['games'];
        }
        if (!empty($_POST['pages'])) {
            $pages = $_POST['pages'];
        }
        if (!empty($_POST['groups'])) {
            $groups = $_POST['groups'];
        }
        if (!empty($_POST['developers_page'])) {
            $developers_page = $_POST['developers_page'];
        }
        if (!empty($_POST['user_registration'])) {
            $user_registration = $_POST['user_registration'];
        }
        if (!empty($_POST['maintenance_mode'])) {
            $maintenance_mode = $_POST['maintenance_mode'];
        }
        $saved_data = array(
            'cacheSystem' => $cacheSystem,
            'chatSystem' => $chatSystem,
            'emailValidation' => $emailValidation,
            'emailNotification' => $emailNotification,
            'seoLink' => $seoLink,
            'fileSharing' => $fileSharing,
            'useSeoFrindly' => $useSeoFrindly,
            'message_seen' => $message_seen,
            'message_typing' => $message_typing,
            'user_lastseen' => $user_lastseen,
            'deleteAccount' => $deleteAccount,
            'profileVisit' => $profileVisit,
            'online_sidebar' => $online_sidebar,
            'profile_privacy' => $profile_privacy,
            'video_upload' => $video_upload,
            'audio_upload' => $audio_upload,
            'sms_or_email' => $sms_or_email,
            'css_upload' => $css_upload,
            'smooth_loading' => $smooth_loading,
            'games' => $games,
            'groups' => $groups,
            'pages' => $pages,
            'developers_page' => $developers_page,
            'user_registration' => $user_registration,
            'maintenance_mode' => $maintenance_mode,
            'classified' => $classified
        );
        foreach ($saved_data as $key => $value) {
            $saveSetting = Wo_SaveConfig($key, $value);
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_s3') {

        try {
            $s3Client = S3Client::factory(array(
                'version'    => 'latest',
                'region'      => $wo['config']['region'],
                'credentials' => array(
                    'key'    => $wo['config']['amazone_s3_key'],
                    'secret' => $wo['config']['amazone_s3_s_key'],
                )
            ));
            $buckets = $s3Client->listBuckets();
            if (!empty($buckets)) {
                if ($s3Client->doesBucketExist($wo['config']['bucket_name'])) {
                    $data['status'] = 200;
                    $array = array(
                        'upload/photos/d-avatar.jpg',
                        'upload/photos/d-cover.jpg',
                        'upload/photos/d-group.jpg',
                        'upload/photos/d-page.jpg',
                        'upload/photos/game-icon.png',
                    );
                    foreach ($array as $key => $value) {
                        $upload = Wo_UploadToS3($value, array('delete' => 'no'));
                    }
                } else {
                    $data['status'] = 300;
                } 
            } else {
                $data['status'] = 500;
            }
        } catch(Exception $e) {
            $data['status'] = 400;
            $data['message'] = $e->getMessage();
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_site_setting' && Wo_CheckSession($hash_id) === true) {
        $saveSetting = false;
        if (!empty($_POST['reCaptcha'])) {
            $_POST['reCaptcha'] = 1;
        } else {
            $_POST['reCaptcha'] = 0;
        }
        if (!empty($_POST['amazone_s3'])) {
            $_POST['amazone_s3'] = 1;
        } else {
            $_POST['amazone_s3'] = 0;
        }
        $delete_follow_table = 0;
        if (isset($_POST['connectivitySystem'])) {
            if ($config['connectivitySystem'] == 1 && $_POST['connectivitySystem'] != 1) {
                $delete_follow_table = 1;
            } else if ($config['connectivitySystem'] != 1 && $_POST['connectivitySystem'] == 1) {
                $delete_follow_table = 1;
            }
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            if ($delete_follow_table == 1) {
                mysqli_query($sqlConnect, "DELETE FROM " . T_FOLLOWERS);
                mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE type='following'");
            }
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'upload_files') {
        $files = glob_recursive('upload/*');
        $upload_s3 = false;
        foreach ($files as $key => $value) {
            if (!is_dir($value)) {
                $upload_s3 = Wo_UploadToS3($value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_pro_setting' && Wo_CheckSession($hash_id) === true) {
        $saveSetting = false;
        if (!empty($_POST['pro'])) {
            $_POST['pro'] = 1;
        } else {
            $_POST['pro'] = 0;
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_terms_setting' && Wo_CheckSession($hash_id) === true) {
        $saveSetting = false;
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveTerm($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_email_setting' && Wo_CheckSession($hash_id) === true) {
        $saveSetting = false;
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_message') {
        $send_message_data = array(
            'from_email' => $wo['config']['siteEmail'],
            'from_name' => $wo['config']['siteName'],
            'to_email' => $wo['user']['email'],
            'to_name' => $wo['user']['name'],
            'subject' => 'Test Message From ' . $wo['config']['siteName'],
            'charSet' => 'utf-8',
            'message_body' => 'If you can see this message, then your SMTP configuration is working fine.',
            'is_html' => false
        );
        $send_message      = Wo_SendMessage($send_message_data);
        if ($send_message === true) {
            $data['status'] = 200;
        } else {
            $data['status'] = 400;
            $data['error']  = $mail->ErrorInfo;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_sms_setting' && Wo_CheckSession($hash_id) === true) {
        $saveSetting = false;
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_sms_message') {
        $message      = 'This is a test message from ' . $wo['config']['siteName'];
        $send_message = Wo_SendSMSMessage($wo['config']['sms_phone_number'], $message);
        if ($send_message === true) {
            $data['status'] = 200;
        } else {
            $data['status'] = 400;
            $data['error']  = $send_message;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_design_setting' && Wo_CheckSession($hash_id) === true) {
        $saveSetting = false;
        if (isset($_FILES['logo']['name'])) {
            $fileInfo = array(
                'file' => $_FILES["logo"]["tmp_name"],
                'name' => $_FILES['logo']['name'],
                'size' => $_FILES["logo"]["size"]
            );
            $media    = Wo_UploadLogo($fileInfo);
        }
        if (isset($_FILES['background']['name'])) {
            $fileInfo = array(
                'file' => $_FILES["background"]["tmp_name"],
                'name' => $_FILES['background']['name'],
                'size' => $_FILES["background"]["size"]
            );
            $media    = Wo_UploadBackground($fileInfo);
        }
        if (isset($_FILES['favicon']['name'])) {
            $fileInfo = array(
                'file' => $_FILES["favicon"]["tmp_name"],
                'name' => $_FILES['favicon']['name'],
                'size' => $_FILES["favicon"]["size"]
            );
            $media    = Wo_UploadFavicon($fileInfo);
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_reCaptcha_setting' && isset($_POST['reCaptcha'])) {
        $saveSetting = false;
        foreach ($_POST as $key => $value) {
            $saveSetting = Wo_SaveConfig($key, $value);
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'updateTheme' && isset($_POST['theme']) && Wo_CheckSession($hash_id) === true) {
        $saveSetting = false;
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_user' && isset($_GET['user_id']) && Wo_CheckSession($hash_id) === true) {
        if (Wo_DeleteUser($_GET['user_id']) === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_user_page' && isset($_GET['page_id']) && Wo_CheckSession($hash_id) === true) {
        if (Wo_DeletePage($_GET['page_id']) === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_group' && isset($_GET['group_id']) && Wo_CheckSession($hash_id) === true) {
        if (Wo_DeleteGroup($_GET['group_id']) === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'filter_all_users') {
        $html  = '';
        $after = (isset($_GET['after_user_id']) && is_numeric($_GET['after_user_id']) && $_GET['after_user_id'] > 0) ? $_GET['after_user_id'] : 0;
        foreach (Wo_GetAllUsers(20, 'ManageUsers', $_POST, $after) as $wo['userlist']) {
            $html .= Wo_LoadPage('admin/manage_users/users-list');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_more_pages') {
        $html  = '';
        $after = (isset($_GET['after_page_id']) && is_numeric($_GET['after_page_id']) && $_GET['after_page_id'] > 0) ? $_GET['after_page_id'] : 0;
        foreach (Wo_GetAllPages(20, $after) as $wo['pagelist']) {
            $html .= Wo_LoadPage('admin/manage_pages/pages-list');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_more_groups') {
        $html  = '';
        $after = (isset($_GET['after_group_id']) && is_numeric($_GET['after_group_id']) && $_GET['after_group_id'] > 0) ? $_GET['after_group_id'] : 0;
        foreach (Wo_GetAllGroups(20, $after) as $wo['grouplist']) {
            $html .= Wo_LoadPage('admin/manage_groups/groups-list');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'clear_cache_folder') {
        Wo_ClearCache();
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_cache_folder_size') {
        $html = Wo_SizeFormat(Wo_FolderSize('cache'));
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_users_setting' && isset($_POST['user_lastseen'])) {
        $delete_follow_table = 0;
        $saveSetting         = false;
        foreach ($_POST as $key => $value) {
            $saveSetting = Wo_SaveConfig($key, $value);
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_more_posts') {
        $html      = '';
        $postsData = array(
            'limit' => 10,
            'after_post_id' => Wo_Secure($_GET['after_post_id'])
        );
        foreach (Wo_GetAllPosts($postsData) as $wo['story']) {
            $html .= Wo_LoadPage('admin/manage_posts/posts-list');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_post' && Wo_CheckSession($hash_id) === true) {
        if (!empty($_POST['post_id'])) {
            if (Wo_DeletePost($_POST['post_id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_google_analytics_code') {
        if (isset($_POST['googleAnalytics'])) {
            $saveSetting = false;
            foreach ($_POST as $key => $value) {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
            if ($saveSetting === true) {
                $data['status'] = 200;
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_reported_post') {
        if (!empty($_GET['post_id'])) {
            if (Wo_DeletePost($_GET['post_id']) === true) {
                $deleteReport = Wo_DeleteReport($_GET['report_id']);
                if ($deleteReport === true) {
                    $data = array(
                        'status' => 200,
                        'html' => Wo_CountUnseenReports()
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'mark_as_safe') {
        if (!empty($_GET['report_id'])) {
            $deleteReport = Wo_DeleteReport($_GET['report_id']);
            if ($deleteReport === true) {
                $data = array(
                    'status' => 200,
                    'html' => Wo_CountUnseenReports()
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_verification') {
        if (!empty($_GET['id'])) {
            if (Wo_DeleteVerificationRequest($_GET['id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_game') {
        if (!empty($_GET['game_id'])) {
            if (Wo_DeleteGame($_GET['game_id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'verify_user') {
        if (!empty($_GET['id'])) {
            $type = '';
            if (!empty($_GET['type'])) {
                $type = $_GET['type'];
            }
            if (Wo_VerifyUser($_GET['id'], $_GET['verification_id'], $type) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'send_mail_to_all_users') {
        $isset_test = 'off';
        if (empty($_POST['message']) || empty($_POST['subject'])) {
            $send_errors = $error_icon . $wo['lang']['please_check_details'];
        } else {
            if (!empty($_POST['test_message'])) {
                if ($_POST['test_message'] == 'on') {
                    $isset_test = 'on';
                }
            }
            if ($isset_test == 'on') {
                $send_message_data = array(
                    'from_email' => $wo['config']['siteEmail'],
                    'from_name' => $wo['config']['siteName'],
                    'to_email' => $wo['user']['email'],
                    'to_name' => $wo['user']['name'],
                    'subject' => $_POST['subject'],
                    'charSet' => 'utf-8',
                    'message_body' => $_POST['message'],
                    'is_html' => true
                );
                $send              = Wo_SendMessage($send_message_data);
            } else {
                $users_type = 'all';
                if ($_POST['send_to'] == 'active') {
                    $users_type = 'active';
                } else if ($_POST['send_to'] == 'inactive') {
                    $users_type = 'inactive';
                }
                $users = Wo_GetAllUsersByType($users_type);
                foreach ($users as $user) {
                    $send_message_data = array(
                        'from_email' => $wo['config']['siteEmail'],
                        'from_name' => $wo['config']['siteName'],
                        'to_email' => $user['email'],
                        'to_name' => $user['name'],
                        'subject' => $_POST['subject'],
                        'charSet' => 'utf-8',
                        'message_body' => $_POST['message'],
                        'is_html' => true
                    );
                    $send              = Wo_SendMessage($send_message_data);
                    $mail->ClearAddresses();
                }
            }
        }
        header("Content-type: application/json");
        if (!empty($send_errors)) {
            $send_errors_data = array(
                'status' => 400,
                'message' => $send_errors
            );
            echo json_encode($send_errors_data);
        } else {
            $data = array(
                'status' => 200
            );
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'add_new_announcement') {
        if (!empty($_POST['announcement_text'])) {
            $html = '';
            $id   = Wo_AddNewAnnouncement($_POST['announcement_text']);
            if ($id > 0) {
                $wo['activeAnnouncement'] = Wo_GetAnnouncement($id);
                $html .= Wo_LoadPage('admin/announcement/active-list');
                $data = array(
                    'status' => 200,
                    'text' => $html
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_announcement') {
        if (!empty($_GET['id'])) {
            $DeleteAnnouncement = Wo_DeleteAnnouncement($_GET['id']);
            if ($DeleteAnnouncement === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'disable_announcement') {
        if (!empty($_GET['id'])) {
            $html                = '';
            $DisableAnnouncement = Wo_DisableAnnouncement($_GET['id']);
            if ($DisableAnnouncement === true) {
                $wo['inactiveAnnouncement'] = Wo_GetAnnouncement($_GET['id']);
                $html .= Wo_LoadPage('admin/announcement/inactive-list');
                $data = array(
                    'status' => 200,
                    'html' => $html
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'activate_announcement') {
        if (!empty($_GET['id'])) {
            $html                 = '';
            $ActivateAnnouncement = Wo_ActivateAnnouncement($_GET['id']);
            if ($ActivateAnnouncement === true) {
                $wo['activeAnnouncement'] = Wo_GetAnnouncement($_GET['id']);
                $html .= Wo_LoadPage('admin/announcement/active-list');
                $data = array(
                    'status' => 200,
                    'html' => $html
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_ads') {
        if (!empty($_POST['type']) && !empty($_POST['code'])) {
            $ad_data = array(
                'type' => $_POST['type'],
                'code' => $_POST['code']
            );
            if (Wo_UpdateAdsCode($ad_data)) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_ads_status') {
        if (!empty($_GET['type'])) {
            if (Wo_UpdateAdActivation($_GET['type']) == 'active') {
                $data = array(
                    'status' => 200
                );
            } else {
                $data = array(
                    'status' => 300
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
if ($f == 'get_following_users') {
    $html = '';
    if (!empty($_GET['user_id'])) {
        foreach (Wo_GetFollowing($_GET['user_id'], 'sidebar', 12) as $wo['UsersList']) {
            $wo['UsersList']['user_name'] = $wo['UsersList']['name'];
            if (!empty($wo['UsersList']['last_name'])) {
                $wo['UsersList']['user_name'] = $wo['UsersList']['first_name'];
            }
            $html .= Wo_LoadPage('sidebar/profile-sidebar-user-list');
        }
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_followers_users') {
    $html = '';
    if (!empty($_GET['user_id'])) {
        foreach (Wo_GetFollowers($_GET['user_id'], 'sidebar', 12) as $wo['UsersList']) {
            $wo['UsersList']['user_name'] = $wo['UsersList']['name'];
            if (!empty($wo['UsersList']['last_name'])) {
                $wo['UsersList']['user_name'] = $wo['UsersList']['first_name'];
            }
            $html .= Wo_LoadPage('sidebar/profile-sidebar-user-list');
        }
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'posts') {
    if ($s == 'fetch_url') {
        if (isset($_POST["url"])) {
            $get_url = $_POST["url"];
            include_once("assets/import/simple_html_dom.inc.php");
            $get_content = file_get_html($get_url);
            foreach ($get_content->find('title') as $element) {
                @$page_title = $element->plaintext;
            }
            if (empty($page_title)) {
                $page_title = '';
            }
            @$page_body = $get_content->find("meta[name='description']", 0)->content;
            $page_body = mb_substr($page_body, 0, 250, "utf-8");
            if ($page_body === false) {
                $page_body = '';
            }
            if (empty($page_body)) {
                @$page_body = $get_content->find("meta[property='og:description']", 0)->content;
                $page_body = mb_substr($page_body, 0, 250, "utf-8");
                if ($page_body === false) {
                    $page_body = '';
                }
            }
            $image_urls = array();
            @$page_image = $get_content->find("meta[property='og:image']", 0)->content;
            if (!empty($page_image)) {
                if (preg_match('/[\w\-]+\.(jpg|png|gif|jpeg)/', $page_image)) {
                    $image_urls[] = $page_image;
                }
            } else {
                foreach ($get_content->find('img') as $element) {
                    if (!preg_match('/blank.(.*)/i', $element->src)) {
                        if (preg_match('/[\w\-]+\.(jpg|png|gif|jpeg)/', $element->src)) {
                            $image_urls[] = $element->src;
                        }
                    }
                }
            }
            $output = array(
                'title' => $page_title,
                'images' => $image_urls,
                'content' => $page_body,
                'url' => $_POST["url"]
            );
            echo json_encode($output);
            exit();
        }
    }
    if ($s == 'search_for_posts') {
        $html = '';
        if (!empty($_GET['search_query'])) {
            $search_data = Wo_SearchForPosts($_GET['id'], $_GET['search_query'], 20, $_GET['type']);
            if (count($search_data) == 0) {
                $html = Wo_LoadPage('story/filter-no-stories-found');
            } else {
                foreach ($search_data as $wo['story']) {
                    $html .= Wo_LoadPage('story/content');
                }
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'insert_new_post') {
        $media         = '';
        $mediaFilename = '';
        $mediaName     = '';
        $html          = '';
        $recipient_id  = 0;
        $page_id       = 0;
        $group_id      = 0;
        $image_array   = array();
        if (Wo_CheckSession($hash_id) === false) {
            return false;
            die();
        }
        if (isset($_POST['recipient_id']) && !empty($_POST['recipient_id'])) {
            $recipient_id = Wo_Secure($_POST['recipient_id']);
        } else if (isset($_POST['page_id']) && !empty($_POST['page_id'])) {
            $page_id = Wo_Secure($_POST['page_id']);
        } else if (isset($_POST['group_id']) && !empty($_POST['group_id'])) {
            $group_id = Wo_Secure($_POST['group_id']);
            $group    = Wo_GroupData($group_id);
            if (!empty($group['id'])) {
                if ($group['privacy'] == 1) {
                    $_POST['postPrivacy'] = 0;
                } else if ($group['privacy'] == 2) {
                    $_POST['postPrivacy'] = 2;
                }
            }
        }
        if (isset($_FILES['postFile']['name'])) {
            $fileInfo = array(
                'file' => $_FILES["postFile"]["tmp_name"],
                'name' => $_FILES['postFile']['name'],
                'size' => $_FILES["postFile"]["size"],
                'type' => $_FILES["postFile"]["type"]
            );
            $media    = Wo_ShareFile($fileInfo);
            if (!empty($media)) {
                $mediaFilename = $media['filename'];
                $mediaName     = $media['name'];
            }
        }
        if (isset($_FILES['postVideo']['name']) && empty($mediaFilename)) {
            $fileInfo = array(
                'file' => $_FILES["postVideo"]["tmp_name"],
                'name' => $_FILES['postVideo']['name'],
                'size' => $_FILES["postVideo"]["size"],
                'type' => $_FILES["postVideo"]["type"],
                'types' => 'mp4,m4v,webm,flv,mov,mpeg'
            );
            $media    = Wo_ShareFile($fileInfo);
            if (!empty($media)) {
                $mediaFilename = $media['filename'];
                $mediaName     = $media['name'];
            }
        }
        if (isset($_FILES['postMusic']['name']) && empty($mediaFilename)) {
            $fileInfo = array(
                'file' => $_FILES["postMusic"]["tmp_name"],
                'name' => $_FILES['postMusic']['name'],
                'size' => $_FILES["postMusic"]["size"],
                'type' => $_FILES["postMusic"]["type"],
                'types' => 'mp3,wav'
            );
            $media    = Wo_ShareFile($fileInfo);
            if (!empty($media)) {
                $mediaFilename = $media['filename'];
                $mediaName     = $media['name'];
            }
        }
        $multi = 0;
        if (isset($_FILES['postPhotos']['name']) && empty($mediaFilename) && empty($_POST['album_name'])) {
            if (count($_FILES['postPhotos']['name']) == 1) {
                $fileInfo = array(
                    'file' => $_FILES["postPhotos"]["tmp_name"][0],
                    'name' => $_FILES['postPhotos']['name'][0],
                    'size' => $_FILES["postPhotos"]["size"][0],
                    'type' => $_FILES["postPhotos"]["type"][0]
                );
                $media    = Wo_ShareFile($fileInfo);
                if (!empty($media)) {
                    $mediaFilename = $media['filename'];
                    $mediaName     = $media['name'];
                }
            } else {
                $multi = 1;
            }
        }
        if (empty($_POST['postPrivacy'])) {
            $_POST['postPrivacy'] = 0;
        }
        $post_privacy  = 0;
        $privacy_array = array(
            '0',
            '1',
            '2',
            '3'
        );
        if (isset($_POST['postPrivacy'])) {
            if (in_array($_POST['postPrivacy'], $privacy_array)) {
                $post_privacy = $_POST['postPrivacy'];
            }
        }
        $import_url_image = '';
        $url_link         = '';
        $url_content      = '';
        $url_title        = '';
        if (!empty($_POST['url_link']) && !empty($_POST['url_title'])) {
            $url_link  = $_POST['url_link'];
            $url_title = $_POST['url_title'];
            if (!empty($_POST['url_content'])) {
                $url_content = $_POST['url_content'];
            }
            if (!empty($_POST['url_image'])) {
                $import_url_image = @Wo_ImportImageFromUrl($_POST['url_image']);
            }
        }
        $post_text = '';
        $post_map  = '';
        if (!empty($_POST['postText']) && !ctype_space($_POST['postText'])) {
            $post_text = $_POST['postText'];
        }
        if (!empty($_POST['postMap'])) {
            $post_map = $_POST['postMap'];
        }
        $album_name = '';
        if (!empty($_POST['album_name'])) {
            $album_name = $_POST['album_name'];
        }
        if (!isset($_FILES['postPhotos']['name'])) {
            $album_name = '';
        }
        $traveling = '';
        $watching  = '';
        $playing   = '';
        $listening = '';
        $feeling   = '';
        if (!empty($_POST['feeling_type'])) {
            $array_types = array(
                'feelings',
                'traveling',
                'watching',
                'playing',
                'listening'
            );
            if (in_array($_POST['feeling_type'], $array_types)) {
                if ($_POST['feeling_type'] == 'feelings') {
                    if (!empty($_POST['feeling'])) {
                        if (array_key_exists($_POST['feeling'], $wo['feelingIcons'])) {
                            $feeling = $_POST['feeling'];
                        }
                    }
                } else if ($_POST['feeling_type'] == 'traveling') {
                    if (!empty($_POST['feeling'])) {
                        $traveling = $_POST['feeling'];
                    }
                } else if ($_POST['feeling_type'] == 'watching') {
                    if (!empty($_POST['feeling'])) {
                        $watching = $_POST['feeling'];
                    }
                } else if ($_POST['feeling_type'] == 'playing') {
                    if (!empty($_POST['feeling'])) {
                        $playing = $_POST['feeling'];
                    }
                } else if ($_POST['feeling_type'] == 'listening') {
                    if (!empty($_POST['feeling'])) {
                        $listening = $_POST['feeling'];
                    }
                }
            }
        }
        if (isset($_FILES['postPhotos']['name'])) {
            $allowed = array(
                'gif',
                'png',
                'jpg',
                'jpeg'
            );
            for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                $new_string = pathinfo($_FILES['postPhotos']['name'][$i]);
                if (!in_array(strtolower($new_string['extension']), $allowed)) {
                    $errors[] = $error_icon . $wo['lang']['please_check_details'];
                }
            }
        }
        if (!empty($_POST['answer']) && array_filter($_POST['answer'])) {
            if (!empty($_POST['postText'])) {
                foreach ($_POST['answer'] as $key => $value) {
                    if (empty($value) || ctype_space($value)) {
                        $errors = 'Answer #' . ($key + 1) . ' is empty.';
                    }
                }
            } else {
                $errors = 'Please write the question.';
            }
        }
        if (empty($errors)) {
            $is_option = false;
            if (!empty($_POST['answer']) && array_filter($_POST['answer'])) {
                $is_option = true;
            }
            $post_data = array(
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'page_id' => Wo_Secure($page_id),
                'group_id' => Wo_Secure($group_id),
                'postText' => Wo_Secure($post_text),
                'recipient_id' => Wo_Secure($recipient_id),
                'postFile' => Wo_Secure($mediaFilename, 0),
                'postFileName' => Wo_Secure($mediaName),
                'postMap' => Wo_Secure($post_map),
                'postPrivacy' => Wo_Secure($post_privacy),
                'postLinkTitle' => Wo_Secure($url_title),
                'postLinkContent' => Wo_Secure($url_content),
                'postLink' => Wo_Secure($url_link),
                'postLinkImage' => Wo_Secure($import_url_image, 0),
                'album_name' => Wo_Secure($album_name),
                'multi_image' => Wo_Secure($multi),
                'postFeeling' => Wo_Secure($feeling),
                'postListening' => Wo_Secure($listening),
                'postPlaying' => Wo_Secure($playing),
                'postWatching' => Wo_Secure($watching),
                'postTraveling' => Wo_Secure($traveling),
                'time' => time()
            );
            if (!empty($is_option)) {
                $post_data['poll_id'] = 1;
            }
            $id = Wo_RegisterPost($post_data);
            if ($id) {
                if ($is_option == true) {
                    foreach ($_POST['answer'] as $key => $value) {
                        $add_opition = Wo_AddOption($id, $value);
                    }
                }
                if (isset($_FILES['postPhotos']['name'])) {
                    if (count($_FILES['postPhotos']['name']) > 0) {
                        for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                            $fileInfo = array(
                                'file' => $_FILES["postPhotos"]["tmp_name"][$i],
                                'name' => $_FILES['postPhotos']['name'][$i],
                                'size' => $_FILES["postPhotos"]["size"][$i],
                                'type' => $_FILES["postPhotos"]["type"][$i],
                                'types' => 'jpg,png,jpeg,gif'
                            );
                            $file     = Wo_ShareFile($fileInfo, 1);
                            if (!empty($file)) {
                                $media_album = Wo_RegisterAlbumMedia($id, $file['filename']);
                            }
                        }
                    }
                }
                $wo['story'] = Wo_PostData($id);
                $html .= Wo_LoadPage('story/content');
                $data = array(
                    'status' => 200,
                    'html' => $html
                );
            }
        } else {
            header("Content-type: application/json");
            echo json_encode(array(
                'status' => 400,
                'errors' => $errors
            ));
            exit();
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_post' && Wo_CheckMainSession($hash_id) === true) {
        if (!empty($_GET['post_id'])) {
            if (Wo_DeletePost($_GET['post_id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_new_posts') {
        if (!empty($_GET['before_post_id']) && isset($_GET['user_id'])) {
            $html      = '';
            $postsData = array(
                'before_post_id' => $_GET['before_post_id'],
                'publisher_id' => $_GET['user_id'],
                'limit' => 20
            );
            $posts     = Wo_GetPosts($postsData);
            foreach ($posts as $wo['story']) {
                echo Wo_LoadPage('story/content');
            }
        }
        exit();
    }
    if ($s == 'load_more_posts') {
        $html = '';
        if (!empty($_GET['filter_by_more']) && !empty($_GET['after_post_id'])) {
            $page_id  = 0;
            $group_id = 0;
            $user_id  = 0;
            if (!empty($_GET['page_id']) && $_GET['page_id'] > 0) {
                $page_id = Wo_Secure($_GET['page_id']);
            }
            if (!empty($_GET['group_id']) && $_GET['group_id'] > 0) {
                $group_id = Wo_Secure($_GET['group_id']);
            }
            if (!empty($_GET['user_id']) && $_GET['user_id'] > 0) {
                $user_id = Wo_Secure($_GET['user_id']);
            }
            $postsData = array(
                'filter_by' => Wo_Secure($_GET['filter_by_more']),
                'limit' => 6,
                'publisher_id' => $user_id,
                'group_id' => $group_id,
                'page_id' => $page_id,
                'after_post_id' => Wo_Secure($_GET['after_post_id'])
            );
            foreach (Wo_GetPosts($postsData) as $wo['story']) {
                echo Wo_LoadPage('story/content');
            }
            if (!empty($_GET['posts_count'])) {
                if ($_GET['posts_count'] > 9 && $_GET['posts_count'] < 15) {
                    echo Wo_GetAd('post_first', false);
                } else if ($_GET['posts_count'] > 20 && $_GET['posts_count'] < 28) {
                    echo Wo_GetAd('post_second', false);
                } else if ($_GET['posts_count'] > 29) {
                    echo Wo_GetAd('post_third', false);
                }
            }
        }
        exit();
    }
    if ($s == 'edit_post') {
        if (!empty($_POST['post_id']) && !empty($_POST['text'])) {
            $updatePost = Wo_UpdatePost(array(
                'post_id' => $_POST['post_id'],
                'text' => $_POST['text']
            ));
            if (!empty($updatePost)) {
                $data = array(
                    'status' => 200,
                    'html' => $updatePost
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == "update_post_privacy") {
        if (!empty($_GET['post_id']) && isset($_GET['privacy_type']) && Wo_CheckMainSession($hash_id) === true) {
            $updatePost = Wo_UpdatePostPrivacy(array(
                'post_id' => Wo_Secure($_GET['post_id']),
                'privacy_type' => Wo_Secure($_GET['privacy_type'])
            ));
            if (isset($updatePost)) {
                $data = array(
                    'status' => 200,
                    'privacy_type' => $updatePost
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_like') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddLikes($_GET['post_id']) == 'unliked') {
                $data = array(
                    'status' => 300,
                    'likes' => Wo_CountLikes($_GET['post_id']),
                    'like_lang' => $wo['lang']['like']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'likes' => Wo_CountLikes($_GET['post_id']),
                    'like_lang' => $wo['lang']['liked']
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike']              = 1;
                $data['default_lang_like']    = $wo['lang']['like'];
                $data['default_lang_dislike'] = $wo['lang']['dislike'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_wonder') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddWonders($_GET['post_id']) == 'unwonder') {
                $data                = array(
                    'status' => 300,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountWonders($_GET['post_id'])
                );
                $data['wonder_lang'] = ($config['second_post_button'] == 'dislike') ? $wo['lang']['dislike'] : $wo['lang']['wonder'];
            } else {
                $data                = array(
                    'status' => 200,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountWonders($_GET['post_id'])
                );
                $data['wonder_lang'] = ($config['second_post_button'] == 'dislike') ? $wo['lang']['disliked'] : $wo['lang']['wondered'];
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike']              = 1;
                $data['default_lang_like']    = $wo['lang']['like'];
                $data['default_lang_dislike'] = $wo['lang']['dislike'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_share') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddShare($_GET['post_id']) == 'unshare') {
                $data = array(
                    'status' => 300,
                    'shares' => Wo_CountShares($_GET['post_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'shares' => Wo_CountShares($_GET['post_id'])
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment') {
        if (!empty($_POST['post_id']) && isset($_POST['text']) && Wo_CheckMainSession($hash_id) === true) {
            $html    = '';
            $page_id = '';
            if (!empty($_POST['page_id'])) {
                $page_id = $_POST['page_id'];
            }
            $comment_image = '';
            if (!empty($_POST['comment_image'])) {
                $comment_image = $_POST['comment_image'];
            }
            if (empty($comment_image) && empty($_POST['text'])) {
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
            $text_comment = '';
            if (!empty($_POST['text']) && !ctype_space($_POST['text'])) {
                $text_comment = $_POST['text'];
            }
            $C_Data        = array(
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'page_id' => Wo_Secure($page_id),
                'post_id' => Wo_Secure($_POST['post_id']),
                'text' => Wo_Secure($_POST['text']),
                'c_file' => Wo_Secure($comment_image),
                'time' => time()
            );
            $R_Comment     = Wo_RegisterPostComment($C_Data);
            $wo['comment'] = Wo_GetPostComment($R_Comment);
            $wo['story']   = Wo_PostData($_POST['post_id']);
            if (!empty($wo['comment'])) {
                $html = Wo_LoadPage('comment/content');
                $data = array(
                    'status' => 200,
                    'html' => $html,
                    'comments_num' => Wo_CountPostComment($_POST['post_id'])
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_reply') {
        if (!empty($_POST['comment_id']) && !empty($_POST['text']) && Wo_CheckMainSession($hash_id) === true) {
            $html    = '';
            $page_id = '';
            if (!empty($_POST['page_id'])) {
                $page_id = $_POST['page_id'];
            }
            $C_Data      = array(
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'page_id' => Wo_Secure($page_id),
                'comment_id' => Wo_Secure($_POST['comment_id']),
                'text' => Wo_Secure($_POST['text']),
                'time' => time()
            );
            $R_Comment   = Wo_RegisterCommentReply($C_Data);
            $wo['reply'] = Wo_GetCommentReply($R_Comment);
            if (!empty($wo['reply'])) {
                $html = Wo_LoadPage('comment/replies-content');
                $data = array(
                    'status' => 200,
                    'html' => $html,
                    'replies_num' => Wo_CountCommentReplies($_POST['comment_id'])
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_comment') {
        if (!empty($_GET['comment_id']) && Wo_CheckMainSession($hash_id) === true) {
            $DeleteComment = Wo_DeletePostComment($_GET['comment_id']);
            if ($DeleteComment === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_comment_reply') {
        if (!empty($_GET['reply_id']) && Wo_CheckMainSession($hash_id) === true) {
            $DeleteComment = Wo_DeletePostReplyComment($_GET['reply_id']);
            if ($DeleteComment === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_more_comments') {
        if (!empty($_GET['post_id'])) {
            $html        = '';
            $wo['story'] = Wo_PostData($_GET['post_id']);
            foreach (Wo_GetPostComments($_GET['post_id'], Wo_CountPostComment($_GET['post_id'])) as $wo['comment']) {
                $html .= Wo_LoadPage('comment/content');
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_more_replies') {
        if (!empty($_GET['comment_id'])) {
            $html = '';
            foreach (Wo_GetCommentReplies($_GET['comment_id'], Wo_CountCommentReplies($_GET['comment_id'])) as $wo['reply']) {
                $html .= Wo_LoadPage('comment/replies-content');
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'edit_comment') {
        if (!empty($_POST['comment_id']) && !empty($_POST['text']) && Wo_CheckMainSession($hash_id) === true) {
            $updateComment = Wo_UpdateComment(array(
                'comment_id' => $_POST['comment_id'],
                'text' => $_POST['text']
            ));
            if (!empty($updateComment)) {
                $data = array(
                    'status' => 200,
                    'html' => $updateComment
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment_like') {
        if (!empty($_POST['comment_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddCommentLikes($_POST['comment_id'], $_POST['comment_text']) == 'unliked') {
                $data = array(
                    'status' => 300,
                    'likes' => Wo_CountCommentLikes($_POST['comment_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'likes' => Wo_CountCommentLikes($_POST['comment_id'])
                );
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike']   = 1;
                $data['wonders_c'] = Wo_CountCommentWonders($_POST['comment_id']);
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment_wonder') {
        if (!empty($_POST['comment_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddCommentWonders($_POST['comment_id'], $_POST['comment_text']) == 'unwonder') {
                $data = array(
                    'status' => 300,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountCommentWonders($_POST['comment_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountCommentWonders($_POST['comment_id'])
                );
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike'] = 1;
                $data['likes_c'] = Wo_CountCommentLikes($_POST['comment_id']);
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment_reply_like') {
        if (!empty($_POST['reply_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddCommentReplyLikes($_POST['reply_id'], $_POST['comment_text']) == 'unliked') {
                $data = array(
                    'status' => 300,
                    'likes' => Wo_CountCommentReplyLikes($_POST['reply_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'likes' => Wo_CountCommentReplyLikes($_POST['reply_id'])
                );
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike']   = 1;
                $data['wonders_r'] = Wo_CountCommentReplyWonders($_POST['reply_id']);
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment_reply_wonder') {
        if (!empty($_POST['reply_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddCommentReplyWonders($_POST['reply_id'], $_POST['comment_text']) == 'unwonder') {
                $data = array(
                    'status' => 300,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountCommentReplyWonders($_POST['reply_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountCommentReplyWonders($_POST['reply_id'])
                );
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike'] = 1;
                $data['likes_r'] = Wo_CountCommentReplyLikes($_POST['reply_id']);
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'save_post') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            $post_data = array(
                'post_id' => $_GET['post_id']
            );
            if (Wo_SavePosts($post_data) == 'unsaved') {
                $data = array(
                    'status' => 300,
                    'text' => $wo['lang']['save_post']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['unsave_post']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'pin_post') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            $type = 'profile';
            $id   = 0;
            if (!empty($_GET['type'])) {
                $types_array = array(
                    'profile',
                    'page',
                    'group'
                );
                if (in_array($_GET['type'], $types_array)) {
                    $type = $_GET['type'];
                }
            }
            if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
                $id = $_GET['id'];
            }
            if (Wo_PinPost($_GET['post_id'], $type, $id) == 'unpin') {
                $data = array(
                    'status' => 300,
                    'text' => $wo['lang']['pin_post']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['unpin_post']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'boost_post') {
        if (!empty($_GET['post_id']) && $wo['config']['pro'] == 1 && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_BoostPost($_GET['post_id']) == 'unboosted') {
                $data = array(
                    'status' => 300,
                    'text' => $wo['lang']['boost_post']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['unboost_post']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'mark_as_sold_post') {
        if (!empty($_GET['post_id']) && !empty($_GET['product_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_MarkPostAsSold($_GET['post_id'], $_GET['product_id'])) {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['sold']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'report_post') {
        if (!empty($_GET['post_id'])) {
            $post_data = array(
                'post_id' => $_GET['post_id']
            );
            if (Wo_ReportPost($post_data) == 'unreport') {
                $data = array(
                    'status' => 300,
                    'text' => $wo['lang']['report_post']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['unreport_post']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_post_likes') {
        if (!empty($_GET['post_id'])) {
            $data       = array(
                'status' => 200,
                'html' => ''
            );
            $likedUsers = Wo_GetPostLikes($_GET['post_id']);
            if (count($likedUsers) > 0) {
                foreach ($likedUsers as $wo['WondredLikedusers']) {
                    $data['html'] .= Wo_LoadPage('story/post-likes-wonders');
                }
            } else {
                $data['message'] = $wo['lang']['no_likes'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_post_wonders') {
        if (!empty($_GET['post_id'])) {
            $data          = array(
                'status' => 200,
                'html' => ''
            );
            $WonderedUsers = Wo_GetPostWonders($_GET['post_id']);
            if (count($WonderedUsers) > 0) {
                foreach ($WonderedUsers as $wo['WondredLikedusers']) {
                    $data['html'] .= Wo_LoadPage('story/post-likes-wonders');
                }
            } else {
                $data['message'] = ($config['second_post_button'] == 'dislike') ? $wo['lang']['no_dislikes'] : $wo['lang']['no_wonders'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'filter_posts') {
        if (!empty($_GET['filter_by']) && isset($_GET['id'])) {
            $html    = '';
            $options = array(
                'filter_by' => Wo_Secure($_GET['filter_by'])
            );
            if (!empty($_GET['type'])) {
                if ($_GET['type'] == 'page') {
                    $options['page_id'] = $_GET['id'];
                } else if ($_GET['type'] == 'profile') {
                    $options['publisher_id'] = $_GET['id'];
                } else if ($_GET['type'] == 'group') {
                    $options['group_id'] = $_GET['id'];
                }
            }
            $stories = Wo_GetPosts($options);
            if (count($stories) > 0) {
                foreach ($stories as $wo['story']) {
                    $html .= Wo_LoadPage('story/content');
                }
            } else {
                $html .= Wo_LoadPage('story/filter-no-stories-found');
            }
            $loadMoreText = '<i class="fa fa-chevron-circle-down progress-icon" data-icon="chevron-circle-down"></i> ' . $wo['lang']['load_more_posts'];
            if (empty($stories)) {
                $loadMoreText = $wo['lang']['no_more_posts'];
            }
            $data = array(
                'status' => 200,
                'html' => $html,
                'text' => $loadMoreText
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
if ($f == 'activities') {
    if ($s == 'get_new_activities') {
        if (!empty($_POST['before_activity_id'])) {
            $html     = '';
            $activity = Wo_GetActivities(array(
                'before_activity_id' => Wo_Secure($_POST['before_activity_id'])
            ));
            foreach ($activity as $wo['activity']) {
                $wo['activity']['unread'] = 'unread';
                $html .= Wo_LoadPage('sidebar/activities-list');
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_more_activities') {
        if (!empty($_POST['after_activity_id'])) {
            $html = '';
            foreach (Wo_GetActivities(array(
                'after_activity_id' => Wo_Secure($_POST['after_activity_id'])
            )) as $wo['activity']) {
                $html .= Wo_LoadPage('sidebar/activities-list');
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
            if (empty($html)) {
                $data['message'] = $wo['lang']['no_more_actitivties'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
if ($f == 'chat') {
    if ($s == 'count_online_users') {
        $html = Wo_CountOnlineUsers();
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'chat_side') {
        if (Wo_CheckMainSession($hash_id) === true) {
            $online_users  = '';
            $offline_users = '';
            $OnlineUsers   = Wo_GetChatUsers('online');
            $OfflineUsers  = Wo_GetChatUsers('offline');
            $count_chat    = Wo_CountOnlineUsers();
            foreach ($OnlineUsers as $wo['chatList']) {
                $online_users .= Wo_LoadPage('chat/online-user');
            }
            foreach ($OfflineUsers as $wo['chatList']) {
                $offline_users .= Wo_LoadPage('chat/offline-user');
            }
            $data = array(
                'status' => 200,
                'online_users' => $online_users,
                'offline_users' => $offline_users,
                'count_chat' => $count_chat
            );
            if (!empty($_GET['user_id'])) {
                $user_id = Wo_Secure($_GET['user_id']);
                if (!empty($user_id)) {
                    $user_id = $_GET['user_id'];
                    $status  = Wo_IsOnline($user_id);
                    if ($status === true) {
                        $data['chat_user_tab'] = 200;
                    } else {
                        $data['chat_user_tab'] = 300;
                    }
                }
            }
            $data['messages'] = 0;
            if (!empty($_GET['user_id']) && isset($_GET['message_id'])) {
                $html    = '';
                $user_id = Wo_Secure($_GET['user_id']);
                if (!empty($user_id)) {
                    $user_id  = $_GET['user_id'];
                    $messages = Wo_GetMessages(array(
                        'after_message_id' => $_GET['message_id'],
                        'user_id' => $user_id
                    ));
                    if (count($messages) > 0) {
                        $messages_html = '';
                        foreach ($messages as $wo['chatMessage']) {
                            $messages_html .= Wo_LoadPage('chat/chat-list');
                        }
                        $data['chat_user_tab'] = 200;
                        $data['messages']      = 200;
                        $data['messages_html'] = $messages_html;
                        $data['receiver']      = $wo['user']['user_id'];
                        $data['sender']        = $user_id;
                    }
                }
            }
            $data['can_seen'] = 0;
            if (!empty($_GET['last_id']) && $wo['config']['message_seen'] == 1) {
                $message_id = Wo_Secure($_GET['last_id']);
                if (!empty($message_id) || is_numeric($message_id) || $message_id > 0) {
                    $seen = Wo_SeenMessage($message_id);
                    if ($seen > 0) {
                        $data['can_seen'] = 1;
                        $data['time']     = $seen['time'];
                        $data['seen']     = $seen['seen'];
                    }
                }
            }
            $data['is_typing'] = 0;
            if (!empty($_GET['user_id']) && $wo['config']['message_typing'] == 1) {
                $isTyping = Wo_IsTyping($_GET['user_id']);
                if ($isTyping === true) {
                    $img               = Wo_UserData($_GET['user_id']);
                    $data['is_typing'] = 200;
                    $data['img']       = $img['avatar'];
                    $data['typing']    = $wo['config']['theme_url'] . '/img/loading_dots.gif';
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'is_recipient_typing') {
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'recipient_is_typing') {
        if (!empty($_GET['recipient_id'])) {
            $isTyping = Wo_RegisterTyping($_GET['recipient_id'], 1);
            if ($isTyping === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'remove_typing') {
        if (!empty($_GET['recipient_id'])) {
            $isTyping = Wo_RegisterTyping($_GET['recipient_id'], 0);
            if ($isTyping === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_online_recipients') {
        $html        = '';
        $OnlineUsers = Wo_GetChatUsers('online');
        foreach ($OnlineUsers as $wo['chatList']) {
            $html .= Wo_LoadPage('chat/online-user');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_offline_recipients') {
        $html         = '';
        $OfflineUsers = Wo_GetChatUsers('offline');
        foreach ($OfflineUsers as $wo['chatList']) {
            $html .= Wo_LoadPage('chat/offline-user');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'search_for_recipients') {
        if (!empty($_POST['search_query'])) {
            $html   = '';
            $search = Wo_ChatSearchUsers($_POST['search_query']);
            foreach ($search as $wo['chatList']) {
                $html .= Wo_LoadPage('chat/search-result');
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_chat_status') {
        if (!empty($_POST['status'])) {
            $html   = '';
            $status = Wo_UpdateStatus($_POST['status']);
            if ($status == 0) {
                $data = array(
                    'status' => $status
                );
            } else if ($status == 1) {
                $data = array(
                    'status' => $status
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_chat_tab') {
        if (!empty($_GET['recipient_id']) && is_numeric($_GET['recipient_id']) && $_GET['recipient_id'] > 0 && !empty($_GET['placement'])) {
            $recipient_id = Wo_Secure($_GET['recipient_id']);
            $recipient    = Wo_UserData($recipient_id);
            if (isset($recipient['user_id'])) {
                $wo['chat']['recipient'] = $recipient;
                $data                    = array(
                    'status' => 200,
                    'html' => Wo_LoadPage('chat/chat-tab')
                );
                if (isset($_SESSION['chat_id'])) {
                    if (strpos($_SESSION['chat_id'], ',') !== false) {
                        $explode = @explode(',', $_SESSION['chat_id']);
                        if (count($explode) > 2) {
                            if (strpos($_SESSION['chat_id'], $recipient['user_id']) === false) {
                                $_SESSION['chat_id'] = substr($_SESSION['chat_id'], 0, strrpos($_SESSION['chat_id'], ','));
                                $_SESSION['chat_id'] .= ',' . Wo_Secure($recipient['user_id']);
                            }
                        } else {
                            $_SESSION['chat_id'] .= ',' . Wo_Secure($recipient['user_id']);
                        }
                    } else if (strpos($_SESSION['chat_id'], $recipient['user_id']) === false) {
                        $_SESSION['chat_id'] .= ',' . Wo_Secure($recipient['user_id']);
                    } else {
                        $_SESSION['chat_id'] = Wo_Secure($recipient['user_id']);
                    }
                } else {
                    $_SESSION['chat_id'] = Wo_Secure($recipient['user_id']);
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_chat_messages') {
        if (!empty($_GET['recipient_id']) && is_numeric($_GET['recipient_id']) && $_GET['recipient_id'] > 0 && Wo_CheckMainSession($hash_id) === true) {
            $recipient_id = Wo_Secure($_GET['recipient_id']);
            $html         = '';
            $messages     = Wo_GetMessages(array(
                'user_id' => $recipient_id
            ));
            foreach ($messages as $wo['chatMessage']) {
                $html .= Wo_LoadPage('chat/chat-list');
            }
            $data = array(
                'status' => 200,
                'messages' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'open_tab') {
        if (isset($_SESSION['open_chat'])) {
            if ($_SESSION['open_chat'] == 1) {
                $_SESSION['open_chat'] = 0;
            } else if ($_SESSION['open_chat'] == 0) {
                $_SESSION['open_chat'] = 1;
            }
        } else {
            $_SESSION['open_chat'] = 1;
        }
    }
    if ($s == 'send_message') {
        if (!empty($_POST['user_id']) && Wo_CheckMainSession($hash_id) === true) {
            $html          = '';
            $media         = '';
            $mediaFilename = '';
            $mediaName     = '';
            if (isset($_FILES['sendMessageFile']['name'])) {
                $fileInfo      = array(
                    'file' => $_FILES["sendMessageFile"]["tmp_name"],
                    'name' => $_FILES['sendMessageFile']['name'],
                    'size' => $_FILES["sendMessageFile"]["size"],
                    'type' => $_FILES["sendMessageFile"]["type"]
                );
                $media         = Wo_ShareFile($fileInfo);
                $mediaFilename = $media['filename'];
                $mediaName     = $media['name'];
            }
            $message_text = '';
            if (!empty($_POST['textSendMessage'])) {
                $message_text = $_POST['textSendMessage'];
            }
            $messages = Wo_RegisterMessage(array(
                'from_id' => Wo_Secure($wo['user']['user_id']),
                'to_id' => Wo_Secure($_POST['user_id']),
                'text' => Wo_Secure($message_text),
                'media' => Wo_Secure($mediaFilename),
                'mediaFileName' => Wo_Secure($mediaName),
                'time' => time()
            ));
            if ($messages > 0) {
                $messages = Wo_GetMessages(array(
                    'message_id' => $messages,
                    'user_id' => $_POST['user_id']
                ));
                foreach ($messages as $wo['chatMessage']) {
                    $html .= Wo_LoadPage('chat/chat-list');
                }
                $file = false;
                if (isset($_FILES['sendMessageFile']['name'])) {
                    $file = true;
                }
                $data = array(
                    'status' => 200,
                    'html' => $html,
                    'file' => $file
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_new_messages') {
        if (!empty($_GET['user_id']) && Wo_CheckMainSession($hash_id) === true) {
            $html    = '';
            $user_id = Wo_Secure($_GET['user_id']);
            if (!empty($user_id)) {
                $user_id  = $_GET['user_id'];
                $messages = Wo_GetMessages(array(
                    'after_message_id' => $_GET['message_id'],
                    'new' => true,
                    'user_id' => $user_id
                ));
                if (count($messages) > 0) {
                    foreach ($messages as $wo['chatMessage']) {
                        $html .= Wo_LoadPage('chat/chat-list');
                    }
                    $data = array(
                        'status' => 200,
                        'html' => $html,
                        'receiver' => $user_id,
                        'sender' => $wo['user']['user_id']
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_tab_status') {
        $html = '';
        if (!empty($_GET['user_id'])) {
            $user_id = Wo_Secure($_GET['user_id']);
            if (!empty($user_id)) {
                $user_id = $_GET['user_id'];
                $status  = Wo_IsOnline($user_id);
                if ($status === true) {
                    $data['status'] = 200;
                } else {
                    $data['status'] = 300;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'close') {
        if (isset($_SESSION['chat_id'])) {
            if (strpos($_SESSION['chat_id'], ',') !== false) {
                $_SESSION['chat_id'] = str_replace($_GET['id'] . ',', '', $_SESSION['chat_id']);
                $_SESSION['chat_id'] = str_replace(',' . $_GET['id'], '', $_SESSION['chat_id']);
            } else {
                unset($_SESSION['chat_id']);
            }
        }
        if (!empty($_GET['recipient_id'])) {
            $data = array(
                'url' => Wo_SeoLink('index.php?link1=messages&user=' . $_GET['recipient_id'])
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'is_chat_on') {
        if (!empty($_GET['recipient_id'])) {
            $data = array(
                'url' => Wo_SeoLink('index.php?link1=messages&user=' . $_GET['recipient_id']),
                'chat' => $wo['config']['chatSystem']
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
if ($f == 'apps') {
    if ($s == 'create_app') {
        if (empty($_POST['app_name']) || empty($_POST['app_website_url']) || empty($_POST['app_description'])) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        }
        if (!filter_var($_POST['app_website_url'], FILTER_VALIDATE_URL)) {
            $errors[] = $error_icon . $wo['lang']['website_invalid_characters'];
        }
        if (empty($errors)) {
            $re_app_data = array(
                'app_user_id' => Wo_Secure($wo['user']['user_id']),
                'app_name' => Wo_Secure($_POST['app_name']),
                'app_website_url' => Wo_Secure($_POST['app_website_url']),
                'app_description' => Wo_Secure($_POST['app_description'])
            );
            $app_id      = Wo_RegisterApp($re_app_data);
            if ($app_id != '') {
                if (!empty($_FILES["app_avatar"]["name"])) {
                    Wo_UploadImage($_FILES["app_avatar"]["tmp_name"], $_FILES['app_avatar']['name'], 'app', $_FILES['app_avatar']['type'], $app_id);
                }
                $data = array(
                    'status' => 200,
                    'location' => Wo_SeoLink('index.php?link1=app&app_id=' . $app_id)
                );
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'update_app') {
        if (empty($_POST['app_name']) || empty($_POST['app_website_url']) || empty($_POST['app_description'])) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        }
        if (!filter_var($_POST['app_website_url'], FILTER_VALIDATE_URL)) {
            $errors[] = $error_icon . $wo['lang']['website_invalid_characters'];
        }
        if (empty($errors)) {
            $app_id      = $_POST['app_id'];
            $re_app_data = array(
                'app_user_id' => Wo_Secure($wo['user']['user_id']),
                'app_name' => Wo_Secure($_POST['app_name']),
                'app_website_url' => Wo_Secure($_POST['app_website_url']),
                'app_description' => Wo_Secure($_POST['app_description'])
            );
            if (Wo_UpdateAppData($app_id, $re_app_data) === true) {
                if (!empty($_FILES["app_avatar"]["name"])) {
                    Wo_UploadImage($_FILES["app_avatar"]["tmp_name"], $_FILES['app_avatar']['name'], 'app', $_FILES['app_avatar']['type'], $app_id);
                }
                $img  = Wo_GetApp($app_id);
                $data = array(
                    'status' => 200,
                    'message' => $wo['lang']['setting_updated'],
                    'name' => $_POST['app_name'],
                    'image' => $img['app_avatar']
                );
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'acceptPermissions') {
        $acceptPermissions = Wo_AcceptPermissions($_GET['id']);
        if ($acceptPermissions === true) {
            $import = Wo_GenrateToken($wo['user']['user_id'], $_GET['id']);
            $app    = $_GET['url'] . '?access_token=' . $import;
            $data   = array(
                'status' => 200,
                'location' => $app
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
if ($f == 'pages') {
    if ($s == 'create_page') {
        if (empty($_POST['page_name']) || empty($_POST['page_title']) || Wo_CheckSession($hash_id) === false) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        } else {
            $is_exist = Wo_IsNameExist($_POST['page_name'], 0);
            if (in_array(true, $is_exist)) {
                $errors[] = $error_icon . $wo['lang']['page_name_exists'];
            }
            if (in_array($_POST['page_name'], $wo['site_pages'])) {
                $errors[] = $error_icon . $wo['lang']['page_name_invalid_characters'];
            }
            if (strlen($_POST['page_name']) < 5 OR strlen($_POST['page_name']) > 32) {
                $errors[] = $error_icon . $wo['lang']['page_name_characters_length'];
            }
            if (!preg_match('/^[\w]+$/', $_POST['page_name'])) {
                $errors[] = $error_icon . $wo['lang']['page_name_invalid_characters'];
            }
            if (empty($_POST['page_category'])) {
                $_POST['page_category'] = 1;
            }
        }
        if (empty($errors)) {
            $re_page_data  = array(
                'page_name' => Wo_Secure($_POST['page_name']),
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'page_title' => Wo_Secure($_POST['page_title']),
                'page_description' => Wo_Secure($_POST['page_description']),
                'page_category' => Wo_Secure($_POST['page_category']),
                'active' => '1'
            );
            $register_page = Wo_RegisterPage($re_page_data);
            if ($register_page) {
                $data = array(
                    'status' => 200,
                    'location' => Wo_SeoLink('index.php?link1=timeline&u=' . Wo_Secure($_POST['page_name']))
                );
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'update_information_setting') {
        if (!empty($_POST['page_id']) && Wo_CheckSession($hash_id) === true) {
            $PageData = Wo_PageData($_POST['page_id']);
            if (!empty($_POST['website'])) {
                if (!filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
                    $errors[] = $error_icon . $wo['lang']['website_invalid_characters'];
                }
            }
            if (empty($errors)) {
                $Update_data = array(
                    'website' => $_POST['website'],
                    'page_description' => $_POST['page_description'],
                    'company' => $_POST['company'],
                    'address' => $_POST['address'],
                    'phone' => $_POST['phone']
                );
                if (Wo_UpdatePageData($_POST['page_id'], $Update_data)) {
                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['setting_updated']
                    );
                }
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'update_sociallink_setting') {
        if (!empty($_POST['page_id']) && Wo_CheckSession($hash_id) === true) {
            $PageData = Wo_PageData($_POST['page_id']);
            if (empty($errors)) {
                $Update_data = array(
                    'facebook' => $_POST['facebook'],
                    'google' => $_POST['google'],
                    'twitter' => $_POST['twitter'],
                    'linkedin' => $_POST['linkedin'],
                    'vk' => $_POST['vk']
                );
                if (Wo_UpdatePageData($_POST['page_id'], $Update_data)) {
                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['setting_updated']
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_images_setting') {
        if (isset($_POST['page_id']) && Wo_CheckSession($hash_id) === true) {
            $Userdata = Wo_PageData($_POST['page_id']);
            if (!empty($Userdata['page_id'])) {
                if (isset($_FILES['avatar']['name'])) {
                    if (Wo_UploadImage($_FILES["avatar"]["tmp_name"], $_FILES['avatar']['name'], 'avatar', $_FILES['avatar']['type'], $_POST['page_id'], 'page') === true) {
                        $page_data = Wo_PageData($_POST['page_id']);
                    }
                }
                if (isset($_FILES['cover']['name'])) {
                    if (Wo_UploadImage($_FILES["cover"]["tmp_name"], $_FILES['cover']['name'], 'cover', $_FILES['cover']['type'], $_POST['page_id'], 'page') === true) {
                        $page_data = Wo_PageData($_POST['page_id']);
                    }
                }
                if (empty($errors)) {
                    $Update_data = array(
                        'active' => '1'
                    );
                    if (Wo_UpdatePageData($_POST['page_id'], $Update_data)) {
                        $userdata2 = Wo_PageData($_POST['page_id']);
                        $data      = array(
                            'status' => 200,
                            'message' => $success_icon . $wo['lang']['setting_updated'],
                            'cover' => $userdata2['cover'],
                            'avatar' => $userdata2['avatar']
                        );
                    }
                }
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
    }
    if ($s == 'update_general_settings') {
        if (!empty($_POST['page_id']) && Wo_CheckSession($hash_id) === true) {
            $PageData = Wo_PageData($_POST['page_id']);
            if (empty($_POST['page_name']) OR empty($_POST['page_category']) OR empty($_POST['page_title'])) {
                $errors[] = $error_icon . $wo['lang']['please_check_details'];
            } else {
                if ($_POST['page_name'] != $PageData['page_name']) {
                    $is_exist = Wo_IsNameExist($_POST['page_name'], 0);
                    if (in_array(true, $is_exist)) {
                        $errors[] = $error_icon . $wo['lang']['page_name_exists'];
                    }
                }
                if (in_array($_POST['page_name'], $wo['site_pages'])) {
                    $errors[] = $error_icon . $wo['lang']['page_name_invalid_characters'];
                }
                if (strlen($_POST['page_name']) < 5 || strlen($_POST['page_name']) > 32) {
                    $errors[] = $error_icon . $wo['lang']['page_name_characters_length'];
                }
                if (!preg_match('/^[\w]+$/', $_POST['page_name'])) {
                    $errors[] = $error_icon . $wo['lang']['page_name_invalid_characters'];
                }
                if (empty($_POST['page_category'])) {
                    $_POST['page_category'] = 1;
                }
                $call_action_type = 0;
                if (!empty($_POST['call_action_type'])) {
                    if (array_key_exists($_POST['call_action_type'], $wo['call_action'])) {
                        $call_action_type = $_POST['call_action_type'];
                    }
                }
                if (!empty($_POST['call_action_type_url'])) {
                    if (!filter_var($_POST['call_action_type_url'], FILTER_VALIDATE_URL)) {
                        $errors[] = $error_icon . $wo['lang']['call_action_type_url_invalid'];
                    }
                }
                if (empty($errors)) {
                    $Update_data = array(
                        'page_name' => $_POST['page_name'],
                        'page_title' => $_POST['page_title'],
                        'page_category' => $_POST['page_category'],
                        'call_action_type' => $call_action_type,
                        'call_action_type_url' => $_POST['call_action_type_url']
                    );
                    $array       = array(
                        'verified' => 1,
                        'notVerified' => 0
                    );
                    if (!empty($_POST['verified'])) {
                        if (array_key_exists($_POST['verified'], $array)) {
                            $Update_data['verified'] = $array[$_POST['verified']];
                        }
                    }
                    if (Wo_UpdatePageData($_POST['page_id'], $Update_data)) {
                        $data = array(
                            'status' => 200,
                            'message' => $success_icon . $wo['lang']['setting_updated']
                        );
                    }
                }
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'delete_page') {
        if (!empty($_POST['page_id']) && Wo_CheckSession($hash_id) === true) {
            if (md5($_POST['password']) != $wo['user']['password']) {
                $errors[] = $error_icon . $wo['lang']['current_password_mismatch'];
            }
            if (empty($errors)) {
                if (Wo_DeletePage($_POST['page_id']) === true) {
                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['page_deleted'],
                        'location' => Wo_SeoLink('index.php?link1=pages')
                    );
                }
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'get_more_likes') {
        $html = '';
        if (isset($_GET['user_id']) && isset($_GET['after_last_id'])) {
            foreach (Wo_GetLikes($_GET['user_id'], 'profile', 10, $_GET['after_last_id']) as $wo['PageList']) {
                $html .= Wo_LoadPage('timeline/likes-list');
            }
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_next_page') {
        $html    = '';
        $page_id = (!empty($_GET['page_id'])) ? $_GET['page_id'] : 0;
        foreach (Wo_PageSug(1, $page_id) as $wo['PageList']) {
            $wo['PageList']['user_name'] = $wo['PageList']['name'];
            $html                        = Wo_LoadPage('sidebar/sidebar-home-page-list');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_likes') {
        $html = '';
        if (!empty($_GET['user_id'])) {
            foreach (Wo_GetLikes($_GET['user_id'], 'sidebar', 12) as $wo['PageList']) {
                $wo['PageList']['user_name'] = @mb_substr($wo['PageList']['name'], 0, 10, "utf-8");
                $html .= Wo_LoadPage('sidebar/sidebar-page-list');
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
if ($f == 'like_page') {
    if (!empty($_GET['page_id']) && Wo_CheckMainSession($hash_id) === true) {
        if (Wo_IsPageLiked($_GET['page_id'], $wo['user']['user_id']) === true) {
            if (Wo_DeletePageLike($_GET['page_id'], $wo['user']['user_id'])) {
                $data = array(
                    'status' => 200,
                    'html' => Wo_GetLikeButton($_GET['page_id'])
                );
            }
        } else {
            if (Wo_RegisterPageLike($_GET['page_id'], $wo['user']['user_id'])) {
                $data = array(
                    'status' => 200,
                    'html' => Wo_GetLikeButton($_GET['page_id'])
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'check_pagename') {
    if (isset($_GET['pagename']) && !empty($_GET['page_id'])) {
        $pagename  = Wo_Secure($_GET['pagename']);
        $page_data = Wo_PageData($_GET['page_id']);
        if ($pagename == $page_data['page_name']) {
            $data['status']  = 200;
            $data['message'] = $wo['lang']['available'];
        } else if (strlen($pagename) < 5) {
            $data['status']  = 400;
            $data['message'] = $wo['lang']['too_short'];
        } else if (strlen($pagename) > 32) {
            $data['status']  = 500;
            $data['message'] = $wo['lang']['too_long'];
        } else if (!preg_match('/^[\w]+$/', $_GET['pagename'])) {
            $data['status']  = 600;
            $data['message'] = $wo['lang']['username_invalid_characters_2'];
        } else {
            $is_exist = Wo_IsNameExist($_GET['pagename'], 0);
            if (in_array(true, $is_exist)) {
                $data['status']  = 300;
                $data['message'] = $wo['lang']['in_use'];
            } else {
                $data['status']  = 200;
                $data['message'] = $wo['lang']['available'];
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'check_groupname') {
    if (isset($_GET['groupname']) && !empty($_GET['group_id'])) {
        $group_name = Wo_Secure($_GET['groupname']);
        $group_data = Wo_GroupData($_GET['group_id']);
        if ($group_name == $group_data['group_name']) {
            $data['status']  = 200;
            $data['message'] = $wo['lang']['available'];
        } else if (strlen($group_name) < 5) {
            $data['status']  = 400;
            $data['message'] = $wo['lang']['too_short'];
        } else if (strlen($group_name) > 32) {
            $data['status']  = 500;
            $data['message'] = $wo['lang']['too_long'];
        } else if (!preg_match('/^[\w]+$/', $_GET['groupname'])) {
            $data['status']  = 600;
            $data['message'] = $wo['lang']['username_invalid_characters_2'];
        } else {
            $is_exist = Wo_IsNameExist($_GET['groupname'], 0);
            if (in_array(true, $is_exist)) {
                $data['status']  = 300;
                $data['message'] = $wo['lang']['in_use'];
            } else {
                $data['status']  = 200;
                $data['message'] = $wo['lang']['available'];
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_page_cover_picture') {
    if (isset($_FILES['cover']['name']) && !empty($_POST['page_id'])) {
        if (Wo_UploadImage($_FILES["cover"]["tmp_name"], $_FILES['cover']['name'], 'cover', $_FILES['cover']['type'], $_POST['page_id'], 'page')) {
            $img  = Wo_PageData($_POST['page_id']);
            $data = array(
                'status' => 200,
                'img' => $img['cover']
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_page_avatar_picture') {
    if (isset($_FILES['avatar']['name']) && !empty($_POST['page_id'])) {
        if (Wo_UploadImage($_FILES["avatar"]["tmp_name"], $_FILES['avatar']['name'], 'avatar', $_FILES['avatar']['type'], $_POST['page_id'], 'page')) {
            $img  = Wo_PageData($_POST['page_id']);
            $data = array(
                'status' => 200,
                'img' => $img['avatar']
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_group_cover_picture') {
    if (isset($_FILES['cover']['name']) && !empty($_POST['group_id'])) {
        if (Wo_UploadImage($_FILES["cover"]["tmp_name"], $_FILES['cover']['name'], 'cover', $_FILES['cover']['type'], $_POST['group_id'], 'group')) {
            $img  = Wo_GroupData($_POST['group_id']);
            $data = array(
                'status' => 200,
                'img' => $img['cover']
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_group_avatar_picture') {
    if (isset($_FILES['avatar']['name']) && !empty($_POST['group_id'])) {
        if (Wo_UploadImage($_FILES["avatar"]["tmp_name"], $_FILES['avatar']['name'], 'avatar', $_FILES['avatar']['type'], $_POST['group_id'], 'group')) {
            $img  = Wo_GroupData($_POST['group_id']);
            $data = array(
                'status' => 200,
                'img' => $img['avatar']
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'join_group') {
    if (isset($_GET['group_id']) && Wo_CheckMainSession($hash_id) === true) {
        if (Wo_IsGroupJoined($_GET['group_id']) === true || Wo_IsJoinRequested($_GET['group_id'], $wo['user']['user_id']) === true) {
            if (Wo_LeaveGroup($_GET['group_id'], $wo['user']['user_id'])) {
                $data = array(
                    'status' => 200,
                    'html' => '' //Wo_GetJoinButton($_GET['group_id'])
                );
            }
        } else {
            if (Wo_RegisterGroupJoin($_GET['group_id'], $wo['user']['user_id'])) {
                $data = array(
                    'status' => 200,
                    'html' => '' //Wo_GetJoinButton($_GET['group_id'])
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'request_verification') {
    if (!empty($_GET['id']) && !empty($_GET['type'])) {
        if (Wo_RequestVerification($_GET['id'], $_GET['type']) === true) {
            $data = array(
                'status' => 200,
                'html' => Wo_GetVerificationButton($_GET['id'], $_GET['type'])
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'delete_verification') {
    if (!empty($_GET['id']) && !empty($_GET['type'])) {
        if (Wo_DeleteVerification($_GET['id'], $_GET['type']) === true) {
            $data = array(
                'status' => 200,
                'html' => Wo_GetVerificationButton($_GET['id'], $_GET['type'])
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'remove_verification') {
    if (!empty($_GET['id']) && !empty($_GET['type'])) {
        if (Wo_RemoveVerificationRequest($_GET['id'], $_GET['type']) === true) {
            $data = array(
                'status' => 200,
                'html' => Wo_GetVerificationButton($_GET['id'], $_GET['type'])
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'popover') {
    $html        = '';
    $array_types = array(
        'user',
        'page',
        'group'
    );
    if (!empty($_GET['id']) && !empty($_GET['type']) && in_array($_GET['type'], $array_types)) {
        if ($_GET['type'] == 'page') {
            $wo['popover'] = Wo_PageData($_GET['id']);
            if (!empty($wo['popover'])) {
                $html = Wo_LoadPage('popover/page-content');
            }
        } else if ($_GET['type'] == 'user') {
            $wo['popover'] = Wo_UserData($_GET['id']);
            if (!empty($wo['popover'])) {
                $html = Wo_LoadPage('popover/content');
            }
        } else if ($_GET['type'] == 'group') {
            $wo['popover'] = Wo_GroupData($_GET['id']);
            if (!empty($wo['popover'])) {
                $html = Wo_LoadPage('popover/group-content');
            }
        }
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'open_lightbox') {
    $html = '';
    if (!empty($_GET['post_id'])) {
        $wo['story'] = Wo_PostData($_GET['post_id']);
        if (!empty($wo['story'])) {
            $html = Wo_LoadPage('lightbox/content');
        }
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'open_album_lightbox') {
    $html = '';
    if (!empty($_GET['image_id'])) {
        $data_image = array(
            'id' => $_GET['image_id']
        );
        if ($_GET['type'] == 'album') {
            $wo['image'] = Wo_AlbumImageData($data_image);
            if (!empty($wo['image'])) {
                $html = Wo_LoadPage('lightbox/album-content');
            }
        } else {
            $wo['image'] = Wo_ProductImageData($data_image);
            if (!empty($wo['image'])) {
                $html = Wo_LoadPage('lightbox/product-content');
            }
        }
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_next_album_image') {
    $html = '';
    if (!empty($_GET['after_image_id'])) {
        $data_image  = array(
            'post_id' => $_GET['post_id'],
            'after_image_id' => $_GET['after_image_id']
        );
        $wo['image'] = Wo_AlbumImageData($data_image);
        if (!empty($wo['image'])) {
            $html = Wo_LoadPage('lightbox/album-content');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_previous_album_image') {
    $html = '';
    if (!empty($_GET['before_image_id'])) {
        $data_image  = array(
            'post_id' => $_GET['post_id'],
            'before_image_id' => $_GET['before_image_id']
        );
        $wo['image'] = Wo_AlbumImageData($data_image);
        if (!empty($wo['image'])) {
            $html = Wo_LoadPage('lightbox/album-content');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'open_multilightbox') {
    $html = '';
    if (!empty($_POST['url'])) {
        $wo['lighbox']['url'] = $_POST['url'];
        $html                 = Wo_LoadPage('lightbox/content-multi');
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_next_image') {
    $html      = '';
    $postsData = array(
        'limit' => 1,
        'filter_by' => 'photos',
        'after_post_id' => Wo_Secure($_GET['post_id'])
    );
    if (!empty($_GET['type']) && !empty($_GET['id'])) {
        if ($_GET['type'] == 'profile') {
            $postsData['publisher_id'] = $_GET['id'];
        } else if ($_GET['type'] == 'page') {
            $postsData['page_id'] = $_GET['id'];
        } else if ($_GET['type'] == 'group') {
            $postsData['group_id'] = $_GET['id'];
        }
    }
    foreach (Wo_GetPosts($postsData) as $wo['story']) {
        $html .= Wo_LoadPage('lightbox/content');
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_previous_image') {
    $html      = '';
    $postsData = array(
        'limit' => 1,
        'filter_by' => 'photos',
        'order' => 'ASC',
        'before_post_id' => Wo_Secure($_GET['post_id'])
    );
    if (!empty($_GET['type']) && !empty($_GET['id'])) {
        if ($_GET['type'] == 'profile') {
            $postsData['publisher_id'] = $_GET['id'];
        } else if ($_GET['type'] == 'page') {
            $postsData['page_id'] = $_GET['id'];
        } else if ($_GET['type'] == 'group') {
            $postsData['group_id'] = $_GET['id'];
        }
    }
    foreach (Wo_GetPosts($postsData) as $wo['story']) {
        $html .= Wo_LoadPage('lightbox/content');
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'groups') {
    if ($s == 'create_group') {
        if (empty($_POST['group_name']) || empty($_POST['group_title']) || Wo_CheckSession($hash_id) === false) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        } else {
            $is_exist = Wo_IsNameExist($_POST['group_name'], 0);
            if (in_array(true, $is_exist)) {
                $errors[] = $error_icon . $wo['lang']['group_name_exists'];
            }
            if (in_array($_POST['group_name'], $wo['site_pages'])) {
                $errors[] = $error_icon . $wo['lang']['group_name_invalid_characters'];
            }
            if (strlen($_POST['group_name']) < 5 OR strlen($_POST['group_name']) > 32) {
                $errors[] = $error_icon . $wo['lang']['group_name_characters_length'];
            }
            if (!preg_match('/^[\w]+$/', $_POST['group_name'])) {
                $errors[] = $error_icon . $wo['lang']['group_name_invalid_characters'];
            }
            if (empty($_POST['category'])) {
                $_POST['category'] = 1;
            }
        }
        if (empty($errors)) {
            $re_group_data  = array(
                'group_name' => Wo_Secure($_POST['group_name']),
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'group_title' => Wo_Secure($_POST['group_title']),
                'about' => Wo_Secure($_POST['about']),
                'category' => Wo_Secure($_POST['category']),
                'active' => '1'
            );
            $register_group = Wo_RegisterGroup($re_group_data);
            if ($register_group) {
                $data = array(
                    'status' => 200,
                    'location' => Wo_SeoLink('index.php?link1=timeline&u=' . Wo_Secure($_POST['group_name']))
                );
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'update_information_setting') {
        if (!empty($_POST['page_id'])) {
            $PageData = Wo_PageData($_POST['page_id']);
            if (!empty($_POST['website'])) {
                if (!filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
                    $errors[] = $error_icon . $wo['lang']['website_invalid_characters'];
                }
            }
            if (empty($errors)) {
                $Update_data = array(
                    'website' => $_POST['website'],
                    'page_description' => $_POST['page_description'],
                    'company' => $_POST['company'],
                    'address' => $_POST['address'],
                    'phone' => $_POST['phone']
                );
                if (Wo_UpdatePageData($_POST['page_id'], $Update_data)) {
                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['setting_updated']
                    );
                }
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'update_privacy_setting') {
        if (!empty($_POST['group_id']) && Wo_CheckSession($hash_id) === true) {
            $PageData     = Wo_PageData($_POST['group_id']);
            $privacy      = 1;
            $join_privacy = 1;
            $array        = array(
                1,
                2
            );
            if (!empty($_POST['privacy'])) {
                if (in_array($_POST['privacy'], $array)) {
                    $privacy = $_POST['privacy'];
                }
            }
            if (!empty($_POST['join_privacy'])) {
                if (in_array($_POST['join_privacy'], $array)) {
                    $join_privacy = $_POST['join_privacy'];
                }
            }
            if (empty($errors)) {
                $Update_data = array(
                    'privacy' => $privacy,
                    'join_privacy' => $join_privacy
                );
                if (Wo_UpdateGroupData($_POST['group_id'], $Update_data)) {
                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['setting_updated']
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_images_setting') {
        if (isset($_POST['group_id']) && Wo_CheckSession($hash_id) === true) {
            $Userdata = Wo_GroupData($_POST['group_id']);
            if (!empty($Userdata['id'])) {
                if (!empty($_FILES['avatar']['name'])) {
                    if (Wo_UploadImage($_FILES["avatar"]["tmp_name"], $_FILES['avatar']['name'], 'avatar', $_FILES['avatar']['type'], $_POST['group_id'], 'group') === true) {
                        $page_data = Wo_GroupData($_POST['group_id']);
                    }
                }
                if (!empty($_FILES['cover']['name'])) {
                    if (Wo_UploadImage($_FILES["cover"]["tmp_name"], $_FILES['cover']['name'], 'cover', $_FILES['cover']['type'], $_POST['group_id'], 'group') === true) {
                        $page_data = Wo_GroupData($_POST['group_id']);
                    }
                }
                if (empty($errors)) {
                    $Update_data = array(
                        'active' => '1'
                    );
                    if (Wo_UpdateGroupData($_POST['group_id'], $Update_data)) {
                        $userdata2 = Wo_GroupData($_POST['group_id']);
                        $data      = array(
                            'status' => 200,
                            'message' => $success_icon . $wo['lang']['setting_updated'],
                            'cover' => $userdata2['cover'],
                            'avatar' => $userdata2['avatar']
                        );
                    }
                }
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
    }
    if ($s == 'update_general_settings') {
        if (!empty($_POST['group_id']) && Wo_CheckSession($hash_id) === true) {
            $group_data = Wo_GroupData($_POST['group_id']);
            if (empty($_POST['group_name']) OR empty($_POST['group_category']) OR empty($_POST['group_title'])) {
                $errors[] = $error_icon . $wo['lang']['please_check_details'];
            } else {
                if ($_POST['group_name'] != $group_data['group_name']) {
                    $is_exist = Wo_IsNameExist($_POST['group_name'], 0);
                    if (in_array(true, $is_exist)) {
                        $errors[] = $error_icon . $wo['lang']['group_name_exists'];
                    }
                }
                if (in_array($_POST['group_name'], $wo['site_pages'])) {
                    $errors[] = $error_icon . $wo['lang']['group_name_invalid_characters'];
                }
                if (strlen($_POST['group_name']) < 5 || strlen($_POST['group_name']) > 32) {
                    $errors[] = $error_icon . $wo['lang']['group_name_characters_length'];
                }
                if (!preg_match('/^[\w]+$/', $_POST['group_name'])) {
                    $errors[] = $error_icon . $wo['lang']['group_name_invalid_characters'];
                }
                if (empty($_POST['group_category'])) {
                    $_POST['group_category'] = 1;
                }
                if (empty($errors)) {
                    $Update_data = array(
                        'group_name' => $_POST['group_name'],
                        'group_title' => $_POST['group_title'],
                        'category' => $_POST['group_category'],
                        'about' => $_POST['about']
                    );
                    if (Wo_UpdateGroupData($_POST['group_id'], $Update_data)) {
                        $data = array(
                            'status' => 200,
                            'message' => $success_icon . $wo['lang']['setting_updated']
                        );
                    }
                }
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'delete_group') {
        if (!empty($_POST['group_id']) && Wo_CheckSession($hash_id) === true) {
            if (md5($_POST['password']) != $wo['user']['password']) {
                $errors[] = $error_icon . $wo['lang']['current_password_mismatch'];
            }
            if (empty($errors)) {
                if (Wo_DeleteGroup($_POST['group_id']) === true) {
                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['group_deleted'],
                        'location' => Wo_SeoLink('index.php?link1=groups')
                    );
                }
            }
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'accept_request') {
        if (isset($_GET['user_id']) && !empty($_GET['group_id'])) {
            if (Wo_AcceptJoinRequest($_GET['user_id'], $_GET['group_id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_request') {
        if (isset($_GET['user_id']) && !empty($_GET['group_id'])) {
            if (Wo_DeleteJoinRequest($_GET['user_id'], $_GET['group_id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_joined_user') {
        if (isset($_GET['user_id']) && !empty($_GET['group_id'])) {
            if (Wo_LeaveGroup($_GET['group_id'], $_GET['user_id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
if ($f == 'get_user_profile_image_post') {
    if (!empty($_POST['image'])) {
        $getUserImage = Wo_GetUserProfilePicture(Wo_Secure($_POST['image'], 0));
        if (!empty($getUserImage)) {
            $data = array(
                'status' => 200,
                'post_id' => $getUserImage
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_user_profile_cover_image_post') {
    if (!empty($_POST['image'])) {
        $getUserImage = Wo_GetUserProfilePicture(Wo_Secure($_POST['image'], 0));
        if (!empty($getUserImage)) {
            $data = array(
                'status' => 200,
                'post_id' => $getUserImage
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'register_recent_search') {
    $array_type = array(
        'user',
        'page',
        'group'
    );
    if (!empty($_GET['id']) && !empty($_GET['type'])) {
        if (in_array($_GET['type'], $array_type)) {
            if ($_GET['type'] == 'user') {
                $regsiter_recent = Wo_RegsiterRecent($_GET['id'], $_GET['type']);
                $user            = Wo_UserData($regsiter_recent);
            } else if ($_GET['type'] == 'page') {
                $regsiter_recent = Wo_RegsiterRecent($_GET['id'], $_GET['type']);
                $user            = Wo_PageData($regsiter_recent);
            } else if ($_GET['type'] == 'group') {
                $regsiter_recent = Wo_RegsiterRecent($_GET['id'], $_GET['type']);
                $user            = Wo_GroupData($regsiter_recent);
            }
            if (!empty($user['url'])) {
                $data = array(
                    'status' => 200,
                    'href' => $user['url']
                );
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'clearChat') {
    $clear = Wo_ClearRecent();
    if ($clear === true) {
        $data = array(
            'status' => 200
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'album') {
    if ($s == 'create_album' && Wo_CheckSession($hash_id) === true) {
        if (empty($_POST['album_name'])) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        } else if (empty($_FILES['postPhotos']['name'])) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        }
        if (isset($_FILES['postPhotos']['name'])) {
            $allowed = array(
                'gif',
                'png',
                'jpg',
                'jpeg'
            );
            for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                $new_string = pathinfo($_FILES['postPhotos']['name'][$i]);
                if (!in_array(strtolower($new_string['extension']), $allowed)) {
                    $errors[] = $error_icon . $wo['lang']['please_check_details'];
                }
            }
        }
        if (empty($errors)) {
            $post_data = array(
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'album_name' => Wo_Secure($_POST['album_name']),
                'postPrivacy' => Wo_Secure(0),
                'time' => time()
            );
            if (!empty($_POST['id'])) {
                if (is_numeric($_POST['id'])) {
                    $post_data = array(
                        'album_name' => Wo_Secure($_POST['album_name'])
                    );
                    $id        = Wo_UpdatePostData($_POST['id'], $post_data);
                }
            } else {
                $id = Wo_RegisterPost($post_data);
            }
            if (count($_FILES['postPhotos']['name']) > 0) {
                for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                    $fileInfo = array(
                        'file' => $_FILES["postPhotos"]["tmp_name"][$i],
                        'name' => $_FILES['postPhotos']['name'][$i],
                        'size' => $_FILES["postPhotos"]["size"][$i],
                        'type' => $_FILES["postPhotos"]["type"][$i],
                        'types' => 'jpg,png,jpeg,gif'
                    );
                    $file     = Wo_ShareFile($fileInfo, 1);
                    if (!empty($file)) {
                        $media_album = Wo_RegisterAlbumMedia($id, $file['filename']);
                    }
                }
            }
            $data = array(
                'status' => 200,
                'href' => Wo_SeoLink('index.php?link1=post&id=' . $id)
            );
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
}
if ($f == 'delete_album_image') {
    if (!empty($_GET['post_id']) && !empty($_GET['id'])) {
        if (Wo_DeleteImageFromAlbum($_GET['post_id'], $_GET['id']) === true) {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'register_page_invite') {
    if (!empty($_GET['user_id']) && !empty($_GET['page_id'])) {
        $register_invite = Wo_RegsiterInvite($_GET['user_id'], $_GET['page_id']);
        if ($register_invite === true) {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'register_group_add') {
    if (!empty($_GET['user_id']) && !empty($_GET['group_id'])) {
        $register_add = Wo_RegsiterGroupAdd($_GET['user_id'], $_GET['group_id']);
        if ($register_add === true) {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'mention') {
    $html_data  = array();
    $data_finel = array();
    $following  = Wo_GetFollowingSug(5, $_GET['term']);
    header("Content-type: application/json");
    echo json_encode(array(
        $following
    ));
    exit();
}
if ($f == 'skip_step') {
    if (!empty($_GET['type'])) {
        $types = array(
            'start_up_info',
            'startup_image',
            'startup_follow'
        );
        if (in_array($_GET['type'], $types)) {
            $register_skip = Wo_UpdateUserData($wo['user']['user_id'], array(
                $_GET['type'] => 1
            ));
            if ($register_skip === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_user_information_startup') {
    if (isset($_POST['user_id'])) {
        $Userdata = Wo_UserData($_POST['user_id']);
        if (!empty($Userdata['user_id'])) {
            $age_data = '00-00-0000';
            if (!empty($_POST['age_year']) || !empty($_POST['age_day']) || !empty($_POST['age_month'])) {
                if (empty($_POST['age_year']) || empty($_POST['age_day']) || empty($_POST['age_month'])) {
                    $errors[] = $error_icon . $wo['lang']['please_choose_correct_date'];
                } else {
                    $age_data = $_POST['age_year'] . '-' . $_POST['age_month'] . '-' . $_POST['age_day'];
                }
            }
            $Update_data = array(
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'country_id' => $_POST['country'],
                'birthday' => $age_data,
                'start_up_info' => 1
            );
            if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
                $data = array(
                    'status' => 200
                );
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'follow_users') {
    if (!empty($_POST['user'])) {
        $continue = false;
        $ids      = @explode(',', $_POST['user']);
        foreach ($ids as $id) {
            if (Wo_RegisterFollow($id, $wo['user']['user_id']) === true) {
                $continue = true;
            }
        }
        if ($continue == true) {
            if (Wo_UpdateUserData($wo['user']['user_id'], array(
                'startup_follow' => '1',
                'start_up' => '1'
            ))) {
                $data = array(
                    'status' => 200
                );
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'send_mails') {
    if ($wo['config']['emailNotification'] == 0) {
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    $send = Wo_SendMessageFromDB();
    if ($send) {
        $data = array(
            'status' => 200
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 're_cover') {
    if (isset($_POST['pos'])) {
        if ($_POST['cover_image'] != $wo['userDefaultCover']) {
            $from_top             = abs($_POST['pos']);
            $cover_image          = $_POST['cover_image'];
            $full_url_image       = Wo_GetMedia($_POST['cover_image']);
            $default_image        = $_POST['real_image'];
            $image_type           = $_POST['image_type'];
            $default_cover_width  = 918;
            $default_cover_height = 276;
            require_once("assets/import/thumbncrop.inc.php");
            $tb = new ThumbAndCrop();
            $tb->openImg($default_image);
            $newHeight = $tb->getRightHeight($default_cover_width);
            $tb->creaThumb($default_cover_width, $newHeight);
            $tb->setThumbAsOriginal();
            $tb->cropThumb($default_cover_width, 300, 0, $from_top);
            $tb->saveThumb($cover_image);
            $tb->resetOriginal();
            $tb->closeImg();
            $upload_s3 = Wo_UploadToS3($cover_image);
        }
        if (empty($full_url_image)) {
            $full_url_image = Wo_GetMedia($wo['userDefaultCover']);
        }
        $data = array(
            'status' => 200,
            'url' => $full_url_image
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'payment') {
    if (!isset($_GET['success'], $_GET['paymentId'], $_GET['PayerID'])) {
        header("Location: " . Wo_SeoLink('index.php?link1=oops'));
        exit();
    }
    $is_pro = 0;
    $stop   = 0;
    $user   = Wo_UserData($wo['user']['user_id']);
    if ($user['is_pro'] == 1) {
        $stop = 1;
        if ($user['pro_type'] == 1) {
            $time_ = time() - 604800;
            if ($user['pro_time'] > $time_) {
                $stop = 1;
            }
        } else if ($user['pro_type'] == 2) {
            $time_ = time() - 2629743;
            if ($user['pro_time'] > $time_) {
                $stop = 1;
            }
        } else if ($user['pro_type'] == 3) {
            $time_ = time() - 31556926;
            if ($user['pro_time'] > $time_) {
                $stop = 1;
            }
        }
    }
    if ($stop == 0) {
        $pro_types_array = array(
            1,
            2,
            3,
            4
        );
        $pro_type        = 0;
        if (!isset($_GET['pro_type']) || !in_array($_GET['pro_type'], $pro_types_array)) {
            header("Location: " . Wo_SeoLink('index.php?link1=oops'));
            exit();
        }
        $pro_type = $_GET['pro_type'];
        $payment  = Wo_CheckPayment($_GET['paymentId'], $_GET['PayerID']);
        if (is_array($payment)) {
            if (isset($payment['name'])) {
                if ($payment['name'] == 'PAYMENT_ALREADY_DONE' || $payment['name'] == 'MAX_NUMBER_OF_PAYMENT_ATTEMPTS_EXCEEDED') {
                    $is_pro = 1;
                }
            }
        } else if ($payment === true) {
            $is_pro = 1;
        }
    }
    if ($stop == 0) {
        $time = time();
        if ($is_pro == 1) {
            $update_array   = array(
                'is_pro' => 1,
                'pro_time' => time(),
                'verified' => 1,
                'pro_' => 1,
                'pro_type' => $pro_type
            );
            $mysqli         = Wo_UpdateUserData($wo['user']['user_id'], $update_array);
            $create_payment = Wo_CreatePayment($pro_type);
            if ($mysqli) {
                header("Location: " . Wo_SeoLink('index.php?link1=upgraded'));
                exit();
            }
        } else {
            header("Location: " . Wo_SeoLink('index.php?link1=oops'));
            exit();
        }
    } else {
        header("Location: " . Wo_SeoLink('index.php?link1=oops'));
        exit();
    }
}
if ($f == 'stripe_payment') {
    if (empty($_POST['stripeToken'])) {
        header("Location: " . Wo_SeoLink('index.php?link1=oops'));
        exit();
    }
    $token = $_POST['stripeToken'];
    try {
        $customer = \Stripe\Customer::create(array(
            'email' => 'deendoughouz@gmail.com',
            'source' => $token
        ));
        $charge   = \Stripe\Charge::create(array(
            'customer' => $customer->id,
            'amount' => $_POST['amount'],
            'currency' => 'usd'
        ));
        if ($charge) {
            $is_pro = 0;
            $stop   = 0;
            $user   = Wo_UserData($wo['user']['user_id']);
            if ($user['is_pro'] == 1) {
                $stop = 1;
                if ($user['pro_type'] == 1) {
                    $time_ = time() - 604800;
                    if ($user['pro_time'] > $time_) {
                        $stop = 1;
                    }
                } else if ($user['pro_type'] == 2) {
                    $time_ = time() - 2629743;
                    if ($user['pro_time'] > $time_) {
                        $stop = 1;
                    }
                } else if ($user['pro_type'] == 3) {
                    $time_ = time() - 31556926;
                    if ($user['pro_time'] > $time_) {
                        $stop = 1;
                    }
                }
            }
            if ($stop == 0) {
                $pro_types_array = array(
                    1,
                    2,
                    3,
                    4
                );
                $pro_type        = 0;
                if (!isset($_GET['pro_type']) || !in_array($_GET['pro_type'], $pro_types_array)) {
                    $data = array(
                        'status' => 200,
                        'error' => 'Pro type is not set'
                    );
                    header("Content-type: application/json");
                    echo json_encode($data);
                    exit();
                }
                $pro_type = $_GET['pro_type'];
                $is_pro   = 1;
            }
            if ($stop == 0) {
                $time = time();
                if ($is_pro == 1) {
                    $update_array   = array(
                        'is_pro' => 1,
                        'pro_time' => time(),
                        'verified' => 1,
                        'pro_' => 1,
                        'pro_type' => $pro_type
                    );
                    $mysqli         = Wo_UpdateUserData($wo['user']['user_id'], $update_array);
                    $create_payment = Wo_CreatePayment($pro_type);
                    if ($mysqli) {
                        if (!empty($_SESSION['ref']) && $wo['config']['affiliate_type'] == 1 && $wo['user']['referrer'] == 0) {
                            $ref_user_id = Wo_UserIdFromUsername($_SESSION['ref']);
                            if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                                $update_balance = Wo_UpdateBalance($ref_user_id, $wo['config']['amount_ref']);
                                $update_user    = Wo_UpdateUserData($wo['user']['user_id'], array(
                                    'referrer' => $ref_user_id,
                                    'src' => 'Referrer'
                                ));
                                unset($_SESSION['ref']);
                            }
                        }
                        $data = array(
                            'status' => 200,
                            'location' => Wo_SeoLink('index.php?link1=upgraded')
                        );
                        header("Content-type: application/json");
                        echo json_encode($data);
                        exit();
                    }
                } else {
                    $data = array(
                        'status' => 400,
                        'error' => 'Pro type is not set2'
                    );
                    header("Content-type: application/json");
                    echo json_encode($data);
                    exit();
                }
            } else {
                $data = array(
                    'status' => 400,
                    'error' => 'Pro type is not set3'
                );
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
        }
    }
    catch (Exception $e) {
        $data = array(
            'status' => 400,
            'error' => $e->getMessage()
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
if ($f == 'get_payment_method') {
    if (!empty($_GET['type'])) {
        $html            = '';
        $pro_types_array = array(
            1,
            2,
            3,
            4
        );
        if (in_array($_GET['type'], $pro_types_array)) {
            switch ($_GET['type']) {
                case 1:
                    $type        = 'week';
                    $description = 'Star package (1 week)';
                    if (strpos($wo['config']['weekly_price'], ".") !== false) {
                        $price = str_replace('.', "", $wo['config']['weekly_price']);
                    } else {
                        $price = $wo['config']['weekly_price'] . '00';
                    }
                    break;
                case 2:
                    $type        = 'month';
                    $description = 'Hot package (1 month)';
                    if (strpos($wo['config']['monthly_price'], ".") !== false) {
                        $price = str_replace('.', "", $wo['config']['monthly_price']);
                    } else {
                        $price = $wo['config']['monthly_price'] . '00';
                    }
                    break;
                case 3:
                    $type        = 'year';
                    $description = 'Ultima package (1 year)';
                    if (strpos($wo['config']['yearly_price'], ".") !== false) {
                        $price = str_replace('.', "", $wo['config']['yearly_price']);
                    } else {
                        $price = $wo['config']['yearly_price'] . '00';
                    }
                    break;
                case 4:
                    $type        = 'life-time';
                    $description = 'Vip package (life-time)';
                    if (strpos($wo['config']['lifetime_price'], ".") !== false) {
                        $price = str_replace('.', "", $wo['config']['lifetime_price']);
                    } else {
                        $price = $wo['config']['lifetime_price'] . '00';
                    }
                    break;
            }
            $load = Wo_LoadPage('modals/pay-go-pro');
            $load = str_replace('{pro_type}', $type, $load);
            $load = str_replace('{pro_type_id}', $_GET['type'], $load);
            $load = str_replace('{pro_type_description}', $description, $load);
            $load = str_replace('{pro_type_price}', $price, $load);
            if (!empty($load)) {
                $data = array(
                    'status' => 200,
                    'html' => $load
                );
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_paypal_url') {
    $data = array(
        'status' => 400,
        'url' => ''
    );
    if (isset($_POST['type'])) {
        $type2 = '';
        if (!empty($_POST['type2'])) {
            $type2 = $_POST['type2'];
        }
        $url = Wo_PayPal($_POST['type'], $type2);
        if (!empty($url['type'])) {
            if ($url['type'] == 'SUCCESS' && !empty($url['type'])) {
                $data = array(
                    'status' => 200,
                    'url' => $url['url']
                );
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'upgrade') {
    if (!isset($_GET['success'], $_GET['paymentId'], $_GET['PayerID'])) {
        header("Location: " . Wo_SeoLink('index.php?link1=oops'));
        exit();
    }
    $is_pro = 0;
    $stop   = 0;
    $user   = Wo_UserData($wo['user']['user_id']);
    if ($user['is_pro'] == 0) {
        $stop = 1;
    }
    if ($stop == 0) {
        $pro_types_array = array(
            1,
            2,
            3,
            4
        );
        $pro_type        = 0;
        if (!isset($_GET['pro_type']) || !in_array($_GET['pro_type'], $pro_types_array)) {
            header("Location: " . Wo_SeoLink('index.php?link1=oops'));
            exit();
        }
        $pro_type = $_GET['pro_type'];
        $payment  = Wo_CheckPayment($_GET['paymentId'], $_GET['PayerID']);
        if (is_array($payment)) {
            if (isset($payment['name'])) {
                if ($payment['name'] == 'PAYMENT_ALREADY_DONE' || $payment['name'] == 'MAX_NUMBER_OF_PAYMENT_ATTEMPTS_EXCEEDED') {
                    $is_pro = 1;
                }
            }
        } else if ($payment === true) {
            $is_pro = 1;
        }
    }
    if ($stop == 0) {
        $time = time();
        if ($is_pro == 1) {
            $update_array   = array(
                'pro_time' => time(),
                'pro_type' => $pro_type
            );
            $mysqli         = Wo_UpdateUserData($wo['user']['user_id'], $update_array);
            $create_payment = Wo_CreatePayment($pro_type);
            if ($mysqli) {
                if (!empty($_SESSION['ref']) && $wo['config']['affiliate_type'] == 1 && $wo['user']['referrer'] == 0) {
                    $ref_user_id = Wo_UserIdFromUsername($_SESSION['ref']);
                    if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                        $update_user    = Wo_UpdateUserData($wo['user']['user_id'], array(
                            'referrer' => $ref_user_id,
                            'src' => 'Referrer'
                        ));
                        $update_balance = Wo_UpdateBalance($ref_user_id, $wo['config']['amount_ref']);
                        unset($_SESSION['ref']);
                    }
                }
                header("Location: " . Wo_SeoLink('index.php?link1=upgraded'));
                exit();
            }
        } else {
            header("Location: " . Wo_SeoLink('index.php?link1=oops'));
            exit();
        }
    } else {
        header("Location: " . Wo_SeoLink('index.php?link1=oops'));
        exit();
    }
}
if ($f == 'invite_user') {
    if (empty($_POST['email'])) {
        $errors[] = $error_icon . $wo['lang']['please_check_details'];
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = $error_icon . $wo['lang']['email_invalid_characters'];
    } else if (Wo_EmailExists($_POST['email'])) {
        $errors[] = $error_icon . $wo['lang']['email_exists'];
    }
    if (empty($errors)) {
        $email             = Wo_Secure($_POST['email']);
        $message           = Wo_LoadPage('emails/invite');
        $send_message_data = array(
            'from_email' => $wo['config']['siteEmail'],
            'from_name' => $wo['config']['siteName'],
            'to_email' => $email,
            'to_name' => '',
            'subject' => 'invitation request',
            'charSet' => 'utf-8',
            'message_body' => $message,
            'is_html' => true
        );
        $send              = Wo_SendMessage($send_message_data);
        if ($send) {
            $data = array(
                'status' => 200,
                'message' => $success_icon . $wo['lang']['email_sent']
            );
        } else {
            $errors[] = $error_icon . $wo['lang']['processing_error'];
        }
    }
    header("Content-type: application/json");
    if (!empty($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'create_new_video_call') {
    if (empty($_GET['user_id2']) || empty($_GET['user_id1']) || Wo_CheckMainSession($hash_id) === false || $_GET['user_id1'] != $wo['user']['user_id']) {
        exit();
    }
    $accountSid   = $wo['config']['video_accountSid'];
    $apiKeySid    = $wo['config']['video_apiKeySid'];
    $apiKeySecret = $wo['config']['video_apiKeySecret'];
    $call_id      = substr(md5(microtime()), 0, 15);
    $call_id_2    = substr(md5(time()), 0, 15);
    $token        = new Services_Twilio_AccessToken($accountSid, $apiKeySid, $apiKeySecret, 3600, $call_id);
    $grant        = new Services_Twilio_Auth_ConversationsGrant();
    $grant->setConfigurationProfileSid($wo['config']['video_configurationProfileSid']);
    $token->addGrant($grant);
    $token_ = $token->toJWT();
    $token2 = new Services_Twilio_AccessToken($accountSid, $apiKeySid, $apiKeySecret, 3600, $call_id_2);
    $grant2 = new Services_Twilio_Auth_ConversationsGrant();
    $grant2->setConfigurationProfileSid($wo['config']['video_configurationProfileSid']);
    $token2->addGrant($grant2);
    $token_2    = $token2->toJWT();
    $insertData = Wo_CreateNewVideoCall(array(
        'access_token' => Wo_Secure($token_),
        'from_id' => Wo_Secure($_GET['user_id1']),
        'to_id' => Wo_Secure($_GET['user_id2']),
        'call_id' => Wo_Secure($call_id),
        'call_id_2' => $call_id_2,
        'access_token_2' => Wo_Secure($token_2)
    ));
    if ($insertData > 0) {
        $wo['calling_user'] = Wo_UserData($_GET['user_id2']);
        $data               = array(
            'status' => 200,
            'access_token' => $token_,
            'id' => $insertData,
            'url' => $wo['config']['site_url'] . '/video-call/' . $insertData,
            'html' => Wo_LoadPage('modals/calling'),
            'text_no_answer' => $wo['lang']['no_answer'],
            'text_please_try_again_later' => $wo['lang']['please_try_again_later']
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'check_for_answer') {
    if (!empty($_GET['id'])) {
        $selectData = Wo_CheckCallAnswer($_GET['id']);
        if ($selectData !== false) {
            $data = array(
                'status' => 200,
                'url' => $selectData['url'],
                'text_answered' => $wo['lang']['answered'],
                'text_please_wait' => $wo['lang']['please_wait']
            );
        } else {
            $check_declined = Wo_CheckCallAnswerDeclined($_GET['id']);
            if ($check_declined) {
                $data = array(
                    'status' => 400,
                    'text_call_declined' => $wo['lang']['call_declined'],
                    'text_call_declined_desc' => $wo['lang']['call_declined_desc']
                );
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'answer_call') {
    if (!empty($_GET['id'])) {
        $id    = Wo_Secure($_GET['id']);
        $data2 = Wo_GetAllDataFromCallID($id);
        $query = mysqli_query($sqlConnect, "UPDATE " . T_VIDEOS_CALLES . " SET `active` = 1 WHERE `id` = '$id'");
        if ($query) {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'decline_call') {
    if (!empty($_GET['id'])) {
        $id    = Wo_Secure($_GET['id']);
        $query = mysqli_query($sqlConnect, "UPDATE " . T_VIDEOS_CALLES . " SET `declined` = '1' WHERE `id` = '$id'");
        if ($query) {
            $data = array(
                'status' => 200
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'cancel_call') {
    $user_id = Wo_Secure($wo['user']['user_id']);
    $query   = mysqli_query($sqlConnect, "DELETE FROM " . T_VIDEOS_CALLES . " WHERE `from_id` = '$user_id'");
    if ($query) {
        $data = array(
            'status' => 200
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'get_no_posts_name') {
    $data = array(
        'name' => $wo['lang']['no_more_posts']
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'products') {
    if ($s == 'create' && Wo_CheckSession($hash_id) === true) {
        if (empty($_POST['name']) || empty($_POST['category']) || empty($_POST['description'])) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        } else if (empty($_POST['price'])) {
            $errors[] = $error_icon . $wo['lang']['please_choose_price'];
        } else if (!is_numeric($_POST['price'])) {
            $errors[] = $error_icon . $wo['lang']['please_choose_c_price'];
        } else if ($_POST['price'] == '0.00') {
            $errors[] = $error_icon . $wo['lang']['please_choose_price'];
        } else if (empty($_FILES['postPhotos']['name'])) {
            $errors[] = $error_icon . $wo['lang']['please_upload_image'];
        }
        if (isset($_FILES['postPhotos']['name'])) {
            $allowed = array(
                'gif',
                'png',
                'jpg',
                'jpeg'
            );
            for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                $new_string = pathinfo($_FILES['postPhotos']['name'][$i]);
                if (!in_array(strtolower($new_string['extension']), $allowed)) {
                    $errors[] = $error_icon . $wo['lang']['please_check_details'];
                }
            }
        }
        $type = 0;
        if (!empty($_POST['type'])) {
            $type = 1;
        }
        if (empty($errors)) {
            $price              = Wo_Secure($_POST['price']);
            $product_data_array = array(
                'user_id' => $wo['user']['user_id'],
                'name' => Wo_Secure($_POST['name']),
                'category' => Wo_Secure($_POST['category']),
                'description' => Wo_Secure($_POST['description']),
                'time' => Wo_Secure(time()),
                'price' => $price,
                'type' => $type,
                'active' => Wo_Secure(1)
            );
            $product_data       = Wo_RegisterProduct($product_data_array);
            $product_id         = 0;
            if (!$product_data) {
                $errors[] = $error_icon . $wo['lang']['please_check_details'];
                header("Content-type: application/json");
                echo json_encode(array(
                    'errors' => $errors
                ));
                exit();
            }
            $product_id = $product_data;
            $post_data  = array(
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'product_id' => Wo_Secure($product_id),
                'postPrivacy' => Wo_Secure(0),
                'time' => time()
            );
            $id         = Wo_RegisterPost($post_data);
            if (count($_FILES['postPhotos']['name']) > 0 && !empty($id) && $id > 0) {
                for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                    $fileInfo = array(
                        'file' => $_FILES["postPhotos"]["tmp_name"][$i],
                        'name' => $_FILES['postPhotos']['name'][$i],
                        'size' => $_FILES["postPhotos"]["size"][$i],
                        'type' => $_FILES["postPhotos"]["type"][$i],
                        'types' => 'jpg,png,jpeg,gif'
                    );
                    $file     = Wo_ShareFile($fileInfo, 1);
                    if (!empty($file)) {
                        $media_album = Wo_RegisterProductMedia($product_id, $file['filename']);
                    }
                }
            }
            $data = array(
                'status' => 200,
                'href' => Wo_SeoLink('index.php?link1=post&id=' . $id)
            );
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'edit' && Wo_CheckSession($hash_id) === true) {
        if (empty($_POST['name']) || empty($_POST['category']) || empty($_POST['description'])) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        } else if (empty($_POST['price'])) {
            $errors[] = $error_icon . $wo['lang']['please_choose_price'];
        } else if (!is_numeric($_POST['price'])) {
            $errors[] = $error_icon . $wo['lang']['please_choose_c_price'];
        } else if ($_POST['price'] == '0.00') {
            $errors[] = $error_icon . $wo['lang']['please_choose_price'];
        }
        if (isset($_FILES['postPhotos']['name'])) {
            $allowed = array(
                'gif',
                'png',
                'jpg',
                'jpeg'
            );
            for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                $new_string = pathinfo($_FILES['postPhotos']['name'][$i]);
                if (!in_array(strtolower($new_string['extension']), $allowed)) {
                    $errors[] = $error_icon . $wo['lang']['please_check_details'];
                }
            }
        }
        $type = 0;
        if (!empty($_POST['type'])) {
            $type = 1;
        }
        if (empty($errors)) {
            $price              = Wo_Secure($_POST['price']);
            $product_data_array = array(
                'name' => $_POST['name'],
                'category' => $_POST['category'],
                'description' => $_POST['description'],
                'price' => $price,
                'type' => $type
            );
            $product_data       = Wo_UpdateProductData($_POST['product_id'], $product_data_array);
            $product_id         = $_POST['product_id'];
            if (!$product_data) {
                $errors[] = $error_icon . $wo['lang']['please_check_details'];
                header("Content-type: application/json");
                echo json_encode(array(
                    'errors' => $errors
                ));
                exit();
            }
            $id = Wo_GetPostIDFromProdcutID($product_id);
            if (isset($_FILES['postPhotos']['name'])) {
                if (count($_FILES['postPhotos']['name']) > 0 && !empty($id) && $id > 0) {
                    for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                        $fileInfo = array(
                            'file' => $_FILES["postPhotos"]["tmp_name"][$i],
                            'name' => $_FILES['postPhotos']['name'][$i],
                            'size' => $_FILES["postPhotos"]["size"][$i],
                            'type' => $_FILES["postPhotos"]["type"][$i],
                            'types' => 'jpg,png,jpeg,gif'
                        );
                        $file     = Wo_ShareFile($fileInfo, 1);
                        if (!empty($file)) {
                            $media_album = Wo_RegisterProductMedia($product_id, $file['filename']);
                        }
                    }
                }
            }
            $data = array(
                'status' => 200,
                'href' => Wo_SeoLink('index.php?link1=post&id=' . $id)
            );
        }
        header("Content-type: application/json");
        if (isset($errors)) {
            echo json_encode(array(
                'errors' => $errors
            ));
        } else {
            echo json_encode($data);
        }
        exit();
    }
}
if ($f == 'search_products') {
    $html  = '';
    $array = array(
        'limit' => 15
    );
    if (!empty($_POST['c_id'])) {
        $array['c_id'] = Wo_Secure($_POST['c_id']);
    }
    if (!empty($_POST['value'])) {
        $array['keyword'] = $_POST['value'];
    }
    $result = Wo_GetProducts($array);
    foreach ($result as $key => $wo['product']) {
        $html .= Wo_LoadPage('products/products-list');
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'load_more_products') {
    $html  = '';
    $array = array(
        'limit' => 10
    );
    if (!empty($_POST['c_id'])) {
        $array['c_id'] = Wo_Secure($_POST['c_id']);
    }
    if (!empty($_POST['last_id'])) {
        $array['after_id'] = $_POST['last_id'];
    }
    $result = Wo_GetProducts($array);
    foreach ($result as $key => $wo['product']) {
        $html .= Wo_LoadPage('products/products-list');
    }
    $data = array(
        'status' => 200,
        'html' => $html
    );
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'vote_up') {
    if (!empty($_GET['id']) && Wo_CheckMainSession($hash_id) === true) {
        $post_id = Wo_GetPostIDFromOptionID($_GET['id']);
        if (Wo_IsPostVoted($post_id, $wo['user']['user_id'])) {
            $data = array(
                'status' => 400,
                'text' => $wo['lang']['you_have_already_voted']
            );
            header("Content-type: application/json");
            echo json_encode($data);
            exit();
        } else {
            $vote = Wo_VoteUp($_GET['id'], $wo['user']['user_id']);
            if ($vote) {
                $data = array(
                    'status' => 200,
                    'votes' => Ju_GetPercentageOfOptionPost($post_id)
                );
            }
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'upload_image') {
    if (isset($_FILES['image']['name'])) {
        $fileInfo = array(
            'file' => $_FILES["image"]["tmp_name"],
            'name' => $_FILES['image']['name'],
            'size' => $_FILES["image"]["size"],
            'type' => $_FILES["image"]["type"]
        );
        $media    = Wo_ShareFile($fileInfo);
        if (!empty($media)) {
            $mediaFilename = $media['filename'];
            $mediaName     = $media['name'];
            $data          = array(
                'status' => 200,
                'image' => Wo_GetMedia($mediaFilename),
                'image_src' => $mediaFilename
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'request_payment') {
    if (Wo_CheckSession($hash_id) === true) {
        if (empty($_POST['paypal_email']) || empty($_POST['amount'])) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        } else {
            if (Wo_IsUserPaymentRequested($wo['user']['user_id']) === true) {
                $errors[] = $error_icon . $wo['lang']['you_have_pending_request'];
            } else if (!filter_var($_POST['paypal_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = $error_icon . $wo['lang']['email_invalid_characters'];
            } else if (!is_numeric($_POST['amount'])) {
                $errors[] = $error_icon . $wo['lang']['invalid_amount_value'];
            } else if (($wo['user']['balance'] < $_POST['amount'])) {
                $errors[] = $error_icon . $wo['lang']['invalid_amount_value_your'] . ' $' . $wo['user']['balance'];
            } else if ($wo['config']['m_withdrawal'] > $_POST['amount']) {
                $errors[] = $error_icon . $wo['lang']['invalid_amount_value_withdrawal'] . ' $' . $wo['config']['m_withdrawal'];
            }
            if (empty($errors)) {
                $userU          = Wo_UpdateUserData($wo['user']['user_id'], array(
                    'paypal_email' => $_POST['paypal_email']
                ));
                $insert_payment = Wo_RequestNewPayment($wo['user']['user_id'], $_POST['amount']);
                if ($insert_payment) {
                    $update_balance = Wo_UpdateBalance($wo['user']['user_id'], $_POST['amount'], '-');
                    $data           = array(
                        'status' => 200,
                        'message' => $success_icon . $wo['lang']['you_request_sent']
                    );
                }
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}
if ($f == 'turn-off-sound') {
    if (Wo_CheckMainSession($hash_id) === true) {
        $num = 0;
        $message = '<i class="fa fa-volume-up progress-icon" data-icon="volume-up"></i> ' . $wo['lang']['turn_off_notification'] . '</span>';
        if ($wo['user']['notifications_sound'] == 0) {
            $num = 1;
            $message = '<i class="fa fa-volume-off progress-icon" data-icon="volume-off"></i> ' . $wo['lang']['turn_on_notification'] . '</span>';
        }
        $update = Wo_UpdateUserData($wo['user']['user_id'], array('notifications_sound' => $num));
        if ($update) {
            $data = array('status' => 200, 'message' => $message);
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
if ($f == 'update_order_by') {
    if (Wo_CheckMainSession($hash_id) === true) {
        $type = 0;
        if ($_GET['type'] == 1) {
            $type = 1;
        }
        $update = Wo_UpdateUserData($wo['user']['user_id'], array('order_posts_by' => $type));
        if ($update) {
            $data = array('status' => 200);
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}

mysqli_close($sqlConnect);
unset($wo);
?>