# OGGEH Twig Extension

This is a free extension introduces our example implementation on top of Twig template engine, for more control over our API Response (_conditional statements, loops, and custom functionality_).

> You need a Developer Account to obtain your App API Keys, if you don't have an account, [request](https://account.oggeh.com/request) one now.

## Getting Started

1. First, you need to install Twig
```
composer require twig/twig:~2.0
```
2. Next, you need to enter your App API Key in `index.php`
```php
OGGEH::configure('domain', 'domain.ltd');
OGGEH::configure('api_key', '[APP_API_KEY]');
```
3. For local environment, you need to enter your App Sandbox Key as well in `index.php`, and set _sandbox_ setting to `true`
```php
OGGEH::configure('sandbox_key', '[APP_SANDBOX_KEY]');
OGGEH::configure('sandbox', true);
```
4. Optionally, you can configure your own Frontend dictionary for translating page custom model attributes as follows:
```php
OGGEH::configure('i18n', array(
  'category'=>array(
    'en'=>'Category',
    'ar'=>'التصنيف'
  ),
  'client'=>array(
    'en'=>'Client',
    'ar'=>'العميل'
  )
));
```
5. Edit your _hosts_ file and append:
```
127.0.0.1 app.domain.ltd
```
6. Preview example template in browser at http://app.domain.ltd

## IMPORTANT

You will not be charged for your apps in development mode. Please do *not* use _Sandbox_ headers in production mode to avoid blocking your App along with your Developer Account for violating our terms and conditions!
If you're planning to use this example, remove the `SandBox` header from JavaScript (_assets/js/main.js @line 109_)

## How it Works

The library accepts the following URL Segments:
```
http://domain.ltd/?lang=&module=&param1=&param2=
```

If you're familiar with apache rewrite rules, you can rename `htaccess.txt` to `.htaccess` which redirects all requests at your Frontend Template to the above index file as follows:
```
http://domain.ltd/:lang/:module/:param1/:param2
```
Remember to uncomment rewrite settings at `index.php` before activating this file, in addition to all URLs in your template files (_including javascript if necessary_).

URL Segment | Description
--- | ---
domain.ltd | Your App domain as entered during creation.
:lang | URL language code (_for example: en_), this is how you pass target language to our API Requests.
:module | Represents which content model you want to retreive from your customer's content (_page, album, .. etc_).
:child_id | Represents additional filtering parameter to the selected model (_for example: page-unique-identifier_).
:extra_id | Represents an extra parameter to the selected model.

The extension maps each model from your Frontend Request URL above to an HTML template file inside the _tpl_ directory.

As of the home page, you need to keep a default HTML file _tpl/home.html_.
* You can use _tpl/404.html_ for invalid requests.
* You can use _tpl/inactive.html_ to be displayed when your App is not in production mode.

The extension supports the following functions:

Function | Description
--- | ---
call('json') | Makes stacked API request using php curl.
get('alias') | Retrives individual API response by method _alias_ defined for each request method.
trans('phrase') | Translates custom phrases defined at _index.php_.
flag('lang') | Maps the language code to a country code (_defined at locale.json_).

The extension supports the following filters:

Filter | Description
--- | ---
urldecode | Decodes URL-encoded string.

### Usage

There're 2 more files you need to keep for proper usage:

1. `tpl/api.twig`: defines global API methods, mostly reused in header and footer blocks, and extends those based on current module from URL.
2. `tpl/blocks.twig`: define both header and booter blocks to be used later on each module template.

At any given module template (`home`, `page`, `news`, `album`, `contact`, or `search`), you should keep the following structure:
```twig
{# Import global wrapper #}
{% extends 'api.twig' %}

{% block api %}
    
    {# Fetch response from method alias `app` to be used accross all blocks #}
    {% set app = get('app') %}

    {# Reuse common blocks (i.e. header and footer) #}
    {% use 'blocks.twig' %}

    {# Print header block #}
    {{ block('header') }}

    {# Print content block #}
    {% block content  %}

    	{# Your module template goes here  #}

    {% endblock %}

    {# Print footer block #}
    {{ block('footer') }}

{% endblock %}
```
The above sequence enables you to make only one stacked API request and retrieve individual responses, for example:
```twig
{% set contact = get('contacts') %}
<ul>
{% for contact in contacts %}
    <li>
        {{ contact.name }}: {{ contact.email }}
    </li>
{% endfor %}
</ul>
```
Where `contacts` is the alias for the following API request:
```
curl -H "Content-Type: application/json" -X POST -d '[{"alias":"contacts","method":"get.contacts","select":"name,email"}]' https://api.oggeh.com/?api_key=[APP_API_KEY]&lang=en
```

## API Documentation

See [API Reference](http://docs.oggeh.com/#reference-section) for additional details on available values for _select_ attribute on each API Method.

## Template in Use

**Template in Use**
[Editorial by HTML5 UP](https://html5up.net/editorial)

**Template License**
[Creative Commons Attribution 3.0](https://html5up.net/license)

**Template Credits**
Built by [AJ](https://twitter.com/ajlkn) - Modified by [OGGEH Cloud Computing](https://dev.oggeh.com)

### Photos used
[unsplush.com](http://unsplush.com)

