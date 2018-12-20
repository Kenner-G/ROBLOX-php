<?php
/*
 *  Author: github.com/pokemonjpups
 *  Requires: N/A
 *  Location: misc/userIdToUserName.php
 *  Description: Converts the Specified UserID
 *               to a UserName
 *
 */

function getUsernameFromID($id) {
    $url = "https://api.roblox.com/users/$id";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT,
        "Mozilla/5.0 (X11; CrOS x86_64 10032.86.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.140 Safari/537.36");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_HTTPHEADER, array(
        "Content-Length: 0",
    ));

    $result = curl_exec ($ch);
    curl_close ($ch);
    $jsonresult = json_decode($result, true);
    return $jsonresult['Username'];
}