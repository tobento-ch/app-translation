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
 
namespace Tobento\App\Translation;

/**
 * Message scanner.
 */
interface MessageScannerInterface
{
    /**
     * Add a pattern to scan for.
     *
     * @param string $pattern
     * @return static
     */
    public function withPattern(string $pattern): static;

    /**
     * Replace all patterns to scan for.
     *
     * @param array<array-key, string> $patterns
     * @return static
     */
    public function withPatterns(array $patterns): static;
    
    /**
     * Add a directory to scan.
     *
     * @param string $dir
     * @return static
     */
    public function withDirectory(string $dir): static;

    /**
     * Replace all directories to scan.
     *
     * @param array<array-key, string> $dirs
     * @return static
     */
    public function withDirectories(array $dirs): static;

    /**
     * Set the output file path.
     *
     * @param string $path
     * @return static
     */
    public function withOutputPath(string $path): static;

    /**
     * Get directories to scan.
     *
     * @return array<array-key, string>
     */
    public function getDirectories(): array;

    /**
     * Get patterns to scan for.
     *
     * @return array<array-key, string>
     */
    public function getPatterns(): array;

    /**
     * Scan and return messages.
     *
     * Returned array is keyed by message string, with identical values:
     * ['welcome' => 'welcome', ...]
     *
     * @return array<string, string>
     */
    public function scan(): array;

    /**
     * Store messages to the configured output path.
     *
     * @param array<string, string> $messages
     * @return void
     */
    public function storeOutputTo(array $messages): void;

    /**
     * Get the output file path.
     *
     * @return string
     */
    public function getStoreOutputPath(): string;
}