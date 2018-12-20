<?php
/*
 *  Author: github.com/pokemonjpups
 *  Requires: auth/grabToken.php
 *  Location: groups/handleJoinRequest.php
 *  Description: Grabs & Accepts or Declines a
 *               join request specified by the
 *               User's Username
 *
 */

function getJoinRequest($username, $groupid, $cookie) {
    require('../auth/grabToken.php');
    $token = getTokenWithCookie($cookie);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://www.roblox.com/groups/$groupid/joinrequests-html?username=$username");
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: $cookie"));
    $result = curl_exec ($ch);
    //return $result;
    $matchstuff = "/data.rbx.join.request.........../";
    $datacheck = preg_match($matchstuff, $result, $match);
    $joinid = substr($match[0], 23);
    curl_close ($ch);
    return $joinid ;
}

function acceptJoinRequest($requestid, $cookie) {
    require('../auth/grabToken.php');
    $token = getTokenWithCookie($cookie);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://www.roblox.com/group/handle-join-request");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "groupJoinRequestId=$requestid");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: $cookie", "X-CSRF-TOKEN: $token"));
    $result = curl_exec ($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode === 200) {
        //ROBLOX still returns 200 even if the group is public, not sure why but it works for now
        return true;
    }else{
        return false;
    }
    curl_close ($ch);
}

function declineJoinRequest($requestid, $cookie) {
    require('../auth/grabToken.php');
    $token = getTokenWithCookie($cookie);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://www.roblox.com/group/handle-join-request");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "groupJoinRequestId=$requestid&accept=false");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: $cookie", "X-CSRF-TOKEN: $token"));
    $result = curl_exec ($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode === 200) {
        //ROBLOX still returns 200 even if the group is public, not sure why but it works for now
        return true;
    }else{
        return false;
    }
    curl_close ($ch);
}

/* Example Usage:
 *  $cookie = ".ROBLOSECURITY=_";
 *  $joinRequest = getJoinRequest("ROBLOX", 1, $cookie);
 *  echo acceptJoinRequest($joinRequest, $cookie)
 */
