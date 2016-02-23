# dude!
PHP micro-framework (5.5+ / 7+)

## Installation
1. Create a folder for your application (ex. `/AppFolder`)
2. Put (or clone) files in the folder
3. Pur composer.phar into the same folder
4. Open terminal in the same folder and run `php composer.phar install`
3. Create new Virtual Host in your Web-Server (Apache, Nginx)
4. Point Virtual Host root to `/AppFolder/public`
5. Define a MOD_REWRITE rules for Apache or LOCATION for nginx to point at `/AppFolder/public/index.php`

#### Apache
```
<Directory "/AppFolder">
    AllowOverride None
    Require all granted
    <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteBase /
      RewriteRule ^index\.php$ - [L]
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule . /index.php
    </IfModule>
</Directory>
```
## After installation
#### Simple way
Define some route in `/AppFolder/App/route.php`
```php
App::Route(ROUTE_ALL,'/',function ($req,$resp) {
    echo "Hello world";
});
```
And after go to your server `http://your_server/`

#### Complex way
1. Define some controller in the `/App/Controller` folder
```php
namespace App\Controller;
use Core\Controller;
use Core\Server\Request;
use Core\Server\Response;
class ExampleController extends Controller
{
  public function testRoute(Request $request, Response $response){
    $response->send(200,"Hello world.");
  }
}
```
2. Define controller route in `/AppFolder/App/route.php`
```php
App::Route(ROUTE_ALL,'/',function ($req,$resp) {
    App::routeController('ExampleController@testRoute',$req,$resp);
});
```
