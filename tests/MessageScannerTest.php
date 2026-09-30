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
use Tobento\Service\FileSystem\Dir;

class MessageScannerTest extends TestCase
{
    private function scanCode(string $code): array
    {
        $scanDir = sys_get_temp_dir() . '/scanner-test-' . uniqid();
        new Dir()->create($scanDir);

        file_put_contents($scanDir . '/example.php', $code);

        try {
            return new MessageScanner(outputPath: $scanDir . '/messages.json')
                ->withDirectory($scanDir)
                ->withDefaultPatterns()
                ->scan();
        } finally {
            new Dir()->delete($scanDir);
        }
    }
    
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
    
    public function testWithMessageMethod()
    {
        $scanner = new MessageScanner('messages.json');
        $new = $scanner->withMessage('foo');

        $this->assertNotSame($scanner, $new);
        $this->assertSame([], $scanner->getMessages());
        $this->assertSame(['foo'], $new->getMessages());
    }
    
    public function testWithMessagesMethod()
    {
        $scanner = new MessageScanner('messages.json');
        $new = $scanner->withMessages(['foo', 'bar']);

        $this->assertNotSame($scanner, $new);
        $this->assertSame([], $scanner->getMessages());
        $this->assertSame(['foo', 'bar'], $new->getMessages());
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

        $scanner = new MessageScanner('messages.json')
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

    public function testScanIncludesAddedMessages()
    {
        $scanner = new MessageScanner('messages.json')
            ->withMessage('foo')
            ->withMessage('bar');

        $this->assertSame([
            'bar' => 'bar',
            'foo' => 'foo',
        ], $scanner->scan());
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
    
    public function testWithDefaultPatternsMethod()
    {
        $scanner = new MessageScanner('messages.json');
        $new = $scanner->withDefaultPatterns();

        $this->assertNotSame($scanner, $new);
        $this->assertSame([], $scanner->getPatterns());
        $this->assertNotEmpty($new->getPatterns());
    }

    public function testWithDefaultPatternsScansAllSupportedSyntaxes()
    {
        $code = <<<'PHP'
        <?php
        trans('trans single');
        trans("trans double");
        $t->trans('arrow trans single');
        $t->trans("arrow trans double");
        etrans('etrans single');
        etrans("etrans double");
        $t->etrans('arrow etrans single');
        $t->etrans("arrow etrans double");
        $acl->description('description single');
        $acl->description("description double");
        protected string $menuLabel = 'menu single';
        protected string $menuLabel = "menu double";
        PHP;
        
        $messages = $this->scanCode($code);

        $expected = [
            'trans single', 'trans double',
            'arrow trans single', 'arrow trans double',
            'etrans single', 'etrans double',
            'arrow etrans single', 'arrow etrans double',
            'description single', 'description double',
            'menu single', 'menu double',
        ];

        $this->assertEqualsCanonicalizing($expected, array_keys($messages));
        $this->assertSame('trans single', $messages['trans single']);
    }

    public function testWithDefaultPatternsScansHttpExceptionMessage()
    {
        $code = <<<'PHP'
        <?php
        throw new HttpException(code: 404, message: 'Page not found');
        throw new HttpException(code: 403, message: "Access denied");
        throw new HttpException(message: 'Message first');
        PHP;

        $messages = $this->scanCode($code);

        $this->assertEqualsCanonicalizing(
            ['Page not found', 'Access denied', 'Message first'],
            array_keys($messages)
        );
    }

    public function testWithDefaultPatternsIgnoresHttpExceptionWithoutMessage()
    {
        $code = <<<'PHP'
        <?php
        throw new HttpException(code: 404);
        throw new HttpException(404, 'Positional message');
        PHP;

        $this->assertSame([], $this->scanCode($code));
    }
}