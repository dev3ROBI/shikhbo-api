<?php
// google_login.php

require_once 'connection.php';
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept-Language, X-App-Language');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

function debug_log($msg){
    file_put_contents(__DIR__.'/google_login_debug.log', "[".date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
}

register_shutdown_function(function(){
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        debug_log("FATAL: ".json_encode($err));
        http_response_code(500);
        echo json_encode(["status"=>"error","message"=>"Server fatal error. Check logs."]);
        exit;
    }
});

function getClientLanguage(){
    $supported=['en','bn']; $default='en';
    if(isset($_SERVER['HTTP_X_APP_LANGUAGE'])){
        $lang=substr($_SERVER['HTTP_X_APP_LANGUAGE'],0,2);
        if(in_array($lang,$supported)) return $lang;
    }
    if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
        $langs=explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach($langs as $l){$l=substr(trim($l),0,2);if(in_array($l,$supported))return $l;}
    }
    return $default;
}

function getMessage($key,$lang='en'){
    $msg=[
        'en'=>[
            'invalid_request_method'=>"Invalid request method. Only POST allowed.",
            'no_input_data'=>"No input data received",
            'invalid_json'=>"Invalid JSON data",
            'google_token_required'=>"Google token is required",
            'email_required'=>"Email is required",
            'invalid_google_token'=>"Invalid Google token",
            'email_mismatch'=>"Email mismatch between token and provided email",
            'email_registered_password'=>"This email is registered with email/password. Use email login.",
            'account_suspended'=>"Account suspended.",
            'account_inactive'=>"Account inactive.",
            'token_generation_failed'=>"Failed to generate auth token",
            'user_creation_failed'=>"Failed to create user",
            'login_successful'=>"Login successful",
            'registration_successful'=>"Registration successful",
            'server_error'=>"Server error occurred"
        ],
        'bn'=>[
            'invalid_request_method'=>"ভুল অনুরোধ পদ্ধতি। শুধুমাত্র POST অনুমোদিত।",
            'no_input_data'=>"কোনো ডেটা পাওয়া যায়নি",
            'invalid_json'=>"JSON ডেটা সঠিক নয়",
            'google_token_required'=>"Google টোকেন প্রয়োজন",
            'email_required'=>"ইমেইল প্রয়োজন",
            'invalid_google_token'=>"Google টোকেন সঠিক নয়",
            'email_mismatch'=>"টোকেন ও ইমেইল মিলছে না",
            'email_registered_password'=>"এই ইমেইলটি পাসওয়ার্ড দিয়ে নিবন্ধিত। ইমেইল লগইন ব্যবহার করুন।",
            'account_suspended'=>"অ্যাকাউন্ট স্থগিত করা হয়েছে।",
            'account_inactive'=>"অ্যাকাউন্ট নিষ্ক্রিয়।",
            'token_generation_failed'=>"টোকেন তৈরি ব্যর্থ হয়েছে",
            'user_creation_failed'=>"ব্যবহারকারী তৈরি ব্যর্থ হয়েছে",
            'login_successful'=>"লগইন সফল",
            'registration_successful'=>"নিবন্ধন সফল",
            'server_error'=>"সার্ভার ত্রুটি ঘটেছে"
        ]
    ];
    return $msg[$lang][$key] ?? $msg['en'][$key];
}

// ---- VERIFY GOOGLE TOKEN ----
$EXPECTED_AUDIENCES = [
    '151985259285-nvemiiq9gg5lh7ap27vcrv25jv930ddm.apps.googleusercontent.com',
    '151985259285-9vp42do9jbkl0gv5rv25hhi3u74t7sp9.apps.googleusercontent.com'
];

function verifyGoogleToken($token){
    global $EXPECTED_AUDIENCES;
    $info=@file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=".$token);
    if(!$info) return false;
    $data=json_decode($info,true);
    if(!$data||empty($data['aud'])||!in_array($data['aud'],$EXPECTED_AUDIENCES)) return false;
    return $data;
}

