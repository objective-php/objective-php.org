# Objective PHP Website

## What is it ? 

This is the source of the official Objective-PHP.org website.

The website contains all the documentation about Objective-Php and its components.  

## How to make it work
### Initialisation

The website is build with composer:

```bash
composer install
```
And yarn :
```bash
yarn install
yarn build
```

The website use Server Side Includes, to work perfectly it should be hosted on an Nginx or Apache server with ngx_http_ssi_module or mod_include activated.

### Get content

The website listen to webhooks to generate new doc on new tags.

To register a new repository, you have to add a Webhook with this post URL: 

http://www.objective-php.org/api/builApi?version=1.0.0&min-version=2.0

You can change the min-version parameter or remove it to make the program try to documente every tag.

In your webhook creation you have to select "Let me select individual events" and check the "Branch or tag creation" option. 



The website will generate a doc-api and an user doc for each minor tag.

The user documentation has to be markdown files placed in a /docs directory.  







