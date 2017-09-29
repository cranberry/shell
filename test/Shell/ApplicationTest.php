<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

use Cranberry\Shell\Input;
use Cranberry\Shell\Output;
use Cranberry\Shell\Middleware;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
	/**
	 * @var    string
	 */
	protected static $tempPathname;

	public static function setUpBeforeClass()
	{
		self::$tempPathname = dirname( dirname( __DIR__ ) ) . '/fixtures/temp';
		if( !file_exists( self::$tempPathname ) )
		{
			mkdir( self::$tempPathname, 0777, true );
		}
	}

	public static function tearDownAfterClass()
	{
		if( file_exists( self::$tempPathname ) )
		{
			$command = sprintf( 'rm -r %s', self::$tempPathname );
			exec( $command );
		}
	}

	public function getOutputStub()
	{
		$outputStub = $this
			->getMockBuilder( Output\Output::class )
			->disableOriginalConstructor()
			->getMock();

		return $outputStub;
	}

	public function getInputStub()
	{
		$inputStub = $this
			->getMockBuilder( Input\Input::class )
			->disableOriginalConstructor()
			->getMock();

		return $inputStub;
	}

	public function optionCallbackProvider()
	{
		return [
			[true, null],
			[false, Middleware\Middleware::CONTINUE],
		];
	}

	public function testErrorMiddlewareIsBoundToApplication()
	{
		$inputStub = $this->getInputStub();

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$appVersion = '1.' . microtime( true );
		$application = new Application( 'foo', $appVersion, $inputStub, $output );

		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			throw new \Exception( 'An error occurred' );
		}));

		$application->pushErrorMiddleware( new Middleware\Middleware( function( &$input, &$output, \Exception $exception )
		{
			$output->write( $this->getVersion() );
			return Middleware\Middleware::CONTINUE;
		}));

		$application->run();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( $appVersion, file_get_contents( $streamTarget ) );
	}

	public function testExceptionSetsExitCodeTo1()
	{
		$input = new Input\Input( ['app','command'], [] );
		$output = $this->getOutputStub();

		$application = new Application( 'app', '1.23', $input, $output );
		$application->pushMiddleware( new Middleware\Middleware( function()
		{
			throw new \Exception();
		}));

		$this->assertEquals( 0, $application->getExitCode() );

		$application->run();

		$this->assertEquals( 1, $application->getExitCode() );
	}

	public function testGetCommandDescription()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

		$commandName = 'command-' . microtime( true );
		$commandDescription = 'A brief description ' . microtime( true );

		$application->setCommandDescription( $commandName, $commandDescription );

		$this->assertEquals( $commandDescription, $application->getCommandDescription( $commandName ) );
	}

	public function testGetCommandUsage()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

		$commandName = 'command-' . microtime( true );
		$commandUsage = '<arg1> [<arg2>]';

		$application->setCommandUsage( $commandName, $commandUsage );

		$this->assertEquals( $commandUsage, $application->getCommandUsage( $commandName ) );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function testGetInvalidCommandDescriptionThrowsException()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );
		$commandName = 'command-' . microtime( true );

		$application->getCommandDescription( $commandName );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function testGetInvalidCommandUsageThrowsException()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );
		$commandName = 'command-' . microtime( true );

		$application->getCommandUsage( $commandName );
	}

	public function testGetExitCode()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'app', '0.1.0', $inputStub, $outputStub );

		$this->assertEquals( 0, $application->getExitCode() );

		$application->setExitCode( 1 );
		$this->assertEquals( 1, $application->getExitCode() );
	}

	public function testGetName()
	{
		$name = 'foo-' . microtime( true );
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( $name, '0.1.0', $inputStub, $outputStub );

		$this->assertEquals( $name, $application->getName() );
	}

	public function testGetVersion()
	{
		$version = (string) microtime( true );
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', $version, $inputStub, $outputStub );

		$this->assertEquals( $version, $application->getVersion() );
	}

	public function testHasInvalidCommandDescriptionReturnsFalse()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

		$this->assertFalse( $application->hasCommandDescription( 'command' ) );
	}

	public function testHasInvalidCommandUsageReturnsFalse()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

		$this->assertFalse( $application->hasCommandUsage( 'command' ) );
	}

	public function testHasValidCommandUsageReturnsTrue()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

		$commandName = 'command-' . microtime( true );
		$commandUsage = '<arg1> [<arg2>]';

		$application->setCommandUsage( $commandName, $commandUsage );

		$this->assertTrue( $application->hasCommandUsage( $commandName ) );
	}

	public function testHasValidCommandDescriptionReturnsTrue()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

		$commandName = 'command-' . microtime( true );
		$commandDescription = 'A brief description ' . microtime( true );

		$application->setCommandDescription( $commandName, $commandDescription );

		$this->assertTrue( $application->hasCommandDescription( $commandName ) );
	}

	public function testHelpOptionWithoutCommandOutputsApplicationUsage()
	{
		$input = new Input\Input( ['cranberry','--help'], [] );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$appName = 'app-' . microtime( true );
		$appVersion = '1.' . microtime( true );
		$application = new Application( $appName, $appVersion, $input, $output );

		$application->setCommandDescription( 'hello', 'Say hello' );

		$application->run();

		$appUsage = <<<USAGE
usage: {$appName} [--help] [--version] <command> [<args>]

Commands are:

   hello      Say hello

See '{$appName} --help <command>' to read about a specific command.

USAGE;

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( $appUsage, file_get_contents( $streamTarget ) );
	}

	public function testInvalidCommand()
	{
		$appName = 'app-' . microtime( true );
		$commandName = 'command-' . microtime( true );
		$input = new Input\Input( [$appName, $commandName], [] );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$application = new Application( $appName, '1.23', $input, $output );
		$application->run();

		$this->assertEquals( 1, $application->getExitCode() );
		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( sprintf( Application::ERROR_STRING_INVALIDCOMMAND, $appName, $commandName ) . PHP_EOL, file_get_contents( $streamTarget ) );
	}

	public function testInvalidCommandUsage()
	{
		$appName = 'app-' . microtime( true );
		$commandName = 'command-' . microtime( true );
		$commandUsage = '<arg1> [<arg2>]';

		$input = new Input\Input( [$appName, $commandName], [] );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$application = new Application( $appName, '1.23', $input, $output );
		$application->setCommandUsage( $commandName, $commandUsage );

		$middleware = new Middleware\Middleware( function( $input, $output )
		{
			$input->nameArgument( 0, 'arg1' );
			$input->nameArgument( 1, 'arg2' );

			if( !$input->hasArgument( 'arg1' ) )
			{
				throw new \Cranberry\Shell\Exception\InvalidCommandUsageException();
			}
		});

		$application->pushMiddleware( $middleware );
		$application->run();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( sprintf( Application::ERROR_STRING_INVALIDCOMMANDUSAGE, $appName, $commandName, $commandUsage ) . PHP_EOL, file_get_contents( $streamTarget ) );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function testInvalidCommandUsageWithUndefinedUsageThrowsException()
	{
		$appName = 'app-' . microtime( true );
		$commandName = 'command-' . microtime( true );

		$input = new Input\Input( [$appName, $commandName], [] );
		$output = new Output\Output();

		$application = new Application( $appName, '1.23', $input, $output );

		$middleware = new Middleware\Middleware( function( $input, $output )
		{
			$input->nameArgument( 0, 'arg1' );
			$input->nameArgument( 1, 'arg2' );

			if( !$input->hasArgument( 'arg1' ) )
			{
				throw new \Cranberry\Shell\Exception\InvalidCommandUsageException();
			}
		});

		$application->pushMiddleware( $middleware );
		$application->run();
	}

	public function testMiddlewareIsBoundToApplication()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$appVersion = '1.' . microtime( true );
		$application = new Application( 'foo', $appVersion, $inputStub, $outputStub );

		$middlewareParam = new \stdClass();
		$this->assertFalse( isset( $middlewareParam->name ) );
		$this->assertFalse( isset( $middlewareParam->version ) );

		$application->registerMiddlewareParameter( $middlewareParam );

		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output, $object )
		{
			$object->version = $this->getVersion();
		}));

		$application->run();

		$this->assertTrue( isset( $middlewareParam->version ) );
		$this->assertEquals( $appVersion, $middlewareParam->version );
	}

	public function testPushErrorMiddlewareAppendsToEndOfQueue()
	{
		$envTime = (string) microtime( true );
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'getEnv' )
			->willReturn( $envTime );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$application = new Application( 'foo', '1.23b', $inputStub, $output );

		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			throw new \Exception( 'Invalid request', 1 );
		}));
		$application->pushErrorMiddleware( new Middleware\Middleware( function( &$input, &$output, \Exception $exception )
		{
			$output->write( 'An error' );
		}));
		$application->pushErrorMiddleware( new Middleware\Middleware( function( &$input, &$output, \Exception $exception )
		{
			$output->write( ' occurred: ' . $exception->getMessage() );
		}));

		$application->run();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( 'An error occurred: Invalid request', file_get_contents( $streamTarget ) );
	}

	public function testPushMiddlewareAppendsToEndOfQueue()
	{
		$envTime = (string) microtime( true );
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'getEnv' )
			->willReturn( $envTime );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$application = new Application( 'foo', '1.23b', $inputStub, $output );

		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( "It's " );
			return Middleware\Middleware::CONTINUE;
		}));
		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( $input->getEnv( 'CRANBERRY_TIME' ) );
		}));

		$application->run();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( "It's {$envTime}", file_get_contents( $streamTarget ) );
	}

	public function testRegisterMiddlewareParameter()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$middlewareParam = new \stdClass();
		$this->assertFalse( isset( $middlewareParam->foo ) );

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );
		$application->registerMiddlewareParameter( $middlewareParam );

		$middleware = new Middleware\Middleware( function( &$input, &$output, $object )
		{
			$object->foo = 'bar';
		});
		$application->pushMiddleware( $middleware );

		$application->run();

		$this->assertTrue( isset( $middlewareParam->foo ) );
		$this->assertEquals( 'bar', $middlewareParam->foo );
	}

	public function testRunRoutesMiddlewareWithCommandName()
	{
		$input = new Input\Input( ['cranberry', 'command'], [] );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$application = new Application( 'foo', '1.23b', $input, $output );

		/* 1: Command match */
		$middleware_1 = new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( '1' );
			return Middleware\Middleware::CONTINUE;
		});
		$middleware_1->setRoute( 'command' );
		$application->pushMiddleware( $middleware_1 );

		/* 2: Required subcommand mismatch */
		$middleware_2 = new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( '2' );
			return Middleware\Middleware::CONTINUE;
		});
		$middleware_2->setRoute( 'command subcommand' );
		$application->pushMiddleware( $middleware_2 );

		/* 3: Optional subcommand match */
		$middleware_3 = new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( '3' );
		});
		$middleware_3->setRoute( 'command( \S+)?' );
		$application->pushMiddleware( $middleware_3 );

		$application->run();
		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( '13', file_get_contents( $streamTarget ) );
	}

	public function testRunExitsWhenMiddlewareReturnsEXIT()
	{
		$inputStub = $this->getInputStub();

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$application = new Application( 'foo', '1.23b', $inputStub, $output );

		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( 'HELLO' );
			return Middleware\Middleware::EXIT;
		}));
		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( " WORLD" );
		}));

		$application->run();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( 'HELLO', file_get_contents( $streamTarget ) );
	}

	public function testUnshiftErrorMiddlewarePrependsToBeginningOfQueue()
	{
		$envTime = (string) microtime( true );
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'getEnv' )
			->willReturn( $envTime );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$application = new Application( 'foo', '1.23b', $inputStub, $output );

		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			throw new \Exception( 'Invalid request', 1 );
		}));

		$application->pushErrorMiddleware( new Middleware\Middleware( function( &$input, &$output, \Exception $exception )
		{
			$output->write( ' occurred: ' . $exception->getMessage() );
		}));
		$application->unshiftErrorMiddleware( new Middleware\Middleware( function( &$input, &$output, \Exception $exception )
		{
			$output->write( 'An error' );
		}));

		$application->run();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( 'An error occurred: Invalid request', file_get_contents( $streamTarget ) );
	}

	public function testUnshiftMiddlewarePrependsToBeginningOfQueue()
	{
		$envTime = (string) microtime( true );
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'getEnv' )
			->willReturn( $envTime );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$application = new Application( 'foo', '1.23b', $inputStub, $output );

		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( $input->getEnv( 'CRANBERRY_TIME' ) );
		}));
		$application->unshiftMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( "It's " );
			return Middleware\Middleware::CONTINUE;
		}));

		$application->run();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( "It's {$envTime}", file_get_contents( $streamTarget ) );
	}

	/**
	 * @dataProvider	optionCallbackProvider
	 */
	public function testVersionCallback( $hasOption, $expectedReturnValue )
	{
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'hasOption' )
			->willReturn( $hasOption );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$appName = 'app-' . microtime( true );
		$appVersion = '1.' . microtime( true );
		$application = new Application( $appName, $appVersion, $inputStub, $output );

		$this->assertFalse( file_exists( $streamTarget ) );

		$returnValue = $application->___versionCallback( $inputStub, $output );

		$this->assertEquals( $expectedReturnValue, $returnValue );
		$this->assertEquals( $hasOption, file_exists( $streamTarget ) );

		if( $hasOption )
		{
			$this->assertEquals( sprintf( Application::STRING_APPVERSION . PHP_EOL, $appName, $appVersion ), file_get_contents( $streamTarget ) );
		}
	}
}