// ---- IMAGE SAVE ----
function downloadAndSaveImage($url,$uid){
    if(!$url||!filter_var($url,FILTER_VALIDATE_URL))return null;
    $dir=__DIR__.'/uploads/profiles/';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $ext=pathinfo(parse_url($url,PHP_URL_PATH),PATHINFO_EXTENSION)?:'jpg';
    $filename="profile_{$uid}_".uniqid().".$ext";
    $path=$dir.$filename;
    $img=@file_get_contents($url);
    if(!$img){return null;}
    file_put_contents($path,$img);
    $base=(isset($_SERVER['HTTPS'])?'https':'http')."://".$_SERVER['HTTP_HOST'];
    $apiPath=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/');
    return ['file_path'=>$path,'image_url'=>$base.$apiPath.'/uploads/profiles/'.$filename];
}

function saveUserImage($uid,$path,$url){
    global $conn;
    $conn->query("UPDATE user_images SET is_primary=0 WHERE user_id=$uid AND image_type='profile'");
    $stmt=$conn->prepare("INSERT INTO user_images (user_id,image_type,image_path,image_url,is_primary,created_at) VALUES (?,?,?,?,1,NOW())");
    $type='profile';
    $stmt->bind_param("isss",$uid,$type,$path,$url);
    $stmt->execute();
    $stmt->close();
    $stmt2=$conn->prepare("UPDATE users SET profile_image=?, updated_at=NOW() WHERE id=?");
    $stmt2->bind_param("si",$url,$uid);
    $stmt2->execute();
    $stmt2->close();
}

// ---- REFERRAL ----
function validateReferralCode($code){
    global $conn;
    $stmt=$conn->prepare("SELECT id FROM users WHERE referral_code=? AND status='active' LIMIT 1");
    $stmt->bind_param("s",$code);
    $stmt->execute();
    $res=$stmt->get_result();
    $u=$res->fetch_assoc();
    $stmt->close();
    return $u?$u['id']:null;
}

function logReferral($referrer,$newuser,$code){
    global $conn;
    $stmt=$conn->prepare("INSERT INTO referral_logs (referrer_id,new_user_id,referral_code_used,status,reward_amount,created_at) VALUES (?,?,?,'completed',5.00,NOW())");
    $stmt->bind_param("iis",$referrer,$newuser,$code);
    $stmt->execute();
    $logid=$stmt->insert_id;
    $stmt->close();
    $type='signup_bonus'; $amount=5.00;
    $stmt2=$conn->prepare("INSERT INTO referral_rewards (user_id,referral_log_id,reward_type,amount,status,created_at,credited_at) VALUES (?,?,?,?,'credited',NOW(),NOW())");
    $stmt2->bind_param("iisd",$referrer,$logid,$type,$amount);
    $stmt2->execute();
    $stmt2->close();
}

function generateToken($l=64){return bin2hex(random_bytes($l/2));}

// ---- MAIN ----
$lang=getClientLanguage();
debug_log("=== NEW GOOGLE LOGIN REQUEST ===");
debug_log("Client Language: " . $lang);

// Check database connection first
if ($conn->connect_error) {
    debug_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>getMessage('server_error',$lang)]);
    exit;
}

