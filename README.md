#Simple PHP Rewrite CLASS

The DI_Rewrite CLASS isn't meant to be a framework like others. 
It’s not a definitive guide to how a web application should be built. 
It's tailored to my needs, so if you attempt to use it as a foundation for a future project, your mileage may vary. 
Modify and mangle at will, but know this is not a supported system.

include and create an instance of a class
```php
require_once 'class.di_rewrite.php';
$di_rewrite = new DI_Rewrite();
```
first simple example of single rule and run it: 
```php
$di_rewrite->rewrite_rule('/', function(){})->run();
```
multi rewrite rule by add_rewrite_rule function and start
```php
function sayhello(){
  echo 'hello';
}
function sayhello_admin(){
  echo 'hello admin';
}
function sayhello_user(){
  echo 'hello user';
}
$di_rewrite->add_rewrite_rule('/', 'sayhello');
$di_rewrite->add_rewrite_rule('/admin', 'sayhello_admin');
$di_rewrite->add_rewrite_rule('/user', 'sayhello_user');
$di_rewrite->start();
```

method filter:
```php
$di_rewrite->add_rewrite_rule('GET /', function(){echo 'this is GET methode'});
$di_rewrite->add_rewrite_rule('POST /', function(){echo 'this is POST methode'});
$di_rewrite->start();
```
multi method filter:
```php
$di_rewrite->add_rewrite_rule('GET|POST /', function(){echo 'this is GET or POST methode'});
$di_rewrite->start();
```
more advance example:

```php
$di_rewrite->add_rewrite_rule('/user/[0-9]+', function(){
    // This will match /user/1234
});

$di_rewrite->add_rewrite_rule('/@name/@id', function($name, $id){
    echo "hello, $name ($id)!";
});

$di_rewrite->add_rewrite_rule('/@name/@id:[0-9]{3}', function($name, $id){
    // This will match /bob/123
    // But will not match /bob/12345
});


$di_rewrite->add_rewrite_rule('/blog(/@year(/@month(/@day)))', function($year, $month, $day){
    // This will match the following URLS:
    // /blog/2015/12/10
    // /blog/2015/12
    // /blog/2015
    // /blog
});


$di_rewrite->add_rewrite_rule('/blog/*', function($asterisk =''){
    // This will match /blog/2015/02/01
		// $asterisk == 2015/02/01
});

$di_rewrite->add_rewrite_rule('*', function(){
    // Do something
});

$di_rewrite->add_rewrite_rule('/user/@name', function($name){
    // Check some condition
    if ($name != "Bob") {
        // Continue to next route
        return true;
    }
});

$di_rewrite->add_rewrite_rule('/user/*', function($asterisk =''){
    // This will get called
	$asterisk = *
});

$di_rewrite->start();
```


