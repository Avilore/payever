# exozet/behat-utils

The `exozet/behat-utils` provides some traits for easier api testing with behat and mink.

## Usage

```console
$ composer require exozet/behat-utils
```

and add

```php
use \Exozet\Behat\Utils\Base\JsonApiSteps;
```

to your context, like this:

```php
class ServiceContext extends \Behat\MinkExtension\Context\MinkContext
{
    use \Exozet\Behat\Utils\Base\JsonApiSteps;
}
```

That's it!

## Traits

### JsonApiSteps

The `JsonApiSteps` are helpful for testing json apis.

## LICENSE

The behat-utils is copyright by Exozet (http://exozet.com) and licensed under the terms of MIT License.

