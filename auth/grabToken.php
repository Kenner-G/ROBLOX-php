<?php
/*
 *  Author: github.com/pokemonjpups
 *  Requires: N/A
 *  Location: auth/grabToken.php
 *  Description: Grabs the X-CSRF-Token for the Provided
 *               Cookie (Or IP, if no cookie is provided)
 *
 */

function getTokenWithCookie($cookie) {
    // Grab Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://api.roblox.com/sign-out/v1");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT,
        "Mozilla/5.0 (X11; CrOS x86_64 10032.86.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.140 Safari/537.36");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: 0',"Cookie: $cookie"));
    $result = curl_exec ($ch);
    $matchstuff = "/X-CSRF-TOKEN: ............/";
    $xcsrftokencheck = preg_match($matchstuff, $result, $match);
    $xcsrtoken = substr($match[0], 14);
    curl_close ($ch);
    return $xcsrtoken;
}

function getTokenWithoutCookie() {
    // Grab Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://api.roblox.com/sign-out/v1");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: 0'));
    $result = curl_exec ($ch);
    $matchstuff = "/X-CSRF-TOKEN: ............/";
    $xcsrftokencheck = preg_match($matchstuff, $result, $match);
    $xcsrtoken = substr($match[0], 14);
    curl_close ($ch);
    return $xcsrtoken;
}