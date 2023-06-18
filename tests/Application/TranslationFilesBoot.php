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

namespace Tobento\App\Translation\Test\Application;

use Tobento\App\Boot;
use Tobento\App\Migration\Boot\Migration;

class TranslationFilesBoot extends Boot
{
    public const BOOT = [
        // you may ensure the migration boot.
        Migration::class,
    ];
    
    public function boot(Migration $migration)
    {
        // Install migrations
        $migration->install(TranslationFilesMigration::class);
    }
}