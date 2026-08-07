<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Translation\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Functions;
use Tobento\App\Translation\MessageScanner;
use Tobento\App\Translation\MessageScannerInterface;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Translation\Translator;
use Tobento\Service\Translation\FilesResources;
use Tobento\Service\Translation\Resources;
use Tobento\Service\Translation\Resource;
use Tobento\Service\Translation\Modifiers;
use Tobento\Service\Translation\Modifier\ParameterReplacer;
use Tobento\Service\Translation\Modifier\Pluralization;
use Tobento\Service\Translation\MissingTranslationHandlerInterface;
use Tobento\Service\Translation\MissingTranslationHandler;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Language\LanguagesInterface;

/**
 * Translation
 */
class Translation extends Boot
{
    public const INFO = [
        'boot' => [
            'implements '.TranslatorInterface::class,
            'adds trans app macro and function',
        ],
    ];
    
    public const BOOT = [
        Functions::class,
    ];

    /**
     * Boot application services.
     *
     * @param Functions $functions
     * @return void
     */
    public function boot(Functions $functions): void
    {
        // Add trans dir if not exists:
        if (! $this->app->dirs()->has('trans')) {
            $this->app->dirs()->dir(
                dir: $this->app->dir('app').'trans/',
                name: 'trans',
                group: 'trans',
                priority: 100,
            );
        }
        
        // Translator:
        $this->app->set(TranslatorInterface::class, function() {
            
            if ($this->app->has(MissingTranslationHandlerInterface::class)) {
                $missingTranslationHandler = $this->app->get(MissingTranslationHandlerInterface::class);
            } else {
                $missingTranslationHandler = new MissingTranslationHandler();
            }
            
            $translator = new Translator(
                resources: new FilesResources(
                    dirs: $this->app->dirs()->sort()->group('trans')
                ),
                modifiers: new Modifiers(
                    new Pluralization(),
                    new ParameterReplacer(),
                ),
                missingTranslationHandler: $missingTranslationHandler,
            );
            
            if (! $this->app->has(LanguagesInterface::class)) {
                return $translator;
            }
            
            $languages = $this->app->get(LanguagesInterface::class);
            
            $translator->setLocale($languages->current()->key());
            $translator->setLocaleFallbacks($languages->fallbacks('key'));
            
            return $translator;
        });
        
        $this->app->set(MessageScannerInterface::class, function(): MessageScannerInterface {
            return new MessageScanner(
                outputPath: $this->app->dir('root').'build/collected-messages.json'
            )
            ->withDefaultPatterns()
            ->withDirectory($this->app->dir('root').'src');
        });
        
        // App macros.
        $this->app->addMacro('trans', [$this, 'trans']);
        
        // Functions:
        $functions->register(__DIR__.'/../functions.php');
        
        // Console commands:
        $this->app->on(ConsoleInterface::class, static function(ConsoleInterface $console): void {
            $console->addCommand(\Tobento\App\Translation\Console\ListCommand::class);
            $console->addCommand(\Tobento\App\Translation\Console\ResourcesCommand::class);
            $console->addCommand(\Tobento\App\Translation\Console\ScanMessagesCommand::class);
        });
    }
    
    /**
     * Returns the translated message.
     *
     * @param string $message The message to translate.
     * @param array $parameters Any parameters for the message.
     * @param null|string $locale The locale or null to use the default.
     * @return string The translated message.
     */
    public function trans(string $message, array $parameters = [], null|string $locale = null): string
    {
        return $this->app->get(TranslatorInterface::class)->trans($message, $parameters, $locale);
    }
}