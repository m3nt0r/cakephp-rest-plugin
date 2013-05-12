## CakePHP REST Plugin (1.3 tree-only)

[CakePHP REST Plugin](http://kevin.vanzonneveld.net/techblog/article/cakephp_rest_plugin_presentation/)
takes whatever your existing controller actions gather in viewvars, reformats it in json or xml, and outputs it to the client. Because you hook it into existing actions, you only have to write your features once, and this plugin will just unlock them as API. The plugin knows it's being called by looking at the extension in the url: `.json` or `.xml`.

So, if you've already coded:

 - `/servers/reboot/2`

You can have:

- `/servers/reboot/2.json`
- `/servers/reboot/2.xml`

..up & running in no time.

CakePHP REST Plugin can even change the structure of your existing viewvars using bi-directional xpaths. This way you can extract info using an xpath, and output it to your API clients using another xpath. If this doesn't make any sense, please have a look at the examples.

You attach the `Rest.Rest` component to a controller, but you can limit REST activity to a single action.

For best results, 2 changes to your application have to be made:

 - A check if REST is active inside your error handler & `redirect()`
 - Resource mapping in your router (see docs below)

### Compatibilty

Tested with:

 - CakePHP 1.2
 - CakePHP 1.3

### Friendly "its a fork" Warning

This repository is the CakePHP 1.x version and a fork from 
[kvz/cakephp-rest-plugin](https://github.com/kvz/cakephp-rest-plugin). The version from @kvz already has support for CakePHP 2.x, but i do not plan to follow. If you need this plugin for a 2.x project i recommend to look there.

### Resources

This plugin was based on:

- [Priminister's API presentation during CakeFest #03, Berlin](http://www.cake-toppings.com/2009/07/15/cakefest-berlin/)
- [The help of Jonathan Dalrymple](http://github.com/veritech)
- [REST documentation](http://book.cakephp.org/view/476/REST)
- [CakeDC article](http://cakedc.com/eng/developer/mark_story/2008/12/02/nate-abele-restful-cakephp)

I held a presentation on this plugin during the first Dutch CakePHP meetup:

- [REST presentation at slideshare](http://www.slideshare.net/kevinvz/rest-presentation-2901872)

I'm writing a client side API that talks to this plugin for the company I work for. If you're looking to provide your customers with something similar, it may be helpful to [have a look at it](http://github.com/true/true-api).

### Leave comments

[On my blog](http://kevin.vanzonneveld.net/techblog/article/cakephp_rest_plugin_presentation/)

### Leave money ;)

Like this plugin? Consider [a small donation](https://flattr.com/thing/68756/cakephp-rest-plugin)

Love this plugin? Consider [a big donation](http://pledgie.com/campaigns/12581) :)

### Todo

 - More testing
 - DONE - Cake 1.3 support
 - DONE - The RestLog model that tracks usage should focus more on IP for rate-limiting
   than account info. This is mostly to defend against denial of server & brute
   force attempts
 - DONE - Maybe some Refactoring. This is pretty much the first attempt at a working plugin
 - DONE (thx to Jonathan Dalrymple) - XML (now only JSON is supported)

License: BSD-style

## Installation

### As a git submodule

    git submodule add git://github.com/kvz/cakephp-rest-plugin.git app/plugins/rest
    git submodule update --init

### Other

Just place the files directly under: `app/plugins/rest`

### .htaccess

Do you run Apache? Make your `app/webroot/.htaccess` look like so:

    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

        # Adds AUTH support to Rest Plugin:
        RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
    </IfModule>

In my experience Nginx & FastCGI already make the HTTP_AUTHORIZATION available which is used to parse credentials for authentication.

## Implementation

### Controller

Beware that you can no longer use ->render() yourself

```php
    <?php
    class ServersController extends AppController {
        public $components = array(
            'RequestHandler',
            'Rest.Rest' => array(
                'catchredir' => true, // Recommended unless you implement something yourself
                'debug' => 0,
                'view' => array(
                    'extract' => array('server.Server' => 'servers.0'),
                ),
                'index' => array(
                    'extract' => array('rows.{n}.Server' => 'servers'),
                ),
            ),
        );

        /**
         * Shortcut so you can check in your Controllers wether
         * REST Component is currently active.
         *
         * Use it in your ->flash() methods
         * to forward errors to REST with e.g. $this->Rest->error()
         *
         * @return boolean
         */
        protected function _isRest() {
            return !empty($this->Rest) && is_object($this->Rest) && $this->Rest->isActive();
        }
    }
    ?>
```

`extract` extracts variables you have in: `$this->viewVars`
and makes them available in the resulting XML or JSON under
the name you specify in the value part.

Here's a more simple example of how you would use the viewVar `tweets` **as-is**:

```php
    <?php
    class TweetsController extends AppController {
        public $components = array (
            'Rest.Rest' => array(
                'catchredir' => true,
                'index' => array(
                    'extract' => array('tweets'),
                ),
            ),
        );

        public function index() {
            $tweets = $this->_getTweets();
            $this->set(compact('tweets'));
        }
    }
```

And when asked for the xml version, Rest Plugin would return this to your clients:

```xml
    <?xml version="1.0" encoding="utf-8"?>
    <tweets_response>
      <meta>
        <status>ok</status>
        <feedback>
          <item>
            <message>ok</message>
            <level>info</level>
          </item>
        </feedback>
        <request>
          <request_method>GET</request_method>
          <request_uri>/tweets/index.xml</request_uri>
          <server_protocol>HTTP/1.1</server_protocol>
          <remote_addr>123.123.123.123</remote_addr>
          <server_addr>123.123.123.123</server_addr>
          <http_host>www.example.com</http_host>
          <http_user_agent>My API Client 1.0</http_user_agent>
          <request_time/>
        </request>
        <credentials>
          <class/>
          <apikey/>
          <username/>
        </credentials>
      </meta>
      <data>
        <tweets>
          <item>
            <tweet_id>123</tweet_id>
            <message>looking forward to the finals!</message>
          </item>
          <item>
            <tweet_id>123</tweet_id>
            <message>i need a drink</message>
          </item>
        </tweets>
      </data>
    </tweets_response>
```

As you can see, the controller name + response is always the root element (for json there is no root element). Then the content is divived in `meta` & `data`, and the latter is where your actual viewvars are stored. Meta is there to show any information regarding the validity of the request & response.

### Authorization

Check the HTTP header as shown [here](http://docs.amazonwebservices.com/AmazonS3/latest/dev/index.html?RESTAuthentication.html)

You can control the `authKeyword` setting to control what keyword belongs to your REST API. By default it uses: TRUEREST. Have your users supply a header like:

`Authorization: TRUEREST username=john&password=xxx&apikey=247b5a2f72df375279573f2746686daa`

Now, inside your controller these variables will be available by calling `$this->Rest->credentials()`.

This plugin only handles the parsing of the header, and passes the info on to your app. So login anyone with e.g. `$this->Auth->login()` and the information you retrieved from `$this->Rest->credentials()`;

Example:

```php
    public function beforeFilter () {
        if (!$this->Auth->user()) {
            // Try to login user via REST
            if ($this->Rest->isActive()) {
                $this->Auth->autoRedirect = false;
                $data = array(
                    $this->Auth->userModel => array(
                        'username' => $credentials['username'],
                        'password' => $credentials['password'],
                    ),
                );
                $data = $this->Auth->hashPasswords($data);
                if (!$this->Auth->login($data)) {
                    $msg = sprintf('Unable to log you in with the supplied credentials. ');
                    return $this->Rest->abort(array('status' => '403', 'error' => $msg));
                }
            }
        }
        parent::beforeFilter();
    }
```

### Schema

If you're going to make use of this plugin's Logging & Ratelimitting (default) and you should run the database schema found in: `config/schema/rest_logs.sql`.

### Router

```php
    // Add an element for each controller that you want to open up
    // in the REST API
    Router::mapResources(array('servers'));

    // Add XML + JSON to your parseExtensions
    Router::parseExtensions('rss', 'json', 'xml', 'json', 'pdf');
```

### Callbacks

If you're using the built-in ratelimiter, you may still want a little control yourself.
I provide that in the form of 4 callbacks:

```php
    public function restlogBeforeSave ($Rest) {}
    public function restlogAfterSave ($Rest) {}
    public function restlogBeforeFind ($Rest) {}
    public function restlogAfterFind ($Rest) {}
```

That will be called in you AppController if they exists.

You may want to give a specific user a specific ratelimit. In that case you can use
the following callback in your User Model:

```php
    public function restRatelimitMax ($Rest, $credentials = array()) { }
```

And for that user the return value of the callback will be used instead of the general
class limit you could have specified in the settings.

### Customizing callback

You can map callbacks to different places using the `callbacks` setting like so:

```php
    <?php
    class ServersController extends AppController {
        public $components = array(
            'Rest.Rest' => array(
                'catchredir' => true,
                'callbacks' => array(
                    'cbRestlogBeforeSave' => 'restlogBeforeSave',
                    'cbRestlogAfterSave' => 'restlogAfterSave',
                    'cbRestlogBeforeFind' => 'restlogBeforeFind',
                    'cbRestlogAfterFind' => array('Common', 'setCache'),
                    'cbRestRatelimitMax' => 'restRatelimitMax',
                ),
            ),
        );
    }
```

If the resolved callback is a string we assume it's a method in the calling controller.

### JSONP support

[Thanks to](https://github.com/kvz/cakephp-rest-plugin/pull/3#issuecomment-883201)
[Chris Toppon](http://www.supermethod.com/) there
now also is [JSONP](http://en.wikipedia.org/wiki/JSON#JSONP) support out of the box.

No extra PHP code or configuration is required on the server side with this patch, just supply either the parameter `callback` or `jsoncallback` to the JSON url provided by your plugin and the output will be wrapped in mycallback as a function.

For example:

    <script type="text/javascript">
    var showPrice = function (data) {
       alert('Product: ' + data.product.name + ', Price: ' + data.product.price);
    }
    </script>
    <script type="text/javascript" src="http://server2.example.com/getjson?callback=showPrice"></script>

With jQuery, something similar could have been achieved like so:

    jQuery.getJSON('http://www.yourdomain.com/products/product.json', function (data) {
        alert('Product: ' + data.product.name + ', Price: ' + data.product.price);
    });

But for cross-domain requests, use JSONP. jQuery will substitute `?` with the callback.

    jQuery.getJSON('http://www.yourdomain.com/products/product.json?callback=?', function (data) {
        alert('Product: ' + data.product.name + ', Price: ' + data.product.price);
    });

Good explanations of typical JSONP usage here:

 - [What is JSONP?](http://remysharp.com/2007/10/08/what-is-jsonp/)
 - [Cross-domain communications with JSONP, Part 1: Combine JSONP and jQuery to quickly build powerful mashups](http://www.ibm.com/developerworks/library/wa-aj-jsonp1/)
