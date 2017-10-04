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
	public function getOutputStub()
	{
		$outputStub = new \Cranberry\ShellTest\OutputStub();
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
		$outputStub = $this->getOutputStub();

		$appVersion = '1.' . microtime( true );
		$application = new Application( 'foo', $appVersion, $inputStub, $outputStub );

		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			throw new \Exception( 'An error occurred' );
		}));

		$application->pushErrorMiddleware( new Middleware\Middleware( function( &$input, &$output, \Exception $exception )
		{
			$output->write( $this->getVersion() );
		}));

		$application->run();

		$this->assertEquals( $appVersion, $outputStub->getBuffer() );
	}

	public function testExceptionSetsExitCodeTo1()
	{
		$input = new Input\Input( ['app','command'], [] );
		$outputStub = $this->getOutputStub();

		$application = new Application( 'app', '1.23', $input, $outputStub );
		$application->pushMiddleware( new Middleware\Middleware( function()
		{
			throw new \Exception();
		}));

		$this->assertEquals( 0, $application->getExitCode() );

		try
		{
			$application->run();
		}
		catch( \Exception $e )
		{
			$this->assertEquals( 1, $application->getExitCode() );
		}
	}

	public function testGetApplicationUsage()
	{
		$input = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$appName = 'app-' . microtime( true );
		$application = new Application( $appName, '1.23', $input, $outputStub );

		$application->setCommandDescription( 'world', 'Say world' );
		$application->setCommandDescription( 'hello', 'Say hello' );

		/* Should automatically sort by command name */
		$expectedCommandDescriptions = "   hello      Say hello\n   world      Say world\n";
		$appUsage = sprintf( Application::STRING_APPUSAGE, $appName, '[--help] [--version]', $expectedCommandDescriptions );

		$this->assertEquals( $appUsage, $application->getApplicationUsage() );
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

	/**
	 * @dataProvider	optionCallbackProvider
	 */
	public function testHelpCallbackReturnValue( $hasOption, $expectedReturnValue )
	{
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'hasOption' )
			->willReturn( $hasOption );

		$outputStub = $this->getOutputStub();

		$appName = 'app-' . microtime( true );
		$application = new Application( $appName, '1.23', $inputStub, $outputStub );

		$returnValue = $application->___helpCallback( $inputStub, $outputStub );

		$this->assertEquals( $expectedReturnValue, $returnValue );
	}

	public function testHelpCallbackWithCommandOutputsCommandUsage()
	{
		$appName = 'app-' . microtime( true );
		$commandName = 'command-' . microtime( true );
		$commandUsage = 'usage-' . microtime( true );

		$input = new Input\Input( [$appName, '--help', $commandName], [] );
		$outputStub = $this->getOutputStub();

		$application = new Application( $appName, '1.23', $input, $outputStub );
		$application->setCommandUsage( $commandName, $commandUsage );

		$returnValue = $application->___helpCallback( $input, $outputStub );

		$this->assertEquals( null, $returnValue );

		$expectedUsage = sprintf( Application::STRING_COMMANDUSAGE, $appName, $commandName, $commandUsage ) . PHP_EOL;
		$this->assertEquals( $expectedUsage, $outputStub->getBuffer() );
	}

	public function testHelpCallbackWithoutCommandOutputsApplicationUsage()
	{
		$appName = 'app-' . microtime( true );
		$commandName = 'command-' . microtime( true );
		$commandUsage = 'usage-' . microtime( true );

		$input = new Input\Input( [$appName, '--help'], [] );
		$outputStub = $this->getOutputStub();

		$application = new Application( $appName, '1.23', $input, $outputStub );
		$application->setCommandUsage( $commandName, $commandUsage );

		$returnValue = $application->___helpCallback( $input, $outputStub );

		$this->assertEquals( null, $returnValue );
		$this->assertEquals( $application->getApplicationUsage() . PHP_EOL, $outputStub->getBuffer() );
	}

	/**
	 * @expectedException	Cranberry\Shell\Exception\InvalidCommandException
	 */
	public function testHelpCallbackWithInvalidCommandThrowsInvalidCommandException()
	{
		$appName = 'app-' . microtime( true );
		$commandName = 'command-' . microtime( true );

		$input = new Input\Input( [$appName, '--help', $commandName], [] );
		$outputStub = $this->getOutputStub();

		$application = new Application( $appName, '1.23', $input, $outputStub );

		$returnValue = $application->___helpCallback( $input, $outputStub );
	}

	public function testHelpOptionWithoutCommandOutputsApplicationUsage()
	{
		$input = new Input\Input( ['cranberry','--help'], [] );
		$outputStub = $this->getOutputStub();

		$appName = 'app-' . microtime( true );
		$appVersion = '1.' . microtime( true );
		$application = new Application( $appName, $appVersion, $input, $outputStub );

		$application->setCommandDescription( 'world', 'Say world' );
		$application->setCommandDescription( 'hello', 'Say hello' );

		$application->run();

		$appUsage = $application->getApplicationUsage() . PHP_EOL;

		$this->assertEquals( $appUsage, $outputStub->getBuffer() );
	}

	public function testInvalidApplicationUsageCallback()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$appName = 'app-' . microtime( true );
		$application = new Application( $appName, '1.23', $inputStub, $outputStub );

		$application->setCommandDescription( 'hello', microtime( true ) );
		$application->setCommandDescription( 'world', microtime( true ) );

		$exceptionStub = $this
			->getMockBuilder( Exception\InvalidApplicationUsageException::class )
			->disableOriginalConstructor()
			->getMock();

		$returnValue = $application->___invalidApplicationUsageCallback( $inputStub, $outputStub, $exceptionStub );

		$this->assertEquals( null, $returnValue );
		$this->assertEquals( $application->getApplicationUsage() . PHP_EOL, $outputStub->getBuffer() );
	}

	public function testInvalidCommand()
	{
		$appName = 'app-' . microtime( true );
		$commandName = 'command-' . microtime( true );

		$input = new Input\Input( [$appName, $commandName], [] );
		$outputStub = $this->getOutputStub();

		$application = new Application( $appName, '1.23', $input, $outputStub );
		$application->run();

		$this->assertEquals( 1, $application->getExitCode() );
		$this->assertEquals( sprintf( Application::ERROR_STRING_INVALIDCOMMAND, $appName, $commandName ) . PHP_EOL, $outputStub->getBuffer() );
	}

	public function testInvalidCommandCallback()
	{
		$commandName = 'command-' . microtime( true );

		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'getCommand' )
			->willReturn( $commandName );

		$outputStub = $this->getOutputStub();

		$appName = 'app-' . microtime( true );
		$application = new Application( $appName, '1.23', $inputStub, $outputStub );

		$exceptionStub = $this
			->getMockBuilder( Exception\InvalidCommandException::class )
			->disableOriginalConstructor()
			->getMock();

		$returnValue = $application->___invalidCommandCallback( $inputStub, $outputStub, $exceptionStub );

		$this->assertEquals( null, $returnValue );
		$this->assertEquals( sprintf( Application::ERROR_STRING_INVALIDCOMMAND, $appName, $commandName ) . PHP_EOL, $outputStub->getBuffer() );
	}

	public function testInvalidCommandUsageCallback()
	{
		$commandName = 'command-' . microtime( true );
		$commandUsage = 'usage-' . microtime( true );

		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'getCommand' )
			->willReturn( $commandName );

		$outputStub = $this->getOutputStub();

		$appName = 'app-' . microtime( true );
		$application = new Application( $appName, '1.23', $inputStub, $outputStub );

		$application->setCommandUsage( $commandName, $commandUsage );

		$exceptionStub = $this
			->getMockBuilder( Exception\InvalidCommandUsageException::class )
			->disableOriginalConstructor()
			->getMock();

		$returnValue = $application->___invalidCommandUsageCallback( $inputStub, $outputStub, $exceptionStub );

		$this->assertEquals( null, $returnValue );
		$this->assertEquals( sprintf( Application::STRING_COMMANDUSAGE, $appName, $commandName, $commandUsage ) . PHP_EOL, $outputStub->getBuffer() );
	}

	public function testInvalidCommandUsage()
	{
		$appName = 'app-' . microtime( true );
		$commandName = 'command-' . microtime( true );
		$commandUsage = '<arg1> [<arg2>]';

		$input = new Input\Input( [$appName, $commandName], [] );
		$outputStub = $this->getOutputStub();

		$application = new Application( $appName, '1.23', $input, $outputStub );
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

		$this->assertEquals( sprintf( Application::STRING_COMMANDUSAGE, $appName, $commandName, $commandUsage ) . PHP_EOL, $outputStub->getBuffer() );
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

		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

		$application->pushMiddleware( new Middleware\Middleware( function( &$input, &$output )
		{
			throw new \Exception( 'Invalid request', 1 );
		}));
		$application->pushErrorMiddleware( new Middleware\Middleware( function( &$input, &$output, \Exception $exception )
		{
			$output->write( 'An error' );
			return Middleware\Middleware::CONTINUE;
		}));
		$application->pushErrorMiddleware( new Middleware\Middleware( function( &$input, &$output, \Exception $exception )
		{
			$output->write( ' occurred: ' . $exception->getMessage() );
		}));

		$application->run();

		$this->assertEquals( 'An error occurred: Invalid request', $outputStub->getBuffer() );
	}

	public function testPushMiddlewareAppendsToEndOfQueue()
	{
		$envTime = (string) microtime( true );
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'getEnv' )
			->willReturn( $envTime );

		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

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

		$this->assertEquals( "It's {$envTime}", $outputStub->getBuffer() );
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
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $input, $outputStub );

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

		$this->assertEquals( '13', $outputStub->getBuffer() );
	}

	public function testRunExitsWhenMiddlewareReturnsEXIT()
	{
		$inputStub = $this->getInputStub();

		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

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

		$this->assertEquals( 'HELLO', $outputStub->getBuffer() );
	}

	/**
	 * @expectedException		Exception
	 * @expectedExceptionCode	1234
	 */
	public function testUnroutedExceptionIsRethrown()
	{
		$inputStub = $this->getInputStub();
		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

		$application->pushMiddleware( new Middleware\Middleware( function( $inputStub, $outputStub )
		{
			throw new \Exception( 'Invalid request', 1234 );
		}));

		$application->run();
	}

	public function testUnshiftErrorMiddlewarePrependsToBeginningOfQueue()
	{
		$envTime = (string) microtime( true );
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'getEnv' )
			->willReturn( $envTime );

		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

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
			return Middleware\Middleware::CONTINUE;
		}));

		$application->run();

		$this->assertEquals( 'An error occurred: Invalid request', $outputStub->getBuffer() );
	}

	public function testUnshiftMiddlewarePrependsToBeginningOfQueue()
	{
		$envTime = (string) microtime( true );
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'getEnv' )
			->willReturn( $envTime );

		$outputStub = $this->getOutputStub();

		$application = new Application( 'foo', '1.23b', $inputStub, $outputStub );

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

		$this->assertEquals( "It's {$envTime}", $outputStub->getBuffer() );
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

		$outputStub = $this->getOutputStub();

		$appName = 'app-' . microtime( true );
		$appVersion = '1.' . microtime( true );
		$application = new Application( $appName, $appVersion, $inputStub, $outputStub );

		$returnValue = $application->___versionCallback( $inputStub, $outputStub );

		$this->assertEquals( $expectedReturnValue, $returnValue );

		if( $hasOption )
		{
			$this->assertEquals( sprintf( Application::STRING_APPVERSION . PHP_EOL, $appName, $appVersion ), $outputStub->getBuffer() );
		}
	}
}
