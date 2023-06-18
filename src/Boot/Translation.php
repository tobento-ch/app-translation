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
use Tobento\Service\Language\LanguagesInterface;

/**
 * Translation
 */
class Translation extends Boot
{
    public const INFO = [
        'boot' => [
            'implements '.TranslatorInterface::class,
            'adds trans app macro',
        ],
    ];

    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot(): void
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
            
            $translator->setLocale($languages->current()->locale());
            $translator->setLocaleFallbacks($languages->fallbacks('locale'));
            
            return $translator;
        });
        
        // App macros.
        $this->app->addMacro('trans', [$this, 'trans']);
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