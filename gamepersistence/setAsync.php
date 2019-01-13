<?php
/*
 *  Author: github.com/pokemonjpups
 *  Requires: auth/grabToken.php
 *  Location: gamepersistence/setAsync.php
 *  Description: Sets datastore info via the specified
 *               place id and datastore name/
 *key
 */

function setAsync($cookie, $placeid, $dataStoreName, $dataStoreKey, $dataStoreValue) {
    // .ROBLOSECURITY Cookie, The UserID of the User to payout, the GroupID, and the Amount of ROBUX
    require('../auth/grabToken.php');
    $xcsrftoken = getTokenWithCookie($cookie);
    $url = 'https://gamepersistence.roblox.com/persistence/set?placeId=' . $placeid . '&key=' . $dataStoreName . '&&type=standard&scope=global&target=' . $dataStoreKey . '&valueLength=' . strlen($dataStoreValue);
    $postdata = http_build_query(array(
        "value" => $dataStoreValue,
    ));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Accept: */*",
        "Content-Type: application/x-www-form-urlencoded",
        "PlayerCount: 1",
        "Requester: Client",
        "Roblox-Game-Id: 00000000-0000-0000-0000-000000000000",
        "Roblox-Place-Id: $placeid",
        "Connection: keep-alive",
        "Host: gamepersistence.roblox.com",
        "User-Agent: RobloxStudio/WinInet",
        "X-CSRF-TOKEN: $xcsrftoken",
        "Cookie: $cookie",));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    $result = curl_exec($ch);
    return $result;
}