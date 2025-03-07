# FrankenPHP worker mode for Craft CMS

This package configure FrankenPHP worker mode with Craft CMS. This is only a package to start the worker mode. You need to install and configure FrankenPHP separately. Check the [FrankenPHP documentation](https://frankenphp.dev/docs/worker/#using-frankenphp-workers) for more information. 


## Installation
```
composer require performing/craft-frankenphp
```

## Configuration
Update your `web/index.php` file to this version:
```php
// Load shared bootstrap
require dirname(__DIR__) . '/bootstrap.php';

// Load and run Craft
$app = require CRAFT_VENDOR_PATH . '/performing/craft-frankenphp/web.php';
$app->run();
```

## License
This package is open-sourced software licensed under the [MIT license](LICENSE.md).
