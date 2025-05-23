<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Laris\Commands\BackupCommand;
use Symfony\Component\Console\Application;

class BackupCommandTest extends TestCase
{
    private string $testProjectDir;

    protected function setUp(): void
    {
        $this->testProjectDir = sys_get_temp_dir() . '/laris_test_project_' . uniqid();
        mkdir($this->testProjectDir, 0777, true);
        file_put_contents($this->testProjectDir . '/test.txt', 'Hello Laris!');
        mkdir($this->testProjectDir . '/vendor');
        mkdir($this->testProjectDir . '/node_modules');
        mkdir($this->testProjectDir . '/.git');
        file_put_contents($this->testProjectDir . '/.env', 'SECRET=123');
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->testProjectDir);
    }

    protected function deleteDir($dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = "$dir/$item";
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testBackupCommandCreatesZipExcludingFiles()
    {
        chdir($this->testProjectDir);

        $application = new Application();
        $application->add(new BackupCommand());

        $command = $application->find('laris:backup');
        $tester = new CommandTester($command);

        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Backup created successfully', $output);

        $backupFiles = glob($this->testProjectDir . '/backups/*.zip');
        $this->assertNotEmpty($backupFiles, 'No backup file created.');

        // بررسی این که فایل‌های exclude نشده در آرشیو هستن، و فایل‌های exclude نشده نیستن
        $zip = new ZipArchive();
        $zip->open($backupFiles[0]);

        $this->assertNotFalse($zip->locateName('test.txt'), 'Expected file not found in zip.');
        $this->assertFalse($zip->locateName('.env'), '.env file should be excluded from zip.');
        $this->assertFalse($zip->locateName('vendor/'), 'vendor directory should be excluded from zip.');
        $this->assertFalse($zip->locateName('node_modules/'), 'node_modules directory should be excluded from zip.');

        $zip->close();
    }
}
