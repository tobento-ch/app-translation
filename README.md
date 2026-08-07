# App Translation

Integrates the [service-translation](https://github.com/tobento-ch/service-translation) package into your application and provides tools for scanning, managing, and customizing translation messages.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Translation Boot](#translation-boot)
    - [Basic Usage](#basic-usage)
        - [Translate Message](#translate-message)
    - [Configure Translator](#configure-translator)
        - [Configure Locales](#configure-locales)
        - [Configure Missing Translation Handler](#configure-missing-translation-handler)
    - [Managing Translations](#managing-translations)
        - [Migrate Translations](#migrate-translations)
        - [Add Translations](#add-translations)
    - [Customize Translations](#customize-translations)
        - [Using Resources](#using-resources)
        - [Using Directory](#using-directory)
        - [Using Translation Web App](#using-translation-web-app)
    - [Scanning Messages](#scanning-messages)
        - [Message Scanner](#message-scanner)
            - [Default Patterns](#default-patterns)
        - [Run Scanner](#run-scanner)
    - [Console](#console)
        - [List Translations Command](#list-translations-command)
        - [Resources Command](#resouces-command)
        - [Scan Messages Command](#scan-messages-command)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app translation project running this command.

```
composer require tobento/app-translation
```

## Requirements

- PHP 8.4 or greater

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
$app = new AppFactory()->createApp();

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

## Translate Message

You can translate messages in several ways:

**Using the app**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Translation\TranslatorInterface;

// Create the app
$app = new AppFactory()->createApp();

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

**Using the trans function**

```php
use function Tobento\App\Translation\{trans};

$translated = trans(
    message: 'Hi :name',
    parameters: [':name' => 'John'],
    locale: 'de'
);
```

## Configure Translator

### Configure Locales

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
$app = new AppFactory()->createApp();

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

### Configure Missing Translation Handler

If you may want to log missing translation, you can set the ```MissingTranslationHandlerInterface::class``` implementation to fit your needs.

```php
use Tobento\App\AppFactory;
use Tobento\Service\Translation\MissingTranslationHandlerInterface;
use Tobento\Service\Translation\MissingTranslationHandler;
use Psr\Log\LoggerInterface;

// Create the app
$app = new AppFactory()->createApp();

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

You can find more handlers in the [Missing Translation Handler](https://github.com/tobento-ch/service-translation#missing-translation-handler) section of the Translation Service.

## Managing Translations

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
$app = new AppFactory()->createApp();

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
$app = new AppFactory()->createApp();

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

You may check out the [Add Resources](https://github.com/tobento-ch/service-translation#add-resources) section to learn more about it.

## Customize Translations

Customizing translations allows you to override existing messages or provide alternative translations without modifying the original translation files. Tobento’s translation system is fully resource-driven and priority-based, so you can extend or replace translations using resources, directories, or external tools such as the [Translation Web App](https://github.com/tobento-ch/app-translation-web).

### Using Resources

Resources let you override or extend translations programmatically.  
By adding a resource with a higher priority, your custom translations take precedence over the default ones.  
You only need to specify the **translations** you want to override - resources with the same name are automatically merged.

You may check out the [Add Resources](https://github.com/tobento-ch/service-translation#add-resources) section to learn more about how resources work in the underlying Translation Service.

**Example**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Translation\Resource;

// Create the app
$app = new AppFactory()->createApp();

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

### Using Directory

You can also customize translations by adding a new translation directory with a higher priority.  
Any translation file placed in this directory overrides the corresponding file in the default `trans` directory.  
If a file does not exist in your custom directory, the default file is used automatically.

This approach is ideal when you want to override entire translation files or maintain custom translations separately from the application's default ones.

**Example**

```php
use Tobento\App\AppFactory;

// Create the app
$app = new AppFactory()->createApp();

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

### Using Translation Web App

For a more convenient workflow, you can manage, override, and publish translations using the **App Translation Web** package.  
It provides a full browser-based interface for editing, organizing, importing, exporting, and onboarding translations across one or multiple apps.

Check out [App Translation Web](https://github.com/tobento-ch/app-translation-web) for more details.

## Scanning Messages

The translation system includes a message scanner that can automatically detect translation messages in your source code.  
This is useful when creating a new app, customizing existing features, or adding new translation messages during development.  
It helps you keep your translation resources aligned with the messages used in your application code.

The scanner analyzes your project using configurable patterns, allowing you to adapt it to different coding styles or message formats.

### Message Scanner

The Message Scanner is responsible for locating translation messages throughout your application.  
It supports multiple patterns and flexible configuration so you can tailor the scanning process to your project structure.

**Default Output Path and Directory**

By default, the Translation Boot registers the `MessageScannerInterface` with:

- a default output path  
- a default directory (`src`)  
- the default patterns  

This ensures the scanner works out-of-the-box without requiring any command-line options.

```php
use Tobento\App\Translation\MessageScanner;
use Tobento\App\Translation\MessageScannerInterface;

$this->app->set(MessageScannerInterface::class, function(): MessageScannerInterface {
    return new MessageScanner(
        outputPath: $this->app->dir('root').'build/collected-messages.json'
    )
    ->withDefaultPatterns()
    ->withDirectory($this->app->dir('root').'src');    
});
```

The file `collected-messages.json` is created in your project root directory and is used when running the scanner with the `--output` option.

If you want to change where scanned messages are stored see next section.

**Customizing Scanner**

When the [scanner is executed](#run-scanner), a default scanner instance is provided by the Translation Boot.  
It uses the default patterns and directories defined in the boot configuration.

You may customize the scanner by modifying the `MessageScannerInterface` implementation.  
This allows you to add or override patterns, adjust directories, or extend the scanning behavior to match your application's needs.

```php
use Tobento\App\Translation\MessageScannerInterface;

// using the app on method:
$app->on(
    MessageScannerInterface::class,
    function(MessageScannerInterface $scanner): MessageScannerInterface {
        return $scanner
            ->withPattern("/->trans\(\s*'([^']+)'/m")
            ->withOutputPath('path/to/messages.json');
    }
);
```

**Available Methods**

All `with*` methods are immutable, returning a new scanner instance.

```php
// add a single pattern
$scanner = $scanner->withPattern("/->trans\(\s*'([^']+)'/m");

// replace all patterns
$scanner = $scanner->withPatterns([
    "/->trans\(\s*'([^']+)'/m",
    "/trans\(\s*\"([^\"]+)\"/m",
]);

// add a directory to scan
$scanner = $scanner->withDirectory('path/to/app');

// replace all directories
$scanner = $scanner->withDirectories([
    'path/to/app',
    'path/to/src',
]);

// set output file path
$scanner = $scanner->withOutputPath('path/to/messages.json');

// get directories
$dirs = $scanner->getDirectories();

// get patterns
$patterns = $scanner->getPatterns();

// scan and return messages
$messages = $scanner->scan();

// store messages to output path
$scanner->storeOutputTo($messages);

// get output file path
$path = $scanner->getStoreOutputPath();
```

#### Default Patterns

The default scanner provides a set of default patterns that detect common translation usages.  
These patterns cover typical message calls and are suitable for most applications.

```php
use Tobento\App\Translation\MessageScanner;

$scanner = new MessageScanner()->withDefaultPatterns();
```

The following patterns are added:

```php
// trans('...')
"/\btrans\(\s*'([^']+)'/m",
"/\btrans\(\s*\"([^\"]+)\"/m",

// ->trans('...')
"/->trans\(\s*'([^']+)'/m",
"/->trans\(\s*\"([^\"]+)\"/m",

// etrans('...')
"/\betrans\(\s*'([^']+)'/m",
"/\betrans\(\s*\"([^\"]+)\"/m",

// ->etrans('...')
"/->etrans\(\s*'([^']+)'/m",
"/->etrans\(\s*\"([^\"]+)\"/m",

// ->description('...') // ACL
"/->description\(\s*'([^']+)'/m",
"/->description\(\s*\"([^\"]+)\"/m",

// menuLabel = '...'
"/menuLabel\s*=\s*'([^']+)'/m",
"/menuLabel\s*=\s*\"([^\"]+)\"/m",
```

### Run Scanner

The message scanner can be executed through the console using the Scan Messages Command.  
See the [Scan Messages Command](#scan-messages-command) for details on how to run it and how to configure directories, output formats, and storage behavior.

## Console

### List Translations Command

If you have installed the [App Console](https://github.com/tobento-ch/app-console), you may list all available translations using the `translations:list` command.  
This is useful for inspecting which messages exist for each locale and verifying that your translation resources are loaded correctly.

**List all translations**

```
php ap translations:list --locale=de
```

**Available Options**

| Option | Description |
| ------ | ----------- |
| `--locale=code` | Filters translations by the specified locale. |

### Resources Command

You may inspect all registered translation resources using the `translations:resources` command.  
This helps you understand which resources are loaded, their priority, and how they contribute to the final translation set.

**List all translation resources**

```
php ap translations:resources
```

**Available Options**

| Option | Description |
| ------ | ----------- |
| `--locale=code` | Filters resources by the specified locale. |

### Scan Messages Command

You may scan your source code for translation messages using the `translations:scan` command.  
This is useful when creating a new app, customizing existing functionality, or adding new translation messages during development.

**Scan messages using the default scanner configuration**

```
php ap translations:scan
```

**Available Options**

| Option | Description |
| ------ | ----------- |
| `--dir=path` | Directory to scan (defaults to scanner configuration). |
| `--table` | Outputs the results as a formatted table instead of JSON. |
| `--output` | Stores the JSON output using the scanner's configured output path. |

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)