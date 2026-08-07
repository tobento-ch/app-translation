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
use Tobento\App\Translation\Console\ResourcesCommand;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Container\Container;
use Tobento\Service\Translation\MissingTranslationHandler;
use Tobento\Service\Translation\Modifiers;
use Tobento\Service\Translation\Resource;
use Tobento\Service\Translation\Resources;
use Tobento\Service\Translation\Translator;
use Tobento\Service\Translation\TranslatorInterface;

class ResourcesCommandTest extends TestCase
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

        // TranslatorInterface but NOT ResourcesAware
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

        new TestCommand(command: ResourcesCommand::class)
            ->expectsOutput('Translator does not support resources.')
            ->expectsExitCode(1)
            ->execute($container);
    }

    public function testListsAllResourcesForAllLocales()
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
            priority: 10,
        );

        $resourceDe = new Resource(
            locale: 'de',
            name: 'messages',
            group: 'foo',
            translations: [
                'hello' => 'Hallo',
                'world' => 'Welt',
            ],
            priority: 20,
        );

        $translator = $this->createTranslator([$resourceEn, $resourceDe]);

        $container->set(TranslatorInterface::class, $translator);

        new TestCommand(command: ResourcesCommand::class)
            ->expectsTable(
                headers: ['Name', 'Locale', 'Group', 'Priority', 'Filename', 'Translations'],
                rows: [
                    ['messages', 'en', 'foo', 10, '', 2],
                    ['messages', 'de', 'foo', 20, '', 2],
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
            translations: ['hello' => 'Hello'],
            priority: 10,
        );

        $resourceDe = new Resource(
            locale: 'de',
            name: 'messages',
            group: 'foo',
            translations: ['hello' => 'Hallo'],
            priority: 20,
        );

        $translator = $this->createTranslator([$resourceEn, $resourceDe]);

        $container->set(TranslatorInterface::class, $translator);

        new TestCommand(
            command: ResourcesCommand::class,
            input: [
                '--locale' => 'de',
            ],
        )
        ->expectsTable(
            headers: ['Name', 'Locale', 'Group', 'Priority', 'Filename', 'Translations'],
            rows: [
                ['messages', 'de', 'foo', 20, '', 1],
            ],
        )
        ->expectsExitCode(0)
        ->execute($container);
    }
}