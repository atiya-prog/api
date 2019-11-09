<?php

$end_point = 'https://accounts.google.com/o/oauth2/v2/auth';
$client_id = 'YOUR_ID';
$client_secret = 'YOUR_SECRET';
$redirect_uri = 'http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]';                  //  http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"] or urn:ietf:wg:oauth:2.0:oob
$scope = 'https://www.googleapis.com/auth/drive.metadata.readonly';


$authUrl = $end_point.'?'.http_build_query([
    'client_id'              => $client_id,
    'redirect_uri'           => $redirect_uri,              
    'scope'                  => $scope,
    'access_type'            => 'offline',
    'include_granted_scopes' => 'true',
    'state'                  => 'state_parameter_passthrough_value',
    'response_type'          => 'code',
]);

echo '<a href = "'.$authUrl.'">Authorize</a></br>';


// Generate new Access Token and Refresh Token if token.json doesn't exist
if ( !file_exists('token.json') ){
    
    if ( isset($_GET['code'])){
        $code = $_GET['code'];
    }else{
        return;
    } 

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://accounts.google.com/o/oauth2/token");
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code'          => $code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri,
        'grant_type'    => 'authorization_code',
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close ($ch);
    
    file_put_contents('token.json', $response);
}
else{
    $response = file_get_contents('token.json');
    $array = json_decode($response);
    $access_token = $array->access_token;
    $refresh_token = $array->refresh_token;

    // Check if the access token already expired
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token='.$access_token); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $error_response = curl_exec($ch);
    $array = json_decode($error_response);
    
    if( isset($array->error)){
        
        // Generate new Access Token using old Refresh Token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://accounts.google.com/o/oauth2/token");
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'refresh_token'  => $refresh_token,
            'grant_type'    => 'refresh_token',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close ($ch);
    }  
}

var_dump($response);