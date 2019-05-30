<?php
// For Debugging...
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
/*
    -- ROBLOX-PHP API --------------------------------------------------------
    -- -- By: Beak                                                       -- --
    -- -- Special thanks to Sparkle for the inspiration :')              -- --
    -- -- Description: Communicates with ROBLOX's Web API                -- --
    -- -- Requirements: cURL, PHP7(.0|.1|.2)                             -- --
*/

class ROBLOX
{
    // -------------------------
    // -- GLOBAL VARIABLES    --
    // -- Description: Used in a variety of functions below.
    // -------------------------

    private $defaultPostHeaders = array("Accept"=>"*","User-Agent"=>"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.119 Safari/537.36");
    private $defaultGetHeaders = array("Accept"=>"*","User-Agent"=>"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.119 Safari/537.36");

    // -------------------------
    // -- PROTECTED FUNCTIONS --
    // -- Description: Functions used by Other Functions in this file
    // -------------------------

    // file_get_contents replacement
    private function file_get_contents_curl($url, $extraOptions = 0)
    {
    	$ch = curl_init();
    	
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->defaultGetHeaders);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        if ($extraOptions !== 0) {
            if ($extraOptions["ReturnStatusCode"] === true) {
                $data = array("data"=>$data,"statuscode"=> curl_getinfo($ch, CURLINFO_HTTP_CODE));
            }
        }
    	
    	curl_close($ch);
    	
