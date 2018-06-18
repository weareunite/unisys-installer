# unisys-installer
Unisys skeleton installer for api or frontend.

## Requirements

This package requires PHP 7.1 or higher

## Installation


For clean skeleton based on Laravel 5.6, you can use weareunite/unisys-installer that do all bored work for you. Let's install it globally:
``` bash
composer global require "weareunite/unisys-installer"
```

Now you can create a new Unisys skeleton:

``` bash
unisys new project_name
```

This is going to install all dependencies, publish all important vendor configs, migrate, setup some configs and run migrations.

Command is going to generate and print the password for the default administrator account. Do not forget to save this password.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
