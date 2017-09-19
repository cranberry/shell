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
}
