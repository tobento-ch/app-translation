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

use Tobento\App\AppInterface;
use Tobento\App\Translation\MessageScannerInterface;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;
use Tobento\Service\Filesystem\Dir;

class ScanMessagesCommand extends AbstractCommand
{
    public const SIGNATURE = '
        translations:scan | Scan source code for translation messages.
        {--dir= : Directory to scan (default: scanner config).}
        {--table : Output results as a table instead of JSON.}
        {--output : Store the JSON output using the scanner’s configured output path.}
    ';

    public function handle(
        InteractorInterface $io,
        AppInterface $app,
        MessageScannerInterface $scanner,
    ): int {

        // If --dir is provided, override directories
        if ($dirOption = $io->option('dir')) {
            $scanDir = $app->dir('root').trim($dirOption, '/');
            $scanner = $scanner->withDirectories([$scanDir]);
        }

        // Scan messages
        $messages = $scanner->scan();

        // Store output to file if requested
        if ($io->option('output')) {
            $scanner->storeOutputTo($messages);
            $io->write("Stored output to {$scanner->getStoreOutputPath()}");
            return static::SUCCESS;
        }

        // JSON output (default)
        if (! $io->option('table')) {
            $io->write(json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return static::SUCCESS;
        }

        // Table output
        $rows = [];
        foreach ($messages as $msg) {
            $rows[] = [$msg];
        }

        $io->table(
            headers: ['Message'],
            rows: $rows,
        );

        return static::SUCCESS;
    }
}