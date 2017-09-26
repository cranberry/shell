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
		});
		$middleware_1->setRoute( 'command' );
		$application->pushMiddleware( $middleware_1 );

		/* 2: Optional subcommand match */
		$middleware_2 = new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( '2' );
		});
		$middleware_2->setRoute( 'command( \S+)?' );
		$application->pushMiddleware( $middleware_2 );

		/* 3: Required subcommand mismatch */
		$middleware_3 = new Middleware\Middleware( function( &$input, &$output )
		{
			$output->write( '3' );
		});
		$middleware_3->setRoute( 'command subcommand' );
		$application->pushMiddleware( $middleware_3 );

		$application->run();
		$this->assertEquals( '12', file_get_contents( $streamTarget ) );
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
		}));

		$application->run();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( "It's {$envTime}", file_get_contents( $streamTarget ) );
	}

	public function testVersionOption()
	{
		$inputStub = $this->getInputStub();
		$inputStub
			->method( 'hasOption' )
			->willReturn( true );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$appName = 'app-' . microtime( true );
		$appVersion = '1.' . microtime( true );
		$application = new Application( $appName, $appVersion, $inputStub, $output );

		$this->assertFalse( file_exists( $streamTarget ) );

		$application->run();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( sprintf( '%s version %s' . PHP_EOL, $appName, $appVersion ), file_get_contents( $streamTarget ) );
	}
}
