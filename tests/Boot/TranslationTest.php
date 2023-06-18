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

namespace Tobento\App\Translation\Test\Boot;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Translation\Boot\Translation;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Translation\Resource;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Language\LanguageFactory;
use Tobento\Service\Language\Languages;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\App\Translation\Test\Application\TranslationFilesBoot;

/**
 * TranslationTest
 */
class AppTest extends TestCase
{
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            (new Dir())->delete(__DIR__.'/../app/');
        }
        
        (new Dir())->create(__DIR__.'/../app/');
        (new Dir())->create(__DIR__.'/../app/config/');
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../app/'), 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config');
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testTranslatorIsAvailable()
    {
        $app = $this->createApp();
        $app->boot(Translation::class);
        $app->booting();
        
        $this->assertInstanceof(TranslatorInterface::class, $app->get(TranslatorInterface::class));
    }
    
    public function testTranslateMessage()
    {
        $app = $this->createApp();
        $app->boot(Translation::class);
        $app->booting();
        
        $translator = $app->get(TranslatorInterface::class);

        $translated = $translator->trans(
            message: 'Hi :name',
            parameters: [':name' => 'John'],
            locale: 'de'
        );
        
        $this->assertSame('Hi John', $translated);
    }
    
    public function testTranslateMessageWithAppMacro()
    {
        $app = $this->createApp();
        $app->boot(Translation::class);
        $app->booting();

        $translated = $app->trans(
            message: 'Hi :name',
            parameters: [':name' => 'John'],
            locale: 'de'
        );
        
        $this->assertSame('Hi John', $translated);
    }
    
    public function testTranslateMessageWithTranslationBoot()
    {
        $app = $this->createApp();
        $app->boot(Translation::class);
        $app->booting();
        
        $translation = $app->get(Translation::class);
        
        $translated = $translation->trans(
            message: 'Hi :name',
            parameters: [':name' => 'John'],
            locale: 'de'
        );
        
        $this->assertSame('Hi John', $translated);
    }

    public function testConfigureLocaleManually()
    {
        $app = $this->createApp();
        $app->boot(Translation::class);
        $app->boot(TranslationFilesBoot::class);
        $app->booting();
        
        $translator = $app->get(TranslatorInterface::class);
        
        $this->assertSame('about', $translator->trans(message: 'about'));
        $this->assertSame('about', $translator->trans(message: 'about', locale: 'fr'));
        $this->assertSame('about', $translator->trans(message: 'about', locale: 'de-CH'));
        
        $translator->setLocale('de');
        $translator->setLocaleFallbacks(['fr' => 'de']);
        $translator->setLocaleMapping(['de-CH' => 'de']);
        
        $this->assertSame('über uns', $translator->trans(message: 'about'));
        $this->assertSame('über uns', $translator->trans(message: 'about', locale: 'fr'));
        $this->assertSame('über uns', $translator->trans(message: 'about', locale: 'de-CH'));
    }
    
    public function testConfigureLocaleByLanguages()
    {
        $app = $this->createApp();
        $app->boot(Translation::class);
        $app->boot(TranslationFilesBoot::class);
        $app->booting();
        
        $app->set(LanguagesInterface::class, function() {
            $languageFactory = new LanguageFactory();
            return new Languages(
                $languageFactory->createLanguage('en'),
                $languageFactory->createLanguage('de', default: true, fallback: 'en'),
                $languageFactory->createLanguage('fr', fallback: 'en'),
            );
        });
        
        $translator = $app->get(TranslatorInterface::class);
        
        $this->assertSame('de', $translator->getLocale());
        $this->assertSame(['de' => 'en', 'fr' => 'en'], $translator->getLocaleFallbacks());
        
        $this->assertSame('über uns', $translator->trans(message: 'about'));
        $this->assertSame('about', $translator->trans(message: 'about', locale: 'fr'));
    }
    
    public function testMigrateTranslation()
    {
        $app = $this->createApp();
        $app->boot(Translation::class);
        $app->boot(TranslationFilesBoot::class);
        $app->booting();
        
        $translation = $app->get(Translation::class);
        
        $translated = $translation->trans(
            message: 'about',
            locale: 'de'
        );
        
        $this->assertSame('über uns', $translated);
    }
    
    public function testAddTranslations()
    {
        $app = $this->createApp();
        $app->boot(Translation::class);
        $app->boot(TranslationFilesBoot::class);
        $app->booting();
        
        $app->on(TranslatorInterface::class, function(TranslatorInterface $translator) {
            $translator->resources()->add(new Resource('shop', 'de', [
                'Checkout' => 'Kasse',
            ]));
        });
        
        $translator = $app->get(TranslatorInterface::class);
        
        $this->assertSame(
            'Kasse',
            $translator->trans(message: 'Checkout', parameters: ['src' => 'shop'], locale: 'de')
        );        
    }
    
    public function testCustomizeTranslations()
    {
        $app = $this->createApp();
        $app->boot(Translation::class);
        $app->boot(TranslationFilesBoot::class);
        $app->booting();
        
        $app->on(TranslatorInterface::class, function(TranslatorInterface $translator) {
            $translator->resources()->add(new Resource(
                name: 'shop',
                locale: 'de',
                translations: ['cart' => 'WARENKORB'],
                priority: 200,
            ));
        });
        
        $translator = $app->get(TranslatorInterface::class);
        
        $this->assertSame(
            'WARENKORB',
            $translator->trans(message: 'cart', parameters: ['src' => 'shop'], locale: 'de')
        );
    }
    
    public function testCustomizeTranslationsWithHigherTransDir()
    {
        $app = $this->createApp();
        
        $app->dirs()->dir(
            dir: realpath(__DIR__.'/../').'/trans-custom/',
            name: 'trans.custom',
            group: 'trans',
            priority: 300,
        );
        
        $app->boot(Translation::class);
        $app->boot(TranslationFilesBoot::class);
        $app->booting();
        
        $translator = $app->get(TranslatorInterface::class);

        $this->assertSame(
            'warenkorb custom',
            $translator->trans(message: 'cart', parameters: ['src' => 'shop'], locale: 'de')
        );
    }
}