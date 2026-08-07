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

namespace Tobento\App\Translation\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Translation\MessageScanner;
use Tobento\App\Translation\MessageScannerInterface;

class MessageScannerTest extends TestCase
{
    public function testImplementsInterface()
    {
        $scanner = new MessageScanner(outputPath: 'messages.json');
        $this->assertInstanceOf(MessageScannerInterface::class, $scanner);
    }
    
    public function testConstructMethodDirectoriesGetsNormalized()
    {
        $scanner = new MessageScanner(
            outputPath: 'messages.json',
            directories: [
                'app/', 'src', 'foo/baz/',
            ],
        );

        $this->assertSame(['app', 'src', 'foo/baz'], $scanner->getDirectories());
    }

    public function testWithPatternMethod()
    {
        $scanner = new MessageScanner('messages.json');
        $new = $scanner->withPattern('/foo/');

        $this->assertNotSame($scanner, $new);
        $this->assertSame([], $scanner->getPatterns());
        $this->assertSame(['/foo/'], $new->getPatterns());
    }

    public function testWithPatternsMethod()
    {
        $scanner = new MessageScanner('messages.json');
        $new = $scanner->withPatterns(['/foo/', '/bar/']);

        $this->assertNotSame($scanner, $new);
        $this->assertSame([], $scanner->getPatterns());
        $this->assertSame(['/foo/', '/bar/'], $new->getPatterns());
    }

    public function testWithDirectoryMethod()
    {
        $scanner = new MessageScanner('messages.json');
        $new = $scanner->withDirectory('app');

        $this->assertNotSame($scanner, $new);
        $this->assertSame([], $scanner->getDirectories());
        $this->assertSame(['app'], $new->getDirectories());
    }

    public function testWithDirectoriesMethod()
    {
        $scanner = new MessageScanner('messages.json');
        $new = $scanner->withDirectories(['app', 'src']);

        $this->assertNotSame($scanner, $new);
        $this->assertSame([], $scanner->getDirectories());
        $this->assertSame(['app', 'src'], $new->getDirectories());
    }

    public function testWithOutputPathMethod()
    {
        $scanner = new MessageScanner('messages.json');
        $new = $scanner->withOutputPath('new.json');

        $this->assertNotSame($scanner, $new);
        $this->assertSame('messages.json', $scanner->getStoreOutputPath());
        $this->assertSame('new.json', $new->getStoreOutputPath());
    }

    public function testScanFindsMessages()
    {
        $tmpDir = sys_get_temp_dir() . '/scanner-test-' . uniqid();
        mkdir($tmpDir);

        $sub = $tmpDir . '/sub';
        mkdir($sub);

        $file = $sub . '/example.php';
        file_put_contents($file, "<?php trans('hello'); trans(\"world\");");

        $scanner = (new MessageScanner('messages.json'))
            ->withDirectory($tmpDir)
            ->withPatterns([
                "/\btrans\(\s*'([^']+)'/m",
                "/\btrans\(\s*\"([^\"]+)\"/m",
            ]);

        $messages = $scanner->scan();

        $this->assertSame([
            'hello' => 'hello',
            'world' => 'world',
        ], $messages);

        unlink($file);
        rmdir($sub);
        rmdir($tmpDir);
    }

    public function testStoreOutputToMethodWritesFile()
    {
        $tmpFile = sys_get_temp_dir() . '/scanner-output-' . uniqid() . '.json';

        $scanner = new MessageScanner($tmpFile);

        $messages = [
            'hello' => 'hello',
            'world' => 'world',
        ];

        $scanner->storeOutputTo($messages);

        $this->assertFileExists($tmpFile);

        $json = json_decode(file_get_contents($tmpFile), true);

        $this->assertSame($messages, $json);

        unlink($tmpFile);
    }
}