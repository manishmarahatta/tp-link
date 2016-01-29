# TP-Link 940/941ND

### Overview
Control TP-Link 940/941ND router using PHP. This has been tested with `TP-Link 940/941ND` only but might work with tp link's other routers running similar software.

`This package will not work if you have updated your router's firmware to the latest version as all the vulnerabilities have been patched.`

### Installation
Add the following to your `composer.json`

```json
"nikhil-pandey/tp-link": "dev-master"
```

###Usage
The basic usage is shown below.
```php
// Create new instance
// Accepts 3 parameters. [ Host, Router Username, Router Password ]
// All the parameters are optional, you can set them later
$router = new \NikhilPandey\TpLink\Router($routerHost);
// Setting the Router Username and password if you already haven't
$router->setAuth('router_username', 'router_password');
// Connecting to the WAN connection
$router->connect();
// Waits for specified seconds
$router->wait($seconds);
// Disconnecting the WAN connection
$router->disconnect();
// The current configuration of the router
$router->getConfig();
// The current configuration of the router as an associative array
$router->getConfigAssoc();
```

You can also use static method to instantiate the router and chain methods.
```php
use NikhilPandey\TpLink\Router;

$router = Router::at($routerAddress)
    ->setAuth($loginUsername, $loginPassword)
    ->connect()
    ->disconnect()
    ->connect($newWanUsername, $newWanPassword)
    ->wait()
    ->disconnect($newWanUsername, $newWanPassword)
    ->reconnect();
$configuration = $router->getConfigAssoc();
if($configuration['status'] == Router.CONNECTED){
    // ...
}
```
For more information about the constants and other stuffs, dive into the code :)

### Exceptions
Three types of exceptions might occur. `InvalidAuthException`, `UndefinedAuthException` and `UnknownResponseException`. You can catch those and take appropriate actions.
```php
use NikhilPandey\TpLink\Router;
use NikhilPandey\TpLink\Exceptions\InvalidAuthException
// ...
try{
    $router = Router::at($routerAddress)
        ->setAuth($loginUsername, $loginPassword)
        ->connect()
        ->disconnect()
        ->connect($newWanUsername, $newWanPassword);
} catch(InvalidAuthException $e){
    // Handle the exception
} catch(Exception $e){
    // ...
}
```

### Laravel
If you are using it with laravel you can set the service provider and the facade.

#### Service Provider
```php
'providers' => [
    NikhilPandey\TpLink\TpLinkServiceProvider::class,
]
```
#### Facade
```php
'facades'   => [
    'MyRouter'     => NikhilPandey\TpLink\Facades\TpLink::class,
]
```

After doing so, you can use the program as follows.
```php
app('tplink')
    ->setHost($routerHost)
    ->setAuth($routerUsername, $routerPassword)
    ->setUsername($newWanUsername)
    ->setPassword($newWanPassword)
    ->connect();

MyRouter::at($routerHost)
    ->setAuth($routerUsername, $routerPassword)
    ->setUsername($newWanUsername)
    ->setPassword($newWanPassword)
    ->connect();
```

### License
This package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)