    	return $data;
    }

    // Generic GET Request with Headers
    private function GetRequest($url, $headers, $extraOptions = 0)
    {
        $ch = curl_init();
    	
    	if ($extraOptions !== 0) {
            if (isset($extraOptions["Header"])) {
                if ($extraOptions["Header"] === true) {
                    curl_setopt($ch, CURLOPT_HEADER, 1);
                }else{
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                }
            }else{
                curl_setopt($ch, CURLOPT_HEADER, 0);
            }
        }
        $headerarray = array();
        foreach ($headers as $key=>$value) {
            $headerarray[] = $key . ": " . $value;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerarray);
    	
        $data = curl_exec($ch);
        if ($extraOptions !== 0) {
            if (isset($extraOptions["ReturnStatusCode"])) {
                if ($extraOptions["ReturnStatusCode"] === true) {
                    $data = array("data"=>$data,"statuscode"=> curl_getinfo($ch, CURLINFO_HTTP_CODE));
                }
            }
        }
    	curl_close($ch);
    	
    	return $data;
    }

    // Generic POST Request with Headers
    private function postRequest($url, $data, $headers, $extraOptions = 0)
    {
        $ch = curl_init();
            
        if ($extraOptions !== 0) {
            if ($extraOptions["Header"] === true) {
                curl_setopt($ch, CURLOPT_HEADER, 1);
            }else{
                curl_setopt($ch, CURLOPT_HEADER, 0);
            }
        }
        $headerarray = array();
        foreach ($headers as $key=>$value) {
            $headerarray[] = $key . ": " . $value;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($extraOptions !== 0) {
            if (isset($extraOptions["CustomRequest"])) {
                if ($extraOptions["CustomRequest"] !== null) {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $extraOptions["CustomRequest"]);
                }else{
                    curl_setopt($ch, CURLOPT_POST, 1);
                }
            }else{
                curl_setopt($ch, CURLOPT_POST, 1);
            }
        }else{
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerarray);
            
        $data = curl_exec($ch);

        if ($extraOptions !== 0) {
            if ($extraOptions["ReturnStatusCode"] === true) {
                $data = array("data"=>$data,"statuscode"=> curl_getinfo($ch, CURLINFO_HTTP_CODE));
            }
        }

        curl_close($ch);
            
        return $data;
    }

    // Retrieves ASP.NET Verification Tokens (For Legacy Pages)
    // @Return: array("header"=>string,"form"=>string)
    private function retrieveVerificationTokens($headers, $url = "https://www.roblox.com/places/create")
    {
        $headers["Referer"] = $url;
        $data = $this->getRequestWithCookie($url, $headers, array("Header"=>true));
        preg_match("/<input name=\"__RequestVerificationToken\" type=\"hidden\" value=\".+\"/", $data, $matches);
        preg_match('/__RequestVerificationToken=?.+;/', $data, $headertoken);
        $r = array("header"=>$headertoken[0],"form"=>substr($matches[0], 62, -1));
        return $r;
    }

    // Retrieve X-CSRF-Token
    // @Return: array("success"=>bool,"token"=>string)
    private function retrieveXSRF($isAuthenticated = false, $headers = 0, $body = "{}")
    {
        if ($headers === 0) {
            $headers = $this->defaultPostHeaders;
            $data = $this->postRequest("https://auth.roblox.com/v2/login", $body, $headers, array("Header"=>true));
        }else{
            if ($isAuthenticated === true) {
                $data = $this->postRequest("https://auth.roblox.com/v2/logout", "{}", $headers, array("Header"=>true));
            }else{
                $data = $this->postRequest("https://auth.roblox.com/v2/login", $body, $headers, array("Header"=>true));
            }
        }
        preg_match('/X-CSRF-TOKEN: ............/', $data, $matches);
        if (substr($matches[0], 14) !== null and substr($matches[0], 14) !== "") {
            return array("success"=>true,"token"=>substr($matches[0], 14));
        }else{
            return array("success"=>false);
        }
    }

    // Post Request w/ Cookie
    // @Return: string
    private function postRequestWithCookie($url, $body = "{}", $headers = 0, $extraOptions = 0)
    {
        if (isset($extraOptions["IgnoreXSRF"])) {
            if ($extraOptions["IgnoreXSRF"] === true) {
                $data = $this->postRequest($url, $body, $headers, $extraOptions);
                return $data;
            }else{
                $xsrf = $this->retrieveXSRF(true, $headers);
                if ($xsrf["success"]) {
                    if ($headers === 0) {
                        $headers = array();
                    }
                    $headers["X-CSRF-Token"] =  $xsrf["token"];
                    $data = $this->postRequest($url, $body, $headers, $extraOptions);
                    return $data;
                }else{
                    // TODO: Add error handling...
                    return array("success"=>false);
                }
            }
        }else{
            $xsrf = $this->retrieveXSRF(true, $headers);
            if ($xsrf["success"]) {
                if ($headers === 0) {
                    $headers = array();
                }
                $headers["X-CSRF-Token"] =  $xsrf["token"];
                $data = $this->postRequest($url, $body, $headers, $extraOptions);
                return $data;
            }else{
                // TODO: Add error handling...
                return array("success"=>false);
            }
        }
    }

    // Post Request w/o Cookie
    // @Return: string
    private function postRequestWithoutCookie($url, $body = "{}", $headers = 0, $extraOptions = 0)
    {
        $xsrf = $this->retrieveXSRF(false, $headers, $body);
        if ($xsrf["success"]) {
            if ($headers === 0) {
                $headers = array();
            }
            $headers["X-CSRF-Token"] =  $xsrf["token"];
            $data = $this->postRequest($url, $body, $headers, $extraOptions);
            return $data;
        }else{
            // TODO: Add error handling...
            return array("success"=>false);
        }
    }

    // Get Request w/ Cookie
    // @Return: string
    private function getRequestWithCookie($url, $headers = 0, $extraOptions = 0)
    {
        $data = $this->getRequest($url, $headers, $extraOptions);
        return $data;
    }

    // ----------------------
    // -- PUBLIC FUNCTIONS --
    // -- Description: Functions meant to be used outside the roblox.php file
    // ----------------------

    // -- -------------------
    // -- Section: Thumbnails
    // -- -------------------

    // -- Get Requests (No Auth)
    // -- -------------------

    public function getUserAvatarThumbnail($userid, $type = "avatar-headshot", $size = 0, $format = "png")
    {
        if ($type === "avatar-headshot" and $size === 0) {
            $size = "150x150";
        }elseif ($type === "avatar" and $size === 0) {
            $size = "100x100";
        }
        $data = json_decode($this->file_get_contents_curl("https://thumbnails.roblox.com/v1/users/" . $type . "?userIds=" . $userid . "&size=" . $size . "&format=" . $format), true);
        return $data;
    }

    public function getGroupIcon($groupid, $size = "150x150", $format = "png") 
    {
        $data = json_decode($this->file_get_contents_curl("https://thumbnails.roblox.com/v1/groups/icons?groupIds=" . $groupid . "&size=" . $size . "&format=" . $format), true);
        return $data;
    }

    public function getAssetThumbnail($assetid, $size = "150x150", $format = "png") {
        $data = json_decode($this->file_get_contents_curl("https://thumbnails.roblox.com/v1/assets?assetIds=" . $assetid . "&size=" . $size . "&format=" . $format), true);
        return $data;
    }

    public function getBadgeThumbnail($badgeid, $size = "150x150", $format = "png")
    {
        $data = json_decode($this->file_get_contents_curl("https://thumbnails.roblox.com/v1/badges/icons?badgeIds=" . $badgeid . "&size=" . $size . "&format=" . $format), true);
        return $data;
    }

    public function getBundleThuimbnail($bundleid, $size = "150x150", $format = "png")
    {
        $data = json_decode($this->file_get_contents_curl("https://thumbnails.roblox.com/v1/bundles/thumbnails?bundleIds=" . $bundleid . "&size=" . $size . "&format=" . $format), true);
        return $data;
    }

    public function getGamepassThumbnail($gamepassid, $size = "150x150", $format = "png")
    {
        $data = json_decode($this->file_get_contents_curl("https://thumbnails.roblox.com/v1/bundles/game-passes?gamePassIds=" . $bundleid . "&size=" . $size . "&format=" . $format), true);
        return $data;
    }
    
    public function getGameThumbnail($universeid, $thumbnailid, $size = "768x432", $format = "png")
    {
        $data = json_decode($this->file_get_contents_curl("https://thumbnails.roblox.com/v1/games/" . $universeid . "/thumbnails?universeId=" . $universeid . "&thumbnailIds=" . $thumbnailid . "&size=" . $size . "&format=" . $format), true);
        return $data;
    }

    public function getGameIcon($universeid, $size = "50x50", $format = "png")
    {
        $data = json_decode($this->file_get_contents_curl("https://thumbnails.roblox.com/v1/games/icons?universeIds=" . $universeid . "&size=" . $size . "&format=" . $format), true);
        return $data;
    }

    public function multigetGameThumbnails($universeid, $countPerUniverse = 1, $size = "768x432", $format = "png")
    {
        $data = json_decode($this->file_get_contents_curl("https://thumbnails.roblox.com/v1/games/multiget/thumbnails?universeIds=" . $universeid . "&size=" . $size . "&format=" . $format), true);
        return $data;
    }

    // -- -------------------
    // -- Section: User Data/Auth
    // -- -------------------
    // -- Notes: Functions in this section won't have descriptions as their name should be enough.

    // -- Get Requests (No Auth)
    // -- -------------------

    public function retrieveUserFromUserid($userid) 
    {
        $data = json_decode($this->file_get_contents_curl("http://api.roblox.com/users/" . $userid), true);
        return $data;
    }

    public function retrieveUserFromUsername($username) 
    {
        $data = json_decode($this->file_get_contents_curl("http://api.roblox.com/users/get-by-username?username=" . $username), true);
        return $data;
    }

    public function getUserGroups($userid)
    {
        $data = json_decode($this->file_get_contents_curl("http://api.roblox.com/users/" . $userid . "/groups"), true);
        return $data;
    }

    public function getOwnedGroups($userid)
    {
        $data = $this->getUserGroups($userid);
        $ndata = array();
        foreach ($data as &$key) {
            if ($key["Rank"] === 255) {
                $ndata[] = $key;
            }
        }
        return $ndata;
    }

    public function isInGroup($userid, $groupid) 
    {
        $groupid = (int)$groupid;
        $isInGroup = false;
        $data = $this->getUserGroups($userid);
        foreach ($data as &$key) {
            if ($key["Id"] === $groupid) {
                $isInGroup = true;
            }
        }
        return $isInGroup;
    }

    public function isUserTerminated($userid)
    {
        $data = $this->file_get_contents_curl("https://www.roblox.com/users/" . $userid . "/profile", array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 302) {
            return true;
        }else{
            return false;
        }
    }

    public function userOwnsAsset($userid, $assetid) 
    {
        // Todo: Hopefully improve speed by using a different endpoint...
        $data = $this->file_get_contents_curl("https://api.roblox.com/ownership/hasasset?assetId=" . $assetid . "&userId=" . $userid);
        if ($data === "true") {
            return true;
        }else{
            return false;
        }
    }

    public function userBcBcType($userid) 
    {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/profile?userid=" . $userid), true);
        if ($data["BC"] === true) {
            return "BC";
        }elseif ($data["TBC"] === true) {
            return "TBC";
        }elseif ($data["OBC"] === true) {
            return "OBC";
        }else{
            return false;
        }
    }

    public function userHasBc($userid) 
    {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/profile?userid=" . $userid), true);
        if ($data["BC"] === true or $data["TBC"] === true or $data["OBC"] === true) {
            return true;
        }else{
            return false;
        }
    }


    public function getUserFriends($userid) 
    {
        $data = json_decode($this->file_get_contents_curl("http://api.roblox.com/users/" . $userid . "/friends"), true);
        return $data;
    }

    public function getUserProfileBadges($userid) {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/users/profile/playerassets-json?assetTypeId=21&userId=" . $userid), true);
        return $data;
    }

    public function getUserProfileFavoriteGames($userid)
    {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/users/favorites/list-json?assetTypeId=9&itemsPerPage=6&pageNumber=1&thumbHeight=110&thumbWidth=110&userId=" . $userid), true);
        return $data;
    }

    public function getUserProfileModels($userid) {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/users/profile/playerassets-json?assetTypeId=10&userId=" . $userid), true);
        return $data;
    }

    public function getUserProfileClothing($userid) {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/users/profile/playerassets-json?assetTypeId=11&userId=" . $userid), true);
        return $data;
    }

    public function getUserProfileGroups($userid) {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/users/profile/playergroups-json?userId=" . $userid), true);
        return $data;
    }

    public function getUserProfileCollections($userid) {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/users/profile/robloxcollections-json?userId=" . $userid), true);
        return $data;
    }

    public function getProfileHeader($userid)
    {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/users/profile/profileheader-json?userid=" . $userid), true);
        return $data;
    }

    public function getLastOnline($userid) {
        $data = json_decode($this->file_get_contents_curl("https://api.roblox.com/users/$userid/onlinestatus"), true);
        return $data;
    }

    public function getSupportedLocales($userid) 
    {
        $data = json_decode($this->file_get_contents_curl("https://locale.roblox.com/v1/locales/supported-locales"), true);
        return $data;
    }

    public function getUserCollectibles($userid, $assettype = "Hat", $limit = 10, $cursor = "", $sort = "Asc") 
    {
        $data = $this->file_get_contents_curl("https://inventory.roblox.com/v1/users/$userid/assets/collectibles?assetType=$assettype&sortOrder=$sort&limit=$limit&cursor=$cursor", array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"satuscode"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"satuscode"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public $limitedTypeArray = array("Hat", "HairAccessory", "Face", "Gear", "BackAccessory", "FaceAccessory");

    public function getUaid($userid, $uaid)
    {
        $uaid = (int)$uaid;
        $limitedTypeArray = $this->limitedTypeArray;
        foreach ($limitedTypeArray as &$key) {
            $data = $this->getUserCollectibles($userid, $key, 100);
            if (isset($data["data"]["data"][0])) {
                foreach ($data["data"]["data"] as &$key) {
                    if ($key["userAssetId"] === $uaid) {
                        return $key;
                    }
                }
            }
            while ($data["data"]["nextPageCursor"] !== null) {
                $data = $this->getUserCollectibles($userid, $key, 100);
                if (isset($data["data"]["data"][0])) {
                    if ($key["userAssetId"] === $uaid) {
                        return $key;
                    }
                }
            }
        }
        return false;
    }

    public function checkIfOwnsUaid($userid, $uaid)
    {
        $limitedTypeArray = $this->limitedTypeArray;
        foreach ($limitedTypeArray as &$key) {
            $data = $this->getUserCollectibles($userid, $key, 100);
            if (isset($data["data"]["data"][0])) {
                foreach ($data["data"]["data"] as &$key) {
                    if ($key["userAssetId"] === $uaid) {
                        return true;
                    }
                }
            }
            while ($data["data"]["nextPageCursor"] !== null) {
                $data = $this->getUserCollectibles($userid, $key, 100);
                if (isset($data["data"]["data"][0])) {
                    if ($key["userAssetId"] === $uaid) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getAllLimiteds($userid)
    {
        $check = $this->getUserCollectibles($userid, "Hat");
        if ($check["satuscode"] !== 200) {
            return array("success"=>false,"code"=>$check["satuscode"]);
        }else{
            $limiteds = array();
            $limitedTypeArray = $this->limitedTypeArray;
            foreach ($limitedTypeArray as &$limtype) {
                $data = $this->getUserCollectibles($userid, $limtype, 100);
                if (isset($data["data"]["data"][0])) {
                    foreach ($data["data"]["data"] as &$key) {
                        $limiteds[] = $key;
                    }
                }
                while ($data["data"]["nextPageCursor"] !== null) {
                    $data = $this->getUserCollectibles($userid, $limtype, 100, $data["data"]["nextPageCursor"]);
                    if (isset($data["data"]["data"][0])) {
                        foreach ($data["data"]["data"] as &$key) {
                            $limiteds[] = $key;
                        }
                    }
                }
            }
            return array("success"=>true,"data"=>$limiteds);
        }
    }

    // -- Get Requests (With Auth)
    // -- -------------------
    
    public function getCurrentUser($headers)
    {
        $data = $this->getRequestWithCookie("https://assetgame.roblox.com/Game/GetCurrentUser.ashx", $headers);
        return $data;
    }

    public function retrieveUserBalance($headers) 
    {
        $balance = json_decode($this->getRequestWithCookie("https://api.roblox.com/currency/balance", $headers), true);
        return $balance;
    }

    // Retrieve BC Expiry Date & Credit Balance (Returns False if not available)
    public function retrieveUserCreditsAndBc($headers) 
    {
        $balance = json_decode($this->getRequestWithCookie("https://www.roblox.com/premium/membership?ctx=leftnav", $headers, ["ReturnStatusCode"=>true]), true);
        if ($balance["statuscode"] == 200) {
            preg_match('/Credit Balance: <span class=Money>.+<\/span><\/h3>/', $data["data"], $creditBal);
            preg_match('/<span class=product-expiration> Expires: <span class=product-expiration-date>.+<\/span><\/span><\/div><\/div><a data-pid=/', $data["data"], $expires);
            $array = array("creditBalance"=>substr($creditBal[0], 34, -12),"bcExpires"=>substr($expires[0], 77, -38));
            if ($array["creditBalance"] === null or $array["creditBalance"] === false) {
                $array["creditBalance"] = false;
            }
            if ($array["bcExpires"] === null or $array["bcExpires"] === false) {
                $array["bcExpires"] = false;
            }
            return $array;
        }else{
            return array("creditBalance"=>false,"bcExpires"=>false);
        }
    }

    public function retrieveEmail($headers) 
    {
        $data = json_decode($this->getRequestWithCookie("https://accountsettings.roblox.com/v1/email", $headers), true);
        return $data;
    }

    public function retrieveTwoFactorSetting($headers) 
    {
        $data = json_decode($this->getRequestWithCookie("https://accountsettings.roblox.com/v1/twostepverification", $headers), true);
        return $data;
    }

    public function retrieveTradePrivacySetting($headers) 
    {
        $balance = json_decode($this->getRequestWithCookie("https://accountsettings.roblox.com/v1/trade-privacy", $headers), true);
        return $balance;
    }

    public function retrieveTradeValueSetting($headers) 
    {
        $balance = json_decode($this->getRequestWithCookie("https://accountsettings.roblox.com/v1/trade-value", $headers), true);
        return $balance;
    }

    public function checkIfNewUsernameAvailable($headers, $newusername)
    // This is specifically used by roblox for choosing a new username intsead of the newer endpoints. There might be reason for it, so I chose this depreciated one instead of a newer one
    {
        $data = json_decode($this->getRequestWithCookie("https://www.roblox.com/usercheck/checkifinvalidusernameforsignup?username=" . $newusername, $headers), true);
        return $data;
    }


    // -- Post Requests
    // -- -------------------

    public function authLogin($cvalue, $password, $ctype = "Username")
    {
        //$query = json_encode(array("cvalue"=>$cvalue,"ctype"=>$ctype,"Password"=>$password));
        //$query = "{\"cvalue\":\"$cvalue\",\"ctype\":\"Username\",\"Password\":\"$password\"}";
        $query = '{"cvalue":"' . $cvalue . '","ctype":"Username","password":"' . $password . '"}';
        $url = "https://auth.roblox.com/v2/login";
        $headers = array("Content-Type"=>"application/json", "Content-Length"=>strlen($query), "Accept"=>"application/json", "User-Agent"=>$_SERVER["HTTP_USER_AGENT"]);
        $options = array("Header"=>true);
        $data = $this->postRequestWithoutCookie($url, $query, $headers, $options);
        preg_match('/Set-Cookie: \.ROBLOSECURITY=(.+?;)/', $data, $matches);
        if ($matches[1] !== null) {
            return ".ROBLOSECURITY=" . $matches[1];
        }else{
            //return $data;
            return false; //TODO: add proper error handling...
        }

    }

    public function authLogout($headers)
    {
        $data = $this->postRequestWithCookie(
            "https://auth.roblox.com/v2/logout", 
            $post, 
            $headers, 
            array("ReturnStatusCode"=>true)
        );
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"statuscode"=>$data["statuscode"],"data"=>json_decode($data["data"]));
        }else{
            return array("success"=>false,"statuscode"=>$data["statuscode"],"data"=>json_decode($data["data"]));
        }
    }

    public function impersonateUser($headers, $userid)
    {
        $data = $this->postRequestWithCookie(
            "https://auth.roblox.com/v2/users/".$userid."/impersonate", 
            "{}", 
            $headers, 
            array("ReturnStatusCode"=>true)
        );
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"statuscode"=>$data["statuscode"],"data"=>json_decode($data["data"]));
        }else{
            return array("success"=>false,"statuscode"=>$data["statuscode"],"data"=>json_decode($data["data"]));
        }
    }

    public function updateTradePrivacySetting($headers, $tradeprivacy = "All") 
    {
        $post = json_encode(array("tradePrivacy"=>$tradeprivacy));
        $data = $this->postRequestWithCookie("https://accountsettings.roblox.com/v1/trade-privacy", $post, $headers);
        return $data;
    }

    public function updateTradeValueSetting($headers, $tradevalue = "None") 
    {
        $post = json_encode(array("tradeValue"=>$tradevalue));
        $data = $this->postRequestWithCookie("https://accountsettings.roblox.com/v1/trade-value", $post, $headers);
        return $data;
    }

    public function changeUsername($headers, $newname, $password) 
    {
        $tokens = $this->retrieveVerificationTokens($headers, "https://www.roblox.com/my/account#!/info");
        $headers["Cookie"] = $headers["Cookie"] . "; " . $tokens["header"] .";"; 
        $post = json_encode(array("__RequestVerificationToken"=>urlencode($tokens["form"]),"username"=>$newname,"password"=>urlencode($password)));
        $data = json_decode($this->postRequestWithCookie("https://www.roblox.com/account/username/update", $post, $headers), true);
        return $data;
    }

    // -- -------------------
    // -- Section: Avatar
    // -- -------------------

    // -- Get Requests (No Auth)
    // -- -------------------

    public function getUserAvatar($userid) 
    {
        $data = json_decode($this->file_get_contents_curl("https://avatar.roblox.com/v1/users/" . $userid . "/avatar"), true);
        return $data;
    }

    public function getUserAvatarPlace($userid, $placeid = 2845536051)
    {
        $data = json_decode($this->file_get_contents_curl("https://avatar.roblox.com/v1/avatar-fetch/?placeId=$placeid&userId=" . $userid), true);
        return $data;
    }

    public function getUserOutfits($userid, $page = 1, $itemsperpage = 10, $iseditable = "true") 
    {
        $data = json_decode($this->file_get_contents_curl("https://avatar.roblox.com/v1/users/" . $userid . "/outfits?page=$page&itemsPerPage=$itemsperpage&isEditable=$iseditable"), true);
        return $data;
    }

    public function getOutfitDetails($outfitid) 
    {
        $data = json_decode($this->file_get_contents_curl("https://avatar.roblox.com/v1/outfits/$outfitid/details"), true);
        return $data;
    }

    // -- Post Requests
    // -- -------------------

    public function wearAsset($headers, $assetid) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/avatar/assets/$assetid/wear", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function removeAsset($headers, $assetid) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/avatar/assets/$assetid/remove", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function redrawAvatar($headers) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/avatar/redraw-thumbnail", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function setAvatarColors($headers, $head = 0, $torso = 0, $rightarm = 0, $leftarm = 0, $leftleg = 0, $rightleg = 0) 
    {
        $post = json_encode(array("headColorId"=>$head,"torsoColorId"=>$torso,"rightArmColorId"=>$rightarm,"leftArmColorId"=>$leftarm,"rightLegColorId"=>$rightleg,"leftLegColorId"=>$leftleg));
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/avatar/set-body-colors", $post, $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function setAvatarType($headers, $type = "R6") 
    {
        $post = json_encode(array("playerAvatarType"=>$type));
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/avatar/set-player-avatar-type", $post, $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function setAvatarScales($headers, $height = "0.90", $width = "0.70", $head = "0.95", $depth = "0", $proportion = "0", $bodyType = "0") 
    {
        $post = json_encode(array("height"=>$height,"width"=>$width,"head"=>$head,"depth"=>$depth,"proportion"=>$proportion,"bodyType"=>$bodyType));
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/avatar/set-scales", $post, $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function setAvatarWearingAssets($headers, $assets) 
    // Note: Any assets being worn before this method is called are automatically removed.
    {
        /*
        $assets format:
            array(05838681,6936762);
        */
        $post = json_encode(array("assetIds"=>$assets));
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/avatar/set-wearing-assets", $post, $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function deleteOutfit($headers, $outfitid) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/outfits/$outfitid/delete", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function wearOutfit($headers, $outfitid) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/outfits/$outfitid/wear", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function createOutfit($headers, $outfitjson) 
    {
        /*
        Outfit Json Example
        {
            "name": "string",
            "bodyColors": {
                "headColorId": 0,
                "torsoColorId": 0,
                "rightArmColorId": 0,
                "leftArmColorId": 0,
                "rightLegColorId": 0,
                "leftLegColorId": 0
            },
            "assetIds": [
                0
            ],
            "scale": {
                "height": 0,
                "width": 0,
                "head": 0,
                "depth": 0,
                "proportion": 0,
                "bodyType": 0
            },
            "playerAvatarType": "string"
        }
        */
        $post = json_encode($outfitjson);
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/outfits/create", $post, $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function updateOutfit($headers, $outfitid, $outfitjson) 
    {
        /*
        Outfit Json Example
        {
            "name": "string",
            "bodyColors": {
                "headColorId": 0,
                "torsoColorId": 0,
                "rightArmColorId": 0,
                "leftArmColorId": 0,
                "rightLegColorId": 0,
                "leftLegColorId": 0
            },
            "assetIds": [
                0
            ],
            "scale": {
                "height": 0,
                "width": 0,
                "head": 0,
                "depth": 0,
                "proportion": 0,
                "bodyType": 0
            },
            "playerAvatarType": "string"
        }
        */
        $post = json_encode($outfitjson);
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://avatar.roblox.com/v1/outfits/$outfitid/update", $post, $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    // -- -------------------
    // -- Section: Billing
    // -- -------------------

    // -- Get Requests (Auth)
    // -- -------------------

    public function getUserPayments($headers, $limit = 10, $sortorder = "Asc", $cursor = "") 
    {
        $data = json_decode($this->getRequestWithCookie("https://billing.roblox.com/v1/user/payments?sortOrder=$sortorder&limit=$limit&cursor=$cursor", $headers), true);
        return $data;
    }

    // -- Post Requests
    // -- -------------------

    public function redeemGameCard($headers, $pin) 
    {
        $post = json_encode(array("pinCode"=>$pin));
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://billing.roblox.com/v1/gamecard/redeem", $post, $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function reverseGameCard($headers, $pin, $userid = 1) 
    {
        $post = json_encode(array("PinCode"=>$pin,"UserId"=>$userid));
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://billing.roblox.com/v1/gamecard/reverse", $post, $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    // -- -------------------
    // -- Section: Friends/Following
    // -- -------------------

    // -- Get Requests (No Auth)
    // -- -------------------

    public function getFriends($userid)
    {
        $data = json_decode($this->file_get_contents_curl("https://friends.roblox.com/v1/users/$userid/friends"), true);
        return $data;
    }

    // -- Get Requests (Auth)
    // -- -------------------

    public function getFriendsOnline($headers) 
    {
        $data = json_decode($this->getRequestWithCookie("https://friends.roblox.com/v1/my/friendsonline", $headers), true);
        return $data;
    }

    // -- Post Requests
    // -- -------------------

    public function requestFriendship($headers, $userid) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://friends.roblox.com/v1/users/$userid/request-friendship", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function acceptFriendRequest($headers, $userid) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://friends.roblox.com/v1/users/$userid/accept-friend-request", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function declineFriendRequest($headers, $userid) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://friends.roblox.com/v1/users/$userid/decline-friend-request", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function followUser($headers, $userid) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://friends.roblox.com/v1/users/$userid/follow", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function unfollowUser($headers, $userid) 
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://friends.roblox.com/v1/users/$userid/unfollow", "{}", $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function commentOnAsset($headers, $assetid, $text) 
    {
        $postdata = http_build_query(array("assetId"=>$assetid,"text"=>$text));
        $headers["Content-Type"] = "application/x-www-form-urlencoded; charset=UTF-8";
        $headers["Content-Length"] = strlen($postdata);
        $data = json_decode($this->postRequestWithCookie("https://www.roblox.com/comments/post", $postdata, $headers), true);
        return $data;
    }

    // -- -------------------
    // -- Section: Groups
    // -- -------------------

    // -- Get Requests (No Auth)
    // -- -------------------

    public function getGroupInfo($groupid) 
    {
        $data = json_decode($this->file_get_contents_curl("https://groups.roblox.com/v1/groups/" . $groupid), true);
        return $data;
    }

    public function getGroupWall($headers, $groupid, $cursor = "", $limit = 10, $order = "Asc") 
    {
        $data = json_decode($this->file_get_contents_curl("https://groups.roblox.com/v1/groups/$groupid/wall/posts?sortOrder=$order&limit=$limit&cursor=$cursor"), true);
        return $data;
    }

    public function getGroupPublicFunds($groupid) 
    {
        $data = json_decode($this->file_get_contents_curl("https://economy.roblox.com/v1/groups/" . $groupid . "/currency"), true);
        return $data;
    }

    public function getGroupRoles($groupid)
    {
        $data = json_decode($this->file_get_contents_curl("https://groups.roblox.com/v1/groups/" . $groupid . "/roles"), true);
        return $data;
    }

    public function getGroupRelations($headers, $groupid, $type = "Allies", $startRowIndex = 1, $maxRows = 100)
    {
        $data = json_decode($this->file_get_contents_curl("https://groups.roblox.com/v1/groups/$groupid/relationships/$type?model.startRowIndex=$startRowIndex&model.maxRows=$maxRows"), true);
        return $data;
    }

    // -- Get Requests (With Auth)
    // -- -------------------

    public function getProtectedGroupWall($headers, $groupid, $cursor = "", $limit = 10, $order = "Asc") 
    {
        $data = json_decode($this->getRequestWithCookie(
            "https://groups.roblox.com/v1/groups/$groupid/wall/posts?sortOrder=$order&limit=$limit&cursor=$cursor", 
            $headers
        ), true);
        return $data;
    }

    public function getJoinRequests($headers, $groupid) 
    {
        $data = $this->getRequestWithCookie("https://www.roblox.com/groups/3730100/joinrequests-html?groupId=$groupid", $headers);
        return $data;
    }

    public function getAllJoinRequstIds($headers, $groupid)
    {
        $data = $this->getJoinRequests($headers, $groupid);
        preg_match('/data-rbx-group-requests-id=".+?"/', $data, $firstmatches);
        if (isset($firstmatches[0])) {
            $ndata = substr($firstmatches[0], 29, -2);
            $myArray = explode(',', $ndata);
            if (isset($myArray[0])) {
                return array("success"=>true,"data"=>$myArray);
            }else{
                return array("success"=>false,"data"=>$data);
            }
        }else{
            return array("success"=>false,"data"=>$data);
        }

    }

    public function getGroupMetaData($headers) 
    {
        $data = json_decode($this->getRequestWithCookie("https://groups.roblox.com/v1/groups/metadata", $headers), true);
        return $data;
    }

    public function getGroupFunds($headers, $groupid) 
    {
        $data = json_decode($this->getRequestWithCookie("https://economy.roblox.com/v1/groups/" . $groupid . "/currency", $headers), true);
        return $data;
    }

    public function getGroupMembershipData($headers, $groupid)
    {
        $data = json_decode($this->getRequestWithCookie("https://groups.roblox.com/v1/groups/" . $groupid . "/membership", $headers), true);
        return $data;
    }

    public function getGroupRevenueId($headers, $groupid)
    // Used for the two functions below (getGroupCurrencySummary() and lineGroupRevenue())
    {
        $data = $this->getRequestWithCookie("https://www.roblox.com/my/groupadmin.aspx?gid=" . $groupid, $headers);
        preg_match('/<div class="summary" data-get-url="\/currency\/summary\/.+?">/', $data, $matches);
        return substr($matches[0], 53, -2);
    }

    public function getGroupCurrencySummary($headers, $grouprevid, $page = "year")
    {
        $data = preg_replace( "/\r|\n/", "", $this->getRequestWithCookie("https://www.roblox.com/currency/summary/$grouprevid/$page/", $headers));
        $return = array("success"=>true,"data"=>array());
        preg_match('/Sale of Goods<\/td>                    <td class="credit">.+?<\/td>/', $data, $saleofgoods);
        preg_match('/<td class="categories">Pending Sales<\/td>                    <td class="credit">.+?<\/td>/', $data, $pending);
        $return["data"]["SaleOfGoods"] = substr($saleofgoods[0], 57, -5);
        $return["data"]["PendingSales"] = substr($pending[0], 80, -5);
        if ($return["data"]["PendingSales"] !== null and $return["data"]["SaleOfGoods"] !== null) {
            return $return;
        }else{
            return array("success"=>false);
        }
    }

    public function lineGroupRevenue($headers, $grouprevid, $page = "0")
    {
        $data = preg_replace( "/\r|\n/", "", $this->getRequestWithCookie("https://www.roblox.com/currency/line-items/$grouprevid/" . $page, $headers));
        if ($data === "    <tr class=\"more\">        <td colspan=\"4\">            No records found.        </td>    </tr>") {
            return array("success"=>false);
        }else{
            $return = array("success"=>true,"data"=>array());
            preg_match_all('/data-user-id=".+?"/', $data, $useridmatches);
            preg_match_all('/<\/div>                <span>.+?<\/span>/', $data, $usernamematches);
            preg_match_all('/class="robux">.+?<\/span>/', $data, $robuxamount);
            preg_match_all('/Sold <a href=".+?"/', $data, $soldlinks);
            preg_match_all('/<td class="date">.+?<\/td>/', $data, $datematches);
            foreach ($useridmatches[0] as $key=>$value) {
                $return["data"][] = array(
                    "UserId"=>substr($useridmatches[0][$key], 14, -1),
                    "Username"=>substr($usernamematches[0][$key], 28, -7),
                    "Robux"=>substr($robuxamount[0][$key], 14, -7),
                    "Link"=>substr($soldlinks[0][$key], 14, -1),
                    "Date"=>substr($datematches[0][$key], 17, -5),
                );
            }
            return $return;
        }
    }

    public function getGroupPendingRelations($headers, $groupid, $type = "Allies", $startRowIndex = 1, $maxRows = 100)
    {
        $data = json_decode($this->getRequestWithCookie("https://groups.roblox.com/v1/groups/$groupid/relationships/$type/requests?model.startRowIndex=$startRowIndex&model.maxRows=$maxRows", $headers), true);
        return $data;
    }

    // -- Post Requests
    // -- -------------------

    public function acceptAllJoinRequests($headers, $groupid, $requestIdArray)
    {
        // Get the $requestIdArray from getAllJoinRequstIds()["data"]
        $post = "groupId=$groupid";
        foreach ($requestIdArray as &$key) {
            $post = $post . "&groupJoinRequestIDs=" . $key;
        }
        $data = $this->postRequestWithCookie(
            "https://www.roblox.com/group/handle-all-join-requests", 
            $post, 
            $headers,
            array("ReturnStatusCode"=>true)
        );
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function postToGroupWall($headers, $groupid, $message)
    {
        $post = json_encode(array("body"=>$message));
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie(
            "https://groups.roblox.com/v1/groups/$groupid/wall/posts", 
            $post, 
            $headers,
            array("ReturnStatusCode"=>true)
        );
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function sendRelationRequest($headers, $groupid, $groupidToAdd, $type = "Allies")
    {
        $data = $this->postRequestWithCookie(
            "https://groups.roblox.com/v1/groups/$groupid/relationships/$type/$groupidToAdd", 
            "{}", 
            $headers,
            array("ReturnStatusCode"=>true)
        );
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function deleteRelation($headers, $groupid, $groupidToDelete, $type = "Allies")
    {
        $data = $this->postRequestWithCookie(
            "https://groups.roblox.com/v1/groups/$groupid/relationships/$type/$groupidToDelete", 
            "{}", 
            $headers,
            array("ReturnStatusCode"=>true,"CustomRequest"=>"DELETE")
        );
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function createRelationRequest($headers, $groupid, $groupidToAdd, $type = "Allies")
    {
        $data = $this->postRequestWithCookie(
            "https://groups.roblox.com/v1/groups/$groupid/relationships/$type/requests/$groupidToAdd", 
            "{}", 
            $headers,
            array("ReturnStatusCode"=>true)
        );
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function deleteRelationRequest($headers, $groupid, $groupidToDelete, $type = "Allies")
    {
        $data = $this->postRequestWithCookie(
            "https://groups.roblox.com/v1/groups/$groupid/relationships/$type/requests/$groupidToDelete", 
            "{}", 
            $headers,
            array("ReturnStatusCode"=>true,"CustomRequest"=>"DELETE")
        );
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function spendGroupFunds($headers, $groupid, $userid, $amount)
    {
        $headers["X-Request-With"] = "XMLHttpRequest";
        $headers["Connection"] = "keep-alive";
        $headers["Referer"] = "https://www.roblox.com/my/groupadmin.aspx?gid=" . $groupid . "#nav-payouts";

        $post = http_build_query(array("percentages" => json_encode(array($userid => $amount))));
        $data = $this->postRequestWithCookie("https://www.roblox.com/groups/" . $groupid . "/one-time-payout/false", $post, $headers, array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>$data["data"]);
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>$data["data"]);
        }
    }

    public function changeUserRank($headers, $groupid, $userid, $roleid)
    {
        $post = json_encode(array("roleId"=>$roleid));
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://groups.roblox.com/v1/groups/$groupid/users/$userid", $post, $headers,array("ReturnStatusCode"=>true,"CustomRequest"=>"PATCH"));
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function changeGroupStatus($headers, $groupid, $status)
    {
        $post = json_encode(array("message"=>$status));
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://groups.roblox.com/v1/groups/$groupid/status", $post, $headers,array("ReturnStatusCode"=>true,"CustomRequest"=>"PATCH"));
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function changeGroupSettings($headers, $groupid, $approvalrequired = "false", $buildersclub = "false", $enemies = "false", $groupfunds = "false", $groupgames = "false")
    {
        $post = http_build_query(array("groupId"=>$groupid,"approvalRequired"=>$approvalrequired,"buildersClubRequired"=>$buildersclub,"enemiesAllowed"=>$enemies,"displayGroupFunds"=>$groupfunds,"GoupGamesVisible"=>$groupgames));
        $headers["Content-Type"] = "application/x-www-form-urlencoded; charset=UTF-8";
        $headers["Content-Length"] = strlen($post);
        $headers["Referer"] = "https://www.roblox.com/my/groupadmin.aspx?gid=" . $groupid;
        $data = $this->postRequestWithCookie("https://www.roblox.com/group/update-group-settings", $post, $headers,array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function claimGroupOwnership($headers, $groupid)
    {
        $headers["Content-Type"] = "application/json";
        $data = $this->postRequestWithCookie("https://groups.roblox.com/v1/groups/$groupid/claim-ownership", "{}", $headers,array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return  array("success"=>true,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"code"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    /* Why are webforumboundarys so hard? :(
    public function createGroup($headers, $groupname, $groupimage, $groupimagename, $groupdescription = "No Description", $public = "true", $buildersclubonly = "false")
    {
        $post = "------WebKitFormBoundary18Egshz5gq8LQqGw
Content-Disposition: form-data; name=\"request.name\"
        
$groupname
------WebKitFormBoundary18Egshz5gq8LQqGw
Content-Disposition: form-data; name=\"request.description\"
        
$groupdescription
------WebKitFormBoundary18Egshz5gq8LQqGw
Content-Disposition: form-data; name=\"request.publicGroup\"
        
$public
------WebKitFormBoundary18Egshz5gq8LQqGw
Content-Disposition: form-data; name=\"request.buildersClubMembersOnly\"
        
$buildersclubonly
------WebKitFormBoundary18Egshz5gq8LQqGw
Content-Disposition: form-data; name=\"request.files\"; filename=\"" . $groupimagename . "\"
Content-Type: image/jpeg

" . file_get_contents($groupimage) . "\n" .
"------WebKitFormBoundary18Egshz5gq8LQqGw--";


        echo $post;
        echo "\n\n";
        echo "<br><br>";
        $headers["Content-Type"] = "multipart/form-data; boundary=----WebKitFormBoundary18Egshz5gq8LQqGw";
        $data = $this->postRequestWithCookie(
            "https://groups.roblox.com/v1/groups/create", 
            $post, 
            $headers,
            array("ReturnStatusCode"=>true)
        );
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"code"=>$data["statuscode"], "data"=>$data["data"]);
        }else{
            return array("success"=>false,"code"=>$data["statuscode"], "data"=>$data["data"]);
        }
    }

    */

    public function changeGroupOwner($headers, $username, $groupid)
    {
        $post = http_build_query(array("groupId"=>$groupid,"newOwnerName"=>$username));
        $data = $this->postRequestWithCookie(
            "https://www.roblox.com/group/change-group-owner", 
            $post, 
            $headers,
            array("ReturnStatusCode"=>true)
        );
        if ($data["statuscode"] == 302) {
            return array("success"=>true,"code"=>$data["statuscode"], "data"=>$data["data"]);
        }else{
            return array("success"=>false,"code"=>$data["statuscode"], "data"=>$data["data"]);
        }
    }

    // -- -------------------
    // -- Section: games.roblox.com
    // -- -------------------

    public function getGameInfo($universeid)
    {
        $data = json_decode($this->file_get_contents_curl('https://games.roblox.com/v1/games?universeIds=' . $universeid), true);
        return $data;
    }

    public function mutliGetPlaceData($placeid)
    {
        $data = json_decode($this->file_get_contents_curl('https://games.roblox.com/v1/games/multiget-place-details?placeIds=' . $placeid), true);
        return $data;
    }

    public function getGroupGames($groupid, $filter = "Public", $sort = "Asc", $limit = 25) 
    {
        $data = json_decode($this->file_get_contents_curl('https://games.roblox.com/v2/groups/'.$groupid.'/games?accessFilter=' . $filter . '&sortOrder=' . $sort . '&limit=' . $limit), true);
        return $data;
    }

    public function getUserGames($userid, $filter = "Public", $sort = "Asc", $limit = 25) 
    {
        $data = json_decode($this->file_get_contents_curl('https://games.roblox.com/v2/users/'.$userid.'/games?accessFilter=' . $filter . '&sortOrder=' . $sort . '&limit=' . $limit), true);
        return $data;
    }

    // -- -------------------
    // -- Section: Captcha Functions
    // -- -------------------

    // -- Get Requests (No Auth)
    // -- -------------------

    public function getFunCaptchaKeys() 
    {
        $data = json_decode($this->file_get_contents_curl("https://captcha.roblox.com/v1/captcha/metadata"), true);
        return $data;
    }

    public function getLoginFuncaptchaKey()
    {
        $captcha = false;
        $data = $this->getFunCaptchaKeys();
        foreach ($data["funCaptchaPublicKeys"] as &$key) {
            if ($key["type"] === "WebLogin") {
                $captcha = $key["value"];
            }
        }
        return $captcha;
    }

    public function getUserActionFuncaptchaKey()
    {
        $captcha = false;
        $data = $this->getFunCaptchaKeys();
        foreach ($data["funCaptchaPublicKeys"] as &$key) {
            if ($key["type"] === "UserAction") {
                $captcha = $key["value"];
            }
        }
        return $captcha;
    }

    public function getGameCardFuncaptchaKey()
    {
        $captcha = false;
        $data = $this->getFunCaptchaKeys();
        foreach ($data["funCaptchaPublicKeys"] as &$key) {
            if ($key["type"] === "WebGamecardRedemption") {
                $captcha = $key["value"];
            }
        }
        return $captcha;
    }

    // -- Post Requests
    // -- -------------------

    public function postLoginFuncaptcha($captchaResponse, $cvalue = "ROBLOX")
    // Description: Used for /v2/ login
    {
        $query = http_build_query(array("credentialsValue"=>$cvalue,"fcToken"=>$captchaResponse));
        $data = json_decode($this->postRequestWithoutCookie(
            "https://captcha.roblox.com/v1/funcaptcha/login/web", 
            $query, 
            array("Content-Type"=> "application/x-www-form-urlencoded; charset=UTF-8")
        ), true);
        return $data; // Empty array aka null = success (weird, I know...)
    }

    public function postUserActionCaptcha($headers, $captchaResponse)
    // Description: Sending friend requests, joining groups, following users, sending messages, etc
    {
        $query = http_build_query(array("fcToken"=>$captchaResponse));
        $data = json_decode($this->postRequestWithCookie(
            "https://captcha.roblox.com/v1/funcaptcha/user", 
            $query, 
            $headers
        ), true);
        return $data; // Empty array aka null = success (weird, I know...)
    }

    public function postGamecardRedemtion($headers, $captchaResponse)
    {
        $query = http_build_query(array("fcToken"=>$captchaResponse));
        $data = json_decode($this->postRequestWithCookie("https://captcha.roblox.com/v1/funcaptcha/gamecardredemption/web", $query, $headers), true);
        return $data; // Empty array aka null = success (weird, I know...)
    }

    // -- -------------------
    // -- Section: Studio/Dev-Related Functions
    // -- -------------------

    // -- Post Requests
    // -- -------------------

    public function uploadAsset($headers, $file, $assetid)
    {
        $headers["User-Agent"] = "RobloxStudio/WinInet";
        $headers["Roblox-Place-Id"] = $assetid;
        $headers["Connection"] = "keep-alive";
        $headers["PlayerCount"] = 0;
        $headers["Content-Length"] = strlen($file);
        $headers["Content-Type"] = "*/*";
        $headers["Requester"] = "Client";
        $headers["Cache-Control"] = "no-cache";
        $data = $this->postRequestWithCookie(
            "https://data.roblox.com/Data/Upload.ashx?assetid=" . $assetid, 
            $file, 
            $headers
        );
        if ($data == $assetid) {
            return true;
        }else{
            return false;
        }
    }

    public function createUniverse($headers, $placename)
    {
        $tokens = $this->retrieveVerificationTokens($headers);
        $headers["Cookie"] = $headers["Cookie"] . "; " . $tokens["header"] .";"; 
        // There's too many options lmao. If you wanna customize this, youll have to do it yourself
        $post = "__RequestVerificationToken=" . urlencode($tokens["form"]) . "&Name=$placename&Description=&Genre=All&TemplateID=95206881&PlayableDevices%5B0%5D.Selected=true&PlayableDevices%5B0%5D.Selected=false&PlayableDevices%5B0%5D.DeviceType=Computer&PlayableDevices%5B1%5D.Selected=true&PlayableDevices%5B1%5D.Selected=false&PlayableDevices%5B1%5D.DeviceType=Phone&PlayableDevices%5B2%5D.Selected=true&PlayableDevices%5B2%5D.Selected=false&PlayableDevices%5B2%5D.DeviceType=Tablet&PlayableDevices%5B3%5D.Selected=false&PlayableDevices%5B3%5D.DeviceType=Console&NumberOfPlayersMax=10&SocialSlotType=Automatic&NumberOfCustomSocialSlots=4&ArePrivateServersAllowed=true&ArePrivateServersAllowed=false&PrivateServersPrice=100&IsAllGenresAllowed=False&AllowedGearTypes%5B0%5D.IsSelected=false&AllowedGearTypes%5B0%5D.GearTypeDisplayName=Melee&AllowedGearTypes%5B0%5D.EncodedBitMask=1&AllowedGearTypes%5B1%5D.IsSelected=false&AllowedGearTypes%5B1%5D.GearTypeDisplayName=Power+ups&AllowedGearTypes%5B1%5D.EncodedBitMask=8&AllowedGearTypes%5B2%5D.IsSelected=false&AllowedGearTypes%5B2%5D.GearTypeDisplayName=Ranged&AllowedGearTypes%5B2%5D.EncodedBitMask=2&AllowedGearTypes%5B3%5D.IsSelected=false&AllowedGearTypes%5B3%5D.GearTypeDisplayName=Navigation&AllowedGearTypes%5B3%5D.EncodedBitMask=16&AllowedGearTypes%5B4%5D.IsSelected=false&AllowedGearTypes%5B4%5D.GearTypeDisplayName=Explosives&AllowedGearTypes%5B4%5D.EncodedBitMask=4&AllowedGearTypes%5B5%5D.IsSelected=false&AllowedGearTypes%5B5%5D.GearTypeDisplayName=Musical&AllowedGearTypes%5B5%5D.EncodedBitMask=32&AllowedGearTypes%5B6%5D.IsSelected=false&AllowedGearTypes%5B6%5D.GearTypeDisplayName=Social&AllowedGearTypes%5B6%5D.EncodedBitMask=64&AllowedGearTypes%5B7%5D.IsSelected=false&AllowedGearTypes%5B7%5D.GearTypeDisplayName=Transport&AllowedGearTypes%5B7%5D.EncodedBitMask=256&AllowedGearTypes%5B8%5D.IsSelected=false&AllowedGearTypes%5B8%5D.GearTypeDisplayName=Building&AllowedGearTypes%5B8%5D.EncodedBitMask=128&ChatType=Classic&IsCopyingAllowed=false&OverridesDefaultAvatar=False";
        $this->postRequestWithCookie("https://www.roblox.com/places/create", $post, $headers);
    }

    public function modifyPlaceSettings($headers, $universeid, $placename = "ROBLOX Place", $ispublic = "true", $allowstudioapiaccess = "false")
    {
        $tokens = $this->retrieveVerificationTokens($headers);
        $post = "Id=" . $universeid . "&__RequestVerificationToken=" . urlencode($tokens["form"]) . "&Name=" . urlencode($placename) . "&IsPublic=$ispublic&AllowStudioAccessToApis=" . $allowstudioapiaccess;
        $headers["Content-Length"] = strlen($post);
        $headers["Referer"] = "https://www.roblox.com/places/create";
        $headers["Cookie"] = $headers["Cookie"] . "; " . $tokens["header"]; 
        $headers["Content-Type"] = "application/x-www-form-urlencoded";
        $headers["Origin"] = "https://www.roblox.com";
        $headers["Connection"] = "keep-alive";
        $headers["Cache-Control"] = "max-age=0";
        $data = $this->postRequestWithCookie("https://www.roblox.com/universes/doconfigure", $post, $headers, array("IgnoreXSRF"=>true,"ReturnStatusCode"=>true));
        if ($data["statuscode"] == 302) {
            return array("success"=>true,"code"=>$data["statuscode"], "data"=>$data["data"]);
        }else{
            return array("success"=>false,"code"=>$data["statuscode"], "data"=>$data["data"]);
        }
    }

    public function getLatestPlaceCreated($headers)
    {
        // games.roblox.com has a delay of about a couple minutes while /develop updates instantly
        $data = $this->getRequestWithCookie("https://www.roblox.com/develop", $headers);
        preg_match("/Show Public Only<\/label><\/div><\/table><div class=items-container><table class=item-table data-item-id=.+? data-rootplace-id/", $data, $matches);
        $universeid = substr($matches[0], strlen("Show Public Only</label></div></table><div class=items-container><table class=item-table data-item-id="), "-" . strlen("data-rootplace-id\""));
        return $universeid;
    }

    public function datastoreGetAsync($headers, $placeid, $datastorename, $datastorekey)
    {
        $headers["User-Agent"] = "RobloxStudio/WinInet";
        $headers["Roblox-Place-Id"] = $placeid;
        $headers["Connection"] = "keep-alive";
        $headers["PlayerCount"] = "1";
        $headers["Content-Type"] = "application/x-www-form-urlencoded";
        $headers["Requester"] = "Client";
        $headers["Cache-Control"] = "no-cache";
        $postData = http_build_query(array(
            "qkeys[0].scope" => "global",
            "qkeys[0].target" => $datastorekey,
            "qkeys[0].key" => $datastorename,
        ));
        $data = json_decode($this->postRequestWithCookie("https://gamepersistence.roblox.com/persistence/getV2?placeId=" . $placeid . "&type=standard&scope=global", $postData, $headers), true);
        return $data;
    }

    public function datastoreSetAsync($headers, $placeid, $datastorename, $datastorekey, $datastorevalue)
    {
        $headers["User-Agent"] = "RobloxStudio/WinInet";
        $headers["Roblox-Place-Id"] = $placeid;
        $headers["Connection"] = "keep-alive";
        $headers["PlayerCount"] = "1";
        $headers["Content-Type"] = "application/x-www-form-urlencoded";
        $headers["Requester"] = "Client";
        $headers["Cache-Control"] = "no-cache";
        $postData = http_build_query(array(
            "value" => $datastorevalue,
        ));
        $data = json_decode($this->postRequestWithCookie("https://gamepersistence.roblox.com/persistence/set?placeId=" . $placeid . "&key=" . $datastorekey . "type=standard&scope=global&target=" . $datastorekey . "&valueLength=" . strlen($datastorevalue), $postData, $headers), true);
        return $data;
    }


    // -- -------------------
    // -- Section: Marketplace-Related Functions
    // -- -------------------

    // -- Get Requests (No Auth)
    // -- -------------------

    public function getProductDetails($assetid) 
    {
        // Todo: Hopefully improve speed by using a different endpoint...
        $data = json_decode($this->file_get_contents_curl("https://api.roblox.com/marketplace/productinfo?assetId=" . $assetid), true);
        return $data;
    }

    public function getAssetSalesData($assetid)
    {
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/asset/" . $assetid . "/sales-data"), true);
        return $data;
    }

    public function getReccomendedAssets($assetid, $numitems = 7)
    {
        $data = json_decode($this->file_get_contents_curl("https://inventory.roblox.com/v1/recommendations/8?contextAssetId=" . $assetid . "&numItems=" . $numitems), true);
        return $data;
    }

    public function getItemSalesCharts($assetid)
    {
        $result = json_decode($this->file_get_contents_curl('https://www.roblox.com/asset/' . $assetid . '/sales-data'), true);
        $result["data"]["HundredEightyDaySalesChart"] = json_decode(substr("[[" . str_replace('|', '],[', $result["data"]["HundredEightyDaySalesChart"]), 0, -3) . "]]", true);
        $result["data"]["HundredEightyDayVolumeChart"] = json_decode(substr("[[" . str_replace('|', '],[', $result["data"]["HundredEightyDayVolumeChart"]), 0, -3) . "]]", true);
        if ($result["data"]["HundredEightyDaySalesChart"] === null) {
            $result["data"]["HundredEightyDaySalesChart"] = array();
        }
        if ($result["data"]["HundredEightyDayVolumeChart"] === null) {
            $result["data"]["HundredEightyDayVolumeChart"] = array();
        }
        return $result["data"];
    }

    public function getAssetOwners($assetid, $limit = 10, $cursor = "", $sort = "Asc") 
    {
        $data = $this->file_get_contents_curl("https://inventory.roblox.com/v1/assets/$assetid/owners?sortOrder=$sort&limit=$limit&cursor=$cursor", array("ReturnStatusCode"=>true));
        if ($data["statuscode"] == 200) {
            return array("success"=>true,"satuscode"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }else{
            return array("success"=>false,"satuscode"=>$data["statuscode"],"data"=>json_decode($data["data"], true));
        }
    }

    public function getResellersOfProduct($productid, $startIndex = 0, $maxRows = 10) 
    {
        // You can retrieve an asset's ProductID through getProductDetails
        $data = json_decode($this->file_get_contents_curl("https://www.roblox.com/asset/resellers?productId=" . $productid . "&startIndex=" . $startIndex . "&maxRows=" .  $maxRows), true);
        return $data;
    }

    // -- Post Requests
    // -- -------------------

    public function purchaseAsset($headers, $productid, $expectedprice, $sellerid, $currency = 1, $userassetid = "")
    // Note: UserAssetId is only reqired for limiteds. Currency 1 = Robux, 2 = Tix (not available anymore)
    {
        /*
        // New Endpoint was disabled for some reason...
        $data = json_decode($this->postRequestWithCookie("https://economy.roblox.com/v1/purchases/products/" . $productid, http_build_query(array("expectedCurrency"=>$currency,"expectedPrice"=>$expectedprice,"expectedSellerId"=>$sellerid)), $headers), true);
        if ($data["purchased"] === true) {
            return array("success"=>true,"data"=>$data);
        }else{
            return array("success"=>false,"data"=>$data);
        }
        */
        $data = json_decode($this->postRequestWithCookie("https://www.roblox.com/api/item.ashx?rqtype=purchase&productID=" . $productid . "&expectedCurrency=1&expectedPrice=" . $expectedprice . "&expectedSellerID=" . $sellerid . "&userAssetID=" . $userassetid, "", $headers), true);
        if ($data["TransactionVerb"] === "bought") {
            return array("success"=>true,"data"=>$data);
        }else{
            return array("success"=>false,"data"=>$data);
        }
    }

    public function placeLimitedForSale($headers, $assetid, $userassetid, $price)
    {
        $query = http_build_query(array("assetId"=>$assetid,"userAssetId"=>$userassetid,"price"=>$price,"sell"=>"true"));
        $data = json_decode($this->postRequestWithCookie(
            "https://www.roblox.com/asset/toggle-sale", 
            $query, 
            $headers
        ), true);
        if ($data["isValid"] === true) {
            return array("success"=>true,"data"=>$data);
        }else{
            return array("success"=>false,"data"=>$data);
        }
    }

    public function takeLimitedOffSale($headers, $assetid, $userassetid)
    {
        $query = http_build_query(array("assetId"=>$assetid,"userAssetId"=>$userassetid,"price"=>0,"sell"=>false));
        $data = json_decode($this->postRequestWithCookie(
            "https://www.roblox.com/asset/toggle-sale", 
            $query, 
            $headers
        ), true);
        if ($data["isValid"] === true) {
            return array("success"=>true,"data"=>$data);
        }else{
            return array("success"=>false,"data"=>$data);
        }
    }

    public function viewTradeData($headers, $tradeid)
    {
        $body = http_build_query(array("TradeID"=>$tradeid,"cmd"=>"pull"));
        $data = json_decode($this->postRequestWithCookie("https://www.roblox.com/trade/tradehandler.ashx", $body, $headers), true);
        $data["data"] = json_decode(urldecode($data["data"]), true);
        return $data;

    }

    public function declineTradeRequest($headers, $tradeid)
    {
        $body = http_build_query(array("TradeID"=>$tradeid,"cmd"=>"decline"));
        $data = json_decode($this->postRequestWithCookie("https://www.roblox.com/trade/tradehandler.ashx", $body, $headers), true);
        if ($data["success"] === true) {
            return array("success"=>true);
        }else{
            return array("success"=>false,"data"=>$data);
        }

    }

    public function sendTradeRequest($headers, $tradejson)
    /*

    Here's a example of the tradejson param.

    $tradejson = array (
        'AgentOfferList' => 
        array (
            0 => 
            array (
            'AgentID' => 733823521,
            'OfferList' => 
            array (
                0 => 
                array (
                'UserAssetID' => '706355407',
                'Name' => 'Bat+Tie',
                'ItemLink' => 'https://www.roblox.com/catalog/63239668/Bat-Tie',
                'ImageLink' => 'https://www.roblox.com/asset-thumbnail/image?assetId=63239668&height=110&width=110',
                'AveragePrice' => 267,
                'OriginalPrice' => '---',
                'SerialNumber' => '---',
                'SerialNumberTotal' => '---',
                'MembershipLevel' => NULL,
                ),
            ),
            'OfferRobux' => 0,
            'OfferValue' => 267,
            ),
            1 => 
            array (
            'AgentID' => 950233944,
            'OfferList' => 
            array (
                0 => 
                array (
                'UserAssetID' => '628707',
                'Name' => 'Sparkle+Time+Fedora',
                'ItemLink' => 'https://www.roblox.com/catalog/1285307/Sparkle-Time-Fedora',
                'ImageLink' => 'https://www.roblox.com/asset-thumbnail/image?assetId=1285307&height=110&width=110',
                'AveragePrice' => 198027,
                'OriginalPrice' => '---',
                'SerialNumber' => '---',
                'SerialNumberTotal' => '---',
                'MembershipLevel' => NULL,
                ),
                1 => 
                array (
                'UserAssetID' => '13698866',
                'Name' => 'Clockwork\'s+Headphones',
                'ItemLink' => 'https://www.roblox.com/catalog/1235488/Clockworks-Headphones',
                'ImageLink' => 'https://www.roblox.com/asset-thumbnail/image?assetId=1235488&height=110&width=110',
                'AveragePrice' => 93688,
                'OriginalPrice' => '---',
                'SerialNumber' => '---',
                'SerialNumberTotal' => '---',
                'MembershipLevel' => NULL,
                ),
                2 => 
                array (
                'UserAssetID' => '1135812970',
                'Name' => 'Clockwork\'s+Shades',
                'ItemLink' => 'https://www.roblox.com/catalog/11748356/Clockworks-Shades',
                'ImageLink' => 'https://www.roblox.com/asset-thumbnail/image?assetId=11748356&height=110&width=110',
                'AveragePrice' => 199508,
                'OriginalPrice' => '---',
                'SerialNumber' => '---',
                'SerialNumberTotal' => '---',
                'MembershipLevel' => NULL,
                ),
            ),
            'OfferRobux' => 0,
            'OfferValue' => 491223,
            ),
        ),
        'IsActive' => false,
        'TradeStatus' => 'Open',
    );

    */
    {
        $body = http_build_query(array("cmd"=>"send","TradeJSON"=>json_encode($tradejson)));
        $data = json_decode($this->postRequestWithCookie("https://www.roblox.com/Trade/tradehandler.ashx", $body, $headers), true);
        if ($data["success"] === true) {
            return array("success"=>true);
        }else{
            return array("success"=>false,"data"=>$data);
        }

    }


    /*
    Random/MISC Functions
    */

    public function validateCookie($headers) 
    {
        $balance = $this->getRequestWithCookie("https://api.roblox.com/currency/balance", $headers, array("ReturnStatusCode"=>true));
        if ($balance["statuscode"] == 200) {
            return true;
        }else{
            return false;
        }
    }

    public function arrayToCsv($array)
    {
        $keys = "";
        foreach ($array as &$key) {
            $keys = $keys . $key . ",";
        } 
        $keys = substr($keys, 0, -1);
        return $keys;
    }
}
