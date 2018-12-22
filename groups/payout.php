<?php

function payout($cookie, $userid, $group, $amnt) {
    // .ROBLOSECURITY Cookie, The UserID of the User to payout, the GroupID, and the Amount of ROBUX
    require('../auth/grabToken.php');
    $xcsrftoken = getTokenWithCookie($cookie);
    $url = 'https://www.roblox.com/groups/'.$group.'/one-time-payout/false';
    $jsonpost = "percentages=" . urlencode(json_encode(array($userid    =>  $amnt)));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-CSRF-TOKEN: $xcsrftoken",
        'Connection: keep-alive',
        'X-Requested-With: XMLHttpRequest',
        "Cookie: $cookie",));
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.roblox.com/my/groupadmin.aspx?gid='.$group.'#nav-payouts');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonpost);

    $result = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_status == 200) {
        return true;
    }else{
        return false;
    }
}