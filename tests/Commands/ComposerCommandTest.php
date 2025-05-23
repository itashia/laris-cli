<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;
use Laris\Commands\ComposerCommand;

class ComposerCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        file_put_contents(getcwd() . '/artisan', "<?php // fake artisan");
    }

    public function tearDown(): void
    {
        @unlink(getcwd() . '/artisan');
    }

    public function test_command_fails_outside_laravel_project()
    {
        @unlink(getcwd() . '/artisan');

        $command = new ComposerCommand();
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('You are not in a Laravel project.', $commandTester->getDisplay());
    }

    public function test_command_quits_immediately()
    {
        $command = $this->getMockBuilder(ComposerCommand::class)
                        ->onlyMethods(['runProcess'])
                        ->getMock();

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['quit']);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Exiting Composer control.', $commandTester->getDisplay());
    }
}
