<?php
/*
 *  Author: github.com/pokemonjpups
 *  Requires: auth/grabToken.php
 *  Location: groups/changeGroupOwner.php
 *  Description: Changes the owner of the Specified
 *               Group to the Specified Username
 *
 */

function changeGroupOwner($groupId, $newOwnerUsername, $cookie) {
    require('../auth/grabToken.php');
    $xcsrtoken = getTokenWithCookie($cookie);
    $url = "https://www.roblox.com/group/change-group-owner";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT,
        "Mozilla/5.0 (X11; CrOS x86_64 10032.86.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.140 Safari/537.36");
    $postdata = array(
        'groupId'=>$groupId,
        'newOwnerName'=>$newOwnerUsername,);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-CSRF-TOKEN: $xcsrtoken",
        "Content-Type: application/json",
        "Accept: application/json",
        "Cookie: $cookie",
    ));
    $result = curl_exec ($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);

    $jsonresult = json_decode($result, true);
    if ($httpcode == 302) { //302 is the "success" response, anything else is an error
        return true;
    }else{
        return false;
    }
}