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
 
namespace Tobento\App\Translation\Console;

use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Translation\ResourceInterface;
use Tobento\Service\Translation\ResourceFile;
use Tobento\Service\Translation\LocaleAware;
use Tobento\Service\Translation\ResourcesAware;

class ResourcesCommand extends AbstractCommand
{
    public const SIGNATURE = '
        translations:resources | List all translation resources loaded by the Translator.
        {--locale= : Only load resources for this locale.}
    ';

    public function handle(
        InteractorInterface $io,
        TranslatorInterface $translator,
    ): int {

        if (! $translator instanceof ResourcesAware) {
            $io->error('Translator does not support resources.');
            return static::FAILURE;
        }

        // Determine locales to load (same logic as Collector)
        $localeOption = $io->option('locale');
        $locales = [];

        if ($localeOption) {
            $locales = [$localeOption];
        } elseif ($translator instanceof LocaleAware) {
            $locales = array_unique([
                $translator->getLocale(),
                ...array_values($translator->getLocaleFallbacks()),
            ]);
        }

        // Trigger lazy loading
        $resources = $translator->resources()->locales($locales);

        $rows = [];

        foreach ($resources->all() as $resource) {
            $rows[] = [
                $resource->name(),
                $resource->locale(),
                $resource->group() ?: '',
                $resource->priority(),
                $resource instanceof ResourceFile
                    ? $resource->file()->getBasename()
                    : '',
                count($resource->translations()),
            ];
        }

        $io->table(
            headers: ['Name', 'Locale', 'Group', 'Priority', 'Filename', 'Translations'],
            rows: $rows,
        );

        return static::SUCCESS;
    }
}