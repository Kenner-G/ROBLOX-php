# ROBLOX-php

This is my personal Roblox-PHP API Wrapper I use for all my Roblox Projects. An example of this being used in production is [rblx.trade](https://rblx.trade/)

## Requirments
* cURL
* PHP (Tested with 7.0, 7.1, and 7.2)

## Todo
 - Add proper error handling. Some functions require you to json_decode the return value to see if it was successful.
 - Move over some of the older endpoints to the newever subdomain endpoints. Specifically, `spendGroupFunds()`
