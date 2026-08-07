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
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Translation\Console\ScanMessagesCommand;
use Tobento\App\Translation\MessageScanner;
use Tobento\App\Translation\MessageScannerInterface;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Container\Container;
use Tobento\Service\Filesystem\Dir;

class ScanMessagesCommandTest extends TestCase
{
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            new Dir()->delete(__DIR__.'/../app/');
        }

        new Dir()->create(__DIR__.'/../app/');
        new Dir()->create(__DIR__.'/../app/config/');

        $app = new AppFactory()->createApp();

        $app->dirs()
            ->dir(realpath(__DIR__.'/../app/'), 'app')
            ->dir(realpath(__DIR__.'/../'), 'root')
            ->dir($app->dir('app').'config', 'config', group: 'config');

        return $app;
    }

    public static function tearDownAfterClass(): void
    {
        new Dir()->delete(__DIR__.'/../app/');
    }

    protected function createScanner(string $outputPath, array $dirs = [], array $patterns = []): MessageScannerInterface
    {
        return new MessageScanner(
            outputPath: $outputPath,
            patterns: $patterns,
            directories: $dirs,
        );
    }

    public function testScanDefaultJsonOutput()
    {
        $app = $this->createApp();

        // Create directory inside root
        $scanDir = $app->dir('root').'scan-src';
        new Dir()->create($scanDir);

        file_put_contents($scanDir.'/example.php', "<?php trans('hello'); trans(\"world\");");

        $scanner = $this->createScanner(
            outputPath: $app->dir('root').'messages.json',
            dirs: [$scanDir],
            patterns: [
                "/\btrans\(\s*'([^']+)'/m",
                "/\btrans\(\s*\"([^\"]+)\"/m",
            ],
        );

        $container = new Container();
        $container->set(AppInterface::class, $app);
        $container->set(MessageScannerInterface::class, $scanner);

        new TestCommand(command: ScanMessagesCommand::class)
            ->expectsOutputToContain('"hello"')
            ->expectsOutputToContain('"world"')
            ->expectsExitCode(0)
            ->execute($container);

        new Dir()->delete($scanDir);
    }

    public function testScanWithDirOptionOverridesScannerDirectories()
    {
        $app = $this->createApp();

        $overrideDir = $app->dir('root').'override';
        new Dir()->create($overrideDir);

        file_put_contents($overrideDir.'/example.php', "<?php trans('foo');");

        // Scanner initially points to wrong directory
        $scanner = $this->createScanner(
            outputPath: $app->dir('root').'messages.json',
            dirs: [$app->dir('root').'does-not-exist'],
            patterns: ["/\btrans\(\s*'([^']+)'/m"],
        );

        $container = new Container();
        $container->set(AppInterface::class, $app);
        $container->set(MessageScannerInterface::class, $scanner);

        new TestCommand(
            command: ScanMessagesCommand::class,
            input: [
                '--dir' => 'override',
            ],
        )
        ->expectsOutputToContain('"foo"')
        ->expectsExitCode(0)
        ->execute($container);

        new Dir()->delete($overrideDir);
    }

    public function testScanTableOutput()
    {
        $app = $this->createApp();

        $scanDir = $app->dir('root').'scan-src';
        new Dir()->create($scanDir);

        file_put_contents($scanDir.'/example.php', "<?php trans('hello');");

        $scanner = $this->createScanner(
            outputPath: $app->dir('root').'messages.json',
            dirs: [$scanDir],
            patterns: ["/\btrans\(\s*'([^']+)'/m"],
        );

        $container = new Container();
        $container->set(AppInterface::class, $app);
        $container->set(MessageScannerInterface::class, $scanner);

        new TestCommand(
            command: ScanMessagesCommand::class,
            input: [
                '--table' => null,
            ],
        )
        ->expectsTable(
            headers: ['Message'],
            rows: [
                ['hello'],
            ],
        )
        ->expectsExitCode(0)
        ->execute($container);

        new Dir()->delete($scanDir);
    }

    public function testScanStoresOutputToFile()
    {
        $app = $this->createApp();

        $scanDir = $app->dir('root').'scan-src';
        new Dir()->create($scanDir);

        file_put_contents($scanDir.'/example.php', "<?php trans('hello');");

        $outputFile = $app->dir('root').'messages.json';

        $scanner = $this->createScanner(
            outputPath: $outputFile,
            dirs: [$scanDir],
            patterns: ["/\btrans\(\s*'([^']+)'/m"],
        );

        $container = new Container();
        $container->set(AppInterface::class, $app);
        $container->set(MessageScannerInterface::class, $scanner);

        new TestCommand(
            command: ScanMessagesCommand::class,
            input: [
                '--output' => null,
            ],
        )
        ->expectsOutputToContain("Stored output to {$outputFile}")
        ->expectsExitCode(0)
        ->execute($container);

        $this->assertFileExists($outputFile);

        $json = json_decode(file_get_contents($outputFile), true);
        $this->assertSame(['hello' => 'hello'], $json);

        unlink($outputFile);
        new Dir()->delete($scanDir);
    }
}