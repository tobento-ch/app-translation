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

use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Dir\DirsInterface;

class TranslationFilesMigration implements MigrationInterface
{
    protected array $files;
    
    /**
     * Create a new TranslationFilesMigration.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $transDir = realpath(__DIR__.'/../').'/trans/';
        
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