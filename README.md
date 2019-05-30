# Roblox-php

This is my personal Roblox-PHP API Wrapper I use for all my Roblox Projects. An example of this being used in production is [rblx.store](https://rblx.store/)

## Requirments
* cURL
* PHP (Tested with 7.0, 7.1, and 7.2)

## Installation
1. Download the file roblox.php
2. `require()` it into the script that you want to use it in.
3. Define the `roblox` class, such as by adding `$roblox = new roblox;` under the `require()`

## Todo
 - Add proper error handling. Some functions require you to json_decode the return value to see if it was successful.
 - Move over some of the older endpoints to the newever subdomain endpoints. Specifically, `spendGroupFunds()`
 - Add Group Creation. `createGroup()` is temporarily disabled while I figure out webformboundries lol
