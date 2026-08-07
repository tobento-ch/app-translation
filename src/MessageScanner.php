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

use Tobento\Service\Filesystem\Dir;

class MessageScanner implements MessageScannerInterface
{
    /**
     * Create a new instance.
     *
     * @param string $outputPath
     * @param array<array-key, string> $patterns
     * @param array $directories
     */
    public function __construct(
        protected string $outputPath,
        protected array $patterns = [],
        protected array $directories = [],
    ) {
        $this->directories = array_map(
            fn(string $d) => rtrim($d, '/'),
            $directories
        );
    }
    
    /**
     * Add default patterns.
     *
     * @return static
     */
    public function withDefaultPatterns(): static
    {
        return $this->withPatterns([
            // trans('...')
            "/\btrans\(\s*'([^']+)'/m",
            "/\btrans\(\s*\"([^\"]+)\"/m",

            // ->trans('...')
            "/->trans\(\s*'([^']+)'/m",
            "/->trans\(\s*\"([^\"]+)\"/m",

            // etrans('...')
            "/\betrans\(\s*'([^']+)'/m",
            "/\betrans\(\s*\"([^\"]+)\"/m",

            // ->etrans('...')
            "/->etrans\(\s*'([^']+)'/m",
            "/->etrans\(\s*\"([^\"]+)\"/m",

            // ->description('...') // ACL
            "/->description\(\s*'([^']+)'/m",
            "/->description\(\s*\"([^\"]+)\"/m",

            // menuLabel = '...'
            "/menuLabel\s*=\s*'([^']+)'/m",
            "/menuLabel\s*=\s*\"([^\"]+)\"/m",
        ]);
    }
    
    /**
     * Add a pattern to scan for.
     *
     * @param string $pattern
     * @return static
     */
    public function withPattern(string $pattern): static
    {
        $clone = clone $this;
        $clone->patterns[] = $pattern;
        return $clone;
    }

    /**
     * Replace all patterns to scan for.
     *
     * @param array<array-key, string> $patterns
     * @return static
     */
    public function withPatterns(array $patterns): static
    {
        $clone = clone $this;
        $clone->patterns = $patterns;
        return $clone;
    }

    /**
     * Add a directory to scan.
     *
     * @param string $dir
     * @return static
     */
    public function withDirectory(string $dir): static
    {
        $clone = clone $this;
        $clone->directories[] = rtrim($dir, '/');
        return $clone;
    }

    /**
     * Replace all directories to scan.
     *
     * @param array<array-key, string> $dirs
     * @return static
     */
    public function withDirectories(array $dirs): static
    {
        $clone = clone $this;
        $clone->directories = array_map(
            fn(string $d) => rtrim($d, '/'),
            $dirs
        );
        return $clone;
    }

    /**
     * Set the output file path.
     *
     * @param string $path
     * @return static
     */
    public function withOutputPath(string $path): static
    {
        $clone = clone $this;
        $clone->outputPath = $path;
        return $clone;
    }

    /**
     * Get directories to scan.
     *
     * @return array<array-key, string>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * Get patterns to scan for.
     *
     * @return array<array-key, string>
     */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    /**
     * Scan and return messages.
     *
     * Returned array is keyed by message string, with identical values:
     * ['welcome' => 'welcome', ...]
     *
     * @return array<string, string>
     */
    public function scan(): array
    {
        $messages = [];
        $dir = new Dir();

        foreach ($this->directories as $directory) {
            foreach ($dir->getFoldersAll($directory) as $folder) {
                foreach ($dir->getFiles($folder->dir()) as $file) {
                    $content = $file->getContent();

                    foreach ($this->patterns as $pattern) {
                        if (preg_match_all($pattern, $content, $matches)) {
                            foreach ($matches[1] as $match) {
                                $messages[$match] = $match;
                            }
                        }
                    }
                }
            }
        }

        ksort($messages, SORT_NATURAL | SORT_FLAG_CASE);
        return $messages;
    }

    /**
     * Store messages to the configured output path.
     *
     * @param array<string, string> $messages
     * @return void
     */
    public function storeOutputTo(array $messages): void
    {
        $json = json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->outputPath, $json);
    }

    /**
     * Get the output file path.
     *
     * @return string
     */
    public function getStoreOutputPath(): string
    {
        return $this->outputPath;
    }
}