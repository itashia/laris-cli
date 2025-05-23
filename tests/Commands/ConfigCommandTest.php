<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Laris\Commands\ConfigCommand;

class ConfigCommandTest extends TestCase
{
    protected string $configPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Point to test directory for config
        $this->configPath = getcwd() . '/.larisconfig.json';

        // Ensure clean environment
        if (file_exists($this->configPath)) {
            unlink($this->configPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function test_set_command_creates_config_file_and_saves_value()
    {
        $command = new ConfigCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'set',
            'key' => 'app.name',
            'value' => '"Laris CLI"',
        ]);

        $this->assertFileExists($this->configPath);
        $content = json_decode(file_get_contents($this->configPath), true);
        $this->assertEquals('Laris CLI', $content['app.name']);

        $this->assertStringContainsString("Key 'app.name' set to: \"Laris CLI\"", $tester->getDisplay());
    }

    public function test_get_command_retrieves_existing_value()
    {
        file_put_contents($this->configPath, json_encode(['env' => 'local']));

        $command = new ConfigCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'get',
            'key' => 'env',
        ]);

        $this->assertStringContainsString('env = "local"', $tester->getDisplay());
    }

    public function test_get_command_for_unknown_key()
    {
        $command = new ConfigCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'get',
            'key' => 'missing_key',
        ]);

        $this->assertStringContainsString("Key 'missing_key' not found", $tester->getDisplay());
    }

    public function test_list_command_shows_all_keys()
    {
        file_put_contents($this->configPath, json_encode([
            'a' => '1',
            'b' => [1, 2],
        ]));

        $command = new ConfigCommand();
        $tester = new CommandTester($command);

        $tester->execute(['action' => 'list']);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('a = "1"', $display);
        $this->assertStringContainsString('b = [1,2]', $display);
    }

    public function test_remove_command_deletes_key()
    {
        file_put_contents($this->configPath, json_encode([
            'to_delete' => 'value',
            'remain' => 123,
        ]));

        $command = new ConfigCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            'action' => 'remove',
            'key' => 'to_delete',
        ]);

        $content = json_decode(file_get_contents($this->configPath), true);
        $this->assertArrayNotHasKey('to_delete', $content);
        $this->assertArrayHasKey('remain', $content);

        $this->assertStringContainsString("Key 'to_delete' removed", $tester->getDisplay());
    }

    public function test_invalid_action_returns_failure()
    {
        $command = new ConfigCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'action' => 'unknown',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString("Unknown action 'unknown'", $tester->getDisplay());
    }
}
