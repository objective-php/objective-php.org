# Objective PHP Website

## What is it ? 

This is the source of the official Objective-PHP.org website.

The website contains all the documentation about Objective-Php and its components.  

## How to make it work
The website is build with composer:
```
composer install
```
And yarn :
```
yarn install
```

The website use Server Side Includes, to work perfectly it should be hosted on an Nginx or Apache server with ngx_http_ssi_module or mod_include activated.

To generate the doc, run the following command in your terminal

```
vendor/bin/op generate --all
```

The website can listen to webhooks to generate new doc on new tags or updates.

