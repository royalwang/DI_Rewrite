#Simple PHP Rewrite CLASS

The DI_Rewrite CLASS isn't meant to be a framework like others. 
It’s not a definitive guide to how a web application should be built. 
It's tailored to my needs, so if you attempt to use it as a foundation for a future project, your mileage may vary. 
Modify and mangle at will, but know this is not a supported system.

```php
require 'includes/class-di_rewrite.php';

$di_rewrite = new DI_Rewrite();
$di_rewrite->rewrite_rule('/', function(){})->run();
```