try {
    if($_SERVER['REQUEST_METHOD']!=='POST'){
        throw new Exception(getMessage('invalid_request_method',$lang));
    }

    $input=file_get_contents("php://input");
    if(!$input){
        throw new Exception(getMessage('no_input_data',$lang));
    }

    $data=json_decode($input,true);
    if(!$data){
        throw new Exception(getMessage('invalid_json',$lang));
    }

    $token=$data['google_token']??null;
    $email=$data['email']??null;
    $name=$data['name']??null;
    $referral_code_used=$data['referral_code']??null;

    debug_log("Processing Google login for email: " . $email);

    // Validate input - use exceptions instead of direct exit
    if(!$token){
        throw new Exception(getMessage('google_token_required',$lang));
    }
    if(!$email){
        throw new Exception(getMessage('email_required',$lang));
    }

    $gdata=verifyGoogleToken($token);
    if(!$gdata){
        throw new Exception(getMessage('invalid_google_token',$lang));
    }
    if(strtolower($gdata['email'])!==strtolower($email)){
        throw new Exception(getMessage('email_mismatch',$lang));
    }

    $stmt=$conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res=$stmt->get_result();
    $u=$res->fetch_assoc();
    $stmt->close();

    $did=$data['device_info']['device_id']??null;
    $ip=$data['device_info']['ip_address']??($_SERVER['REMOTE_ADDR']??null);
    $model=$data['device_info']['device_model']??null;
    $osv=$data['device_info']['os_version']??null;
    $appv=$data['device_info']['app_version']??null;
    $gid=$gdata['sub'];

    if($u){
        debug_log("Existing user found - ID: " . $u['id']);
        
        if($u['google_login']==0){
            throw new Exception(getMessage('email_registered_password',$lang));
        }
        if($u['status']!=='active'){
            throw new Exception(getMessage('account_inactive',$lang));
        }
        
        $uid=$u['id'];
        $stmt=$conn->prepare("UPDATE users SET google_id=?,device_id=?,ip_address=?,device_model=?,os_version=?,app_version=?,last_login=NOW(),updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssssssi",$gid,$did,$ip,$model,$osv,$appv,$uid);
        $stmt->execute();
        $stmt->close();
    } else {
        debug_log("Creating new user for email: " . $email);
        
        $referrerId=null;
        if($referral_code_used){
            $referrerId=validateReferralCode($referral_code_used);
        }
        $referral_code='REF'.substr(md5(uniqid()),0,8);
        $googleLogin=1;
        $status='active';

        $stmt=$conn->prepare("INSERT INTO users 
            (name,email,google_login,status,referral_code,referred_by,google_id,device_id,ip_address,device_model,os_version,app_version,created_at,updated_at,last_login)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),NOW())");
        
        if(!$stmt) {
            throw new Exception(getMessage('user_creation_failed',$lang));
        }
        
        $stmt->bind_param("ssississssss",$name,$email,$googleLogin,$status,$referral_code,$referrerId,$gid,$did,$ip,$model,$osv,$appv);
        
        if(!$stmt->execute()) {
            throw new Exception(getMessage('user_creation_failed',$lang));
        }
        
        $uid=$stmt->insert_id;
        $stmt->close();

        $pic=$gdata['picture']??null;
        if($pic){
            $img=downloadAndSaveImage($pic,$uid);
            if($img){
                saveUserImage($uid,$img['file_path'],$img['image_url']);
            }
        }
        if($referrerId){
            logReferral($referrerId,$uid,$referral_code_used);
        }
    }

    $tok=generateToken(64);
    $exp=date('Y-m-d H:i:s',strtotime('+30 days'));
    $stmt=$conn->prepare("INSERT INTO user_tokens (user_id,token,expires_at,created_at) VALUES (?,?,?,NOW())");
    $stmt->bind_param("iss",$uid,$tok,$exp);
    
    if(!$stmt->execute()) {
        throw new Exception(getMessage('token_generation_failed',$lang));
    }
    
    $stmt->close();

    // Get complete user data for response
    $stmt = $conn->prepare("SELECT name, email, referral_code, profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userData = $userResult->fetch_assoc();
    $stmt->close();

    $response = [
        'status'=>'success',
        'message'=>$u ? getMessage('login_successful',$lang) : getMessage('registration_successful',$lang),
        'user_id'=>$uid,
        'token'=>$tok,
        'email'=>$userData['email'],
        'name'=>$userData['name'],
        'referral_code'=>$userData['referral_code'],
        'profile_image'=>$userData['profile_image'] ?: '',
        'login_method'=>'google'
    ];

    debug_log("Google login successful for user ID: " . $uid);
    echo json_encode($response);

} catch (Exception $e) {
    $error_message = $e->getMessage();
    debug_log("ERROR: " . $error_message);
    http_response_code(400);
    echo json_encode([
        'status'=>'error',
        'message'=>$error_message
    ]);
}

debug_log("=== GOOGLE LOGIN REQUEST COMPLETED ===\n");
?>
