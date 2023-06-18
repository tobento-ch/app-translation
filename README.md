# App Translation

Translation support for the app.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Translation Boot](#translation-boot)
        - [Translate Message](#translate-message)
        - [Configure Translator](#configure-translator)
        - [Migrate Translations](#migrate-translations)
        - [Add Translations](#add-translations)
        - [Customize Translations](#customize-translations)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app translation project running this command.

```
composer require tobento/app-translation
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Translation Boot

The translation boot does the following:

* implements [TranslatorInterface](https://github.com/tobento-ch/service-translation#create-translator)
* adds ```trans``` app macro

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()->dir(realpath(__DIR__.'/../app/'), 'app');
    
// You might add trans directory:
$app->dirs()
    ->dir($app->dir('app').'trans', 'trans', group: 'trans', priority: 100);
// if not set, the same dir is specified by the boot as default!

// Adding boots
$app->boot(\Tobento\App\Translation\Boot\Translation::class);

// Run the app
$app->run();
```

By default, [Files Resources](https://github.com/tobento-ch/service-translation#files-resources) are used for the translator.

### Translate Message

You can translate messages in several ways:

**Using the app**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Translation\TranslatorInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()->dir(realpath(__DIR__.'/../app/'), 'app');
    
// Adding boots
$app->boot(\Tobento\App\Translation\Boot\Translation::class);
$app->booting();

$translator = $app->get(TranslatorInterface::class);

$translated = $translator->trans(
    message: 'Hi :name',
    parameters: [':name' => 'John'],
    locale: 'de'
);

// or using the app macro:
$translated = $app->trans(
    message: 'Hi :name',
    parameters: [':name' => 'John'],
    locale: 'de'
);

// Run the app
$app->run();
```

**Using autowiring**

You can also request the ```TranslatorInterface::class``` in any class resolved by the app.

```php
use Tobento\Service\Translation\TranslatorInterface;

class SomeService
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {}
}
```

**Using the translation boot**

```php
use Tobento\App\Boot;
use Tobento\App\Translation\Boot\Translation;

class AnyServiceBoot extends Boot
{
    public const BOOT = [
        // you may ensure the view boot.
        Translation::class,
    ];
    
    public function boot(Translation $translation)
    {
        $translated = $translation->trans(
            message: 'Hi :name',
            parameters: [':name' => 'John'],
            locale: 'de'
        );
    }
}
```

Check out the [**Translation Service**](https://github.com/tobento-ch/service-translation) to learn more about it.

### Configure Translator

**Configure locale using the app language**

First install the [**app-language**](https://github.com/tobento-ch/app-language) bundle:

```
composer require tobento/app-language
```

Then, just boot the ```Language::class```. That's all. The locales will be set based on the ```Tobento\Service\Language\LanguagesInterface::class```.

```php
// ...

$app->boot(\Tobento\App\Language\Boot\Language::class);
$app->boot(\Tobento\App\Translation\Boot\Translation::class);

// ...
```

**Configure locale manually**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Translation\TranslatorInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()->dir(realpath(__DIR__.'/../app/'), 'app');
    
// Adding boots
$app->boot(\Tobento\App\Translation\Boot\Translation::class);
$app->booting();

$translator = $app->get(TranslatorInterface::class);

// set the default locale:
$translator->setLocale('de');

// set the locale fallbacks:
$translator->setLocaleFallbacks(['de' => 'en']);

// set the locale mapping:
$translator->setLocaleMapping(['de' => 'de-CH']);
    
// or using the app on method:
$app->on(TranslatorInterface::class, function(TranslatorInterface $translator) {
    $translator->setLocale('de');
    $translator->setLocaleFallbacks(['de' => 'en']);
    $translator->setLocaleMapping(['de' => 'de-CH']);
});

// Run the app
$app->run();
```

**Configure missing translations**

If you may want to log missing translation, you can set the ```MissingTranslationHandlerInterface::class``` implementation to fit your needs.

```php
use Tobento\App\AppFactory;
use Tobento\Service\Translation\MissingTranslationHandlerInterface;
use Tobento\Service\Translation\MissingTranslationHandler;
use Psr\Log\LoggerInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()->dir(realpath(__DIR__.'/../app/'), 'app');

// Adding boots
$app->boot(\Tobento\App\Translation\Boot\Translation::class);
$app->booting();

$app->set(MissingTranslationHandlerInterface::class, function()) {
    return new MissingTranslationHandler($logger); // LoggerInterface, any PSR-3 logger
});

// Run the app
$app->run();
```

### Migrate Translations

By default, [Files Resources](https://github.com/tobento-ch/service-translation#files-resources) are used for the translator. Therefore, you might install and use the [App Migration](https://github.com/tobento-ch/app-migration) bundle:

**Writing a migration**

```php
use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Dir\DirsInterface;

class TranslationFiles implements MigrationInterface
{
    protected array $files;
    
    /**
     * Create a new TranslationFiles.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $transDir = realpath(__DIR__.'/../../').'/resources/trans/';
        
        $this->files = [
            $this->dirs->get('trans').'en/' => [
                $transDir.'en/en.json',
                $transDir.'en/shop.json',
            ],
            $this->dirs->get('trans').'de/' => [
                $transDir.'de/de.json',
                $transDir.'de/shop.json',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Translation files.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        return new Actions(
            new FilesCopy($this->files),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            new FilesDelete($this->files),
        );
    }
}
```

**Migration Boot**

```php
use Tobento\App\Boot;
use Tobento\App\Migration\Boot\Migration;

class TranslationFilesMigration extends Boot
{
    public const BOOT = [
        // you may ensure the migration boot.
        Migration::class,
    ];
    
    public function boot(Migration $migration)
    {
        // Install migrations
        $migration->install(TranslationFiles::class);
    }
}
```

**App example**

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()->dir(realpath(__DIR__.'/../app/'), 'app');
    
// Adding boots
$app->boot(\Tobento\App\Translation\Boot\Translation::class);
$app->boot(TranslationFilesMigration::class);

//...

// Run the app
$app->run();
```

### Add Translations

You can simply add more translations by the following way:

```php
use Tobento\App\AppFactory;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Translation\Resource;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()->dir(realpath(__DIR__.'/../app/'), 'app');
    
// Adding boots
$app->boot(\Tobento\App\Translation\Boot\Translation::class);

// using the app on method:
$app->on(TranslatorInterface::class, function(TranslatorInterface $translator) {
    $translator->resources()->add(new Resource('*', 'de', [
        'Hello World' => 'Hallo Welt',
    ]));
    
    $translator->resources()->add(new Resource('shop', 'de', [
        'Cart' => 'Warenkorb',
    ]));
});

// Run the app
$app->run();
```

You may check out the [Add Resources](https://github.com/tobento-ch/service-translation/tree/1.x#add-resources) section to learn more about it.

### Customize Translations

You might customize/override translations by the following ways:

**By adding a resource with higher priority**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Translation\Resource;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()->dir(realpath(__DIR__.'/../app/'), 'app');
    
// Adding boots
$app->boot(\Tobento\App\Translation\Boot\Translation::class);

// using the app on method:
$app->on(TranslatorInterface::class, function(TranslatorInterface $translator) {

    $translator->resources()->add(new Resource(
        name: 'shop',
        locale: 'de',
        translations: ['Hello World' => 'Hallo Welt'],
        priority: 200, // set a higher the default added
    ));
});

// Run the app
$app->run();
```

You may specify only the translations you want to overrride as same named resources get merged.

You may check out the [Add Resources](https://github.com/tobento-ch/service-translation/tree/1.x#add-resources) section to learn more about it.

**By adding a new trans dir with higher priority**

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()->dir(realpath(__DIR__.'/../app/'), 'app');
    
// Add trans directory with higher priority:
$this->app->dirs()->dir(
    dir: $this->app->dir('app').'trans-custom/',
    
    // do not use 'trans' as name for migration purposes
    name: 'trans.custom',
    
    group: 'trans',
    
    // add higher priority as default trans dir:
    priority: 300,
);

// Adding boots
$app->boot(\Tobento\App\Translation\Boot\Translation::class);

// Run the app
$app->run();
```

Then just add the translation files you wish to override in the defined directory. If the file does not exist, the file from the default trans directory is used.

**Using the translation manager**

In progress...

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)