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

namespace Tobento\App\Translation\Test\Console;

use PHPUnit\Framework\TestCase;
use Tobento\App\Translation\Console\ListCommand;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Container\Container;
use Tobento\Service\Translation\MissingTranslationHandler;
use Tobento\Service\Translation\Modifiers;
use Tobento\Service\Translation\LocaleAware;
use Tobento\Service\Translation\ResourcesAware;
use Tobento\Service\Translation\Resource;
use Tobento\Service\Translation\Resources;
use Tobento\Service\Translation\Translator;
use Tobento\Service\Translation\TranslatorInterface;

class ListCommandTest extends TestCase
{
    protected function createTranslator(array $resources): TranslatorInterface
    {
        return new Translator(
            resources: new Resources(...$resources),
            modifiers: new Modifiers(),
            missingTranslationHandler: new MissingTranslationHandler(),
            locale: 'en',
            localeFallbacks: ['de' => 'en', 'en' => 'de'],
            localeMapping: [],
        );
    }

    public function testTranslatorNotResourcesAware()
    {
        $container = new Container();

        // Use a translator that is NOT ResourcesAware
        // TranslatorInterface is implemented, but resources() is missing.
        $translator = new class implements TranslatorInterface {
            public function trans(string $message, array $parameters = [], ?string $locale = null): string
            {
                return $message;
            }
            public function getLocale(): string { return 'en'; }
            public function setLocale(string $locale): static { return $this; }
            public function getLocaleFallbacks(): array { return []; }
        };

        $container->set(TranslatorInterface::class, $translator);

        new TestCommand(command: ListCommand::class)
            ->expectsOutput('Translator does not support resources.')
            ->expectsExitCode(1)
            ->execute($container);
    }

    public function testListsAllTranslationsForAllLocales()
    {
        $container = new Container();

        $resourceEn = new Resource(
            locale: 'en',
            name: 'messages',
            group: 'foo',
            translations: [
                'hello' => 'Hello',
                'world' => 'World',
            ],
        );

        $resourceDe = new Resource(
            locale: 'de',
            name: 'messages',
            group: 'foo',
            translations: [
                'hello' => 'Hallo',
                'world' => 'Welt',
            ],
        );

        $translator = $this->createTranslator([$resourceEn, $resourceDe]);

        $container->set(TranslatorInterface::class, $translator);

        new TestCommand(command: ListCommand::class)
            ->expectsTable(
                headers: ['Message', 'Translation', 'Locale', 'Resource', 'Group', 'Filename'],
                rows: [
                    ['hello', 'Hello', 'en', 'messages', 'foo', ''],
                    ['world', 'World', 'en', 'messages', 'foo', ''],
                    ['hello', 'Hallo', 'de', 'messages', 'foo', ''],
                    ['world', 'Welt', 'de', 'messages', 'foo', ''],
                ],
            )
            ->expectsExitCode(0)
            ->execute($container);
    }

    public function testListsOnlySpecificLocale()
    {
        $container = new Container();

        $resourceEn = new Resource(
            locale: 'en',
            name: 'messages',
            group: 'foo',
            translations: [
                'hello' => 'Hello',
            ],
        );

        $resourceDe = new Resource(
            locale: 'de',
            name: 'messages',
            group: 'foo',
            translations: [
                'hello' => 'Hallo',
            ],
        );

        $translator = $this->createTranslator([$resourceEn, $resourceDe]);

        $container->set(TranslatorInterface::class, $translator);

        new TestCommand(
            command: ListCommand::class,
            input: [
                '--locale' => 'de',
            ],
        )
        ->expectsTable(
            headers: ['Message', 'Translation', 'Locale', 'Resource', 'Group', 'Filename'],
            rows: [
                ['hello', 'Hallo', 'de', 'messages', 'foo', ''],
            ],
        )
        ->expectsExitCode(0)
        ->execute($container);
    }
}