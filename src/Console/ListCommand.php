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
use Tobento\Service\Translation\ResourcesAware;
use Tobento\Service\Translation\LocaleAware;
use Tobento\Service\Translation\ResourceInterface;
use Tobento\Service\Translation\ResourceFile;

class ListCommand extends AbstractCommand
{
    public const SIGNATURE = '
        translations:list | List all translation messages and their translations.
        {--locale= : Only list translations for this locale.}
    ';

    public function handle(
        InteractorInterface $io,
        TranslatorInterface $translator,
    ): int {

        if (! $translator instanceof ResourcesAware) {
            $io->error('Translator does not support resources.');
            return static::FAILURE;
        }
        
        // Determine locales to load (similar to AllTranslations collector)
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
        
        // Load resources for the locales (lazy loading)
        $resources = $translator->resources()->locales($locales);

        $rows = [];

        foreach ($resources->all() as $resource) {
            $translations = $resource->translations();

            $resourceFilename = $resource instanceof ResourceFile
                ? $resource->file()->getBasename()
                : '';

            foreach ($translations as $message => $translated) {
                $rows[] = [
                    $message,
                    $translated,
                    $resource->locale(),
                    $resource->name(),
                    $resource->group() ?: '',
                    $resourceFilename,
                ];
            }
        }

        $io->table(
            headers: ['Message', 'Translation', 'Locale', 'Resource', 'Group', 'Filename'],
            rows: $rows,
        );

        return static::SUCCESS;
    }
}