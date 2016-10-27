<?php

namespace Illuminated\Helpers\Artisan;

use Mockery;
use PHPUnit_Framework_Error;
use TestCase;

class CommandTest extends TestCase
{
    public static $functions;

    protected function setUp()
    {
        self::$functions = Mockery::mock();

        $phpBinaryMock = Mockery::mock('overload:Symfony\Component\Process\PhpExecutableFinder');
        $phpBinaryMock->shouldReceive('find')->with(false)->zeroOrMoreTimes()->andReturn('php');

        $utilsMock = Mockery::mock('alias:Symfony\Component\Process\ProcessUtils');
        $utilsMock->shouldReceive('escapeArgument')->withAnyArgs()->zeroOrMoreTimes()->andReturnUsing(function ($value) {
            return $value;
        });
    }

    /** @test */
    public function it_can_not_be_initiated_without_constructor_arguments()
    {
        $this->expectException(PHPUnit_Framework_Error::class);
        return new Command();
    }

    /** @test */
    public function only_one_constructor_argument_is_required()
    {
        $command = new Command('test');
        $this->assertInstanceOf(Command::class, $command);
    }

    /** @test */
    public function it_has_static_constructor_named_factory()
    {
        $command = Command::factory('test');
        $this->assertInstanceOf(Command::class, $command);
    }

    /** @test */
    public function it_can_run_command_in_background()
    {
        $this->shouldRecieveExecCallOnceWith('(php artisan test:command) > /dev/null 2>&1 &');

        $command = Command::factory('test:command');
        $command->runInBackground();
    }

    /** @test */
    public function run_in_background_supports_before_subcommand()
    {
        $this->shouldRecieveExecCallOnceWith('(before command && php artisan test:command) > /dev/null 2>&1 &');

        $command = Command::factory('test:command', 'before command');
        $command->runInBackground();
    }

    /** @test */
    public function run_in_background_supports_after_subcommand()
    {
        $this->shouldRecieveExecCallOnceWith('(php artisan test:command && after command) > /dev/null 2>&1 &');

        $command = Command::factory('test:command', null, 'after command');
        $command->runInBackground();
    }

    /** @test */
    public function run_in_background_supports_before_and_after_subcommands_together()
    {
        $this->shouldRecieveExecCallOnceWith('(before && php artisan test:command && after) > /dev/null 2>&1 &');

        $command = Command::factory('test:command', 'before', 'after');
        $command->runInBackground();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_adds_php_option_for_hhvm()
    {
        $this->shouldRecieveExecCallOnceWith('(before && php --php artisan test:command && after) > /dev/null 2>&1 &');

        define('HHVM_VERSION', true);
        $command = Command::factory('test:command', 'before', 'after');
        $command->runInBackground();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_supports_overriding_of_artisan_binary_through_constant()
    {
        $this->shouldRecieveExecCallOnceWith('(before && php custom-artisan test:command && after) > /dev/null 2>&1 &');

        define('ARTISAN_BINARY', 'custom-artisan');
        $command = Command::factory('test:command', 'before', 'after');
        $command->runInBackground();
    }

    private function shouldRecieveExecCallOnceWith($with)
    {
        self::$functions->shouldReceive('exec')->with($with)->once();
    }
}

function exec($command)
{
    return CommandTest::$functions->exec($command);
}
