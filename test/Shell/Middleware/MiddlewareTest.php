<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Middleware;

use Cranberry\Shell\Input;
use Cranberry\Shell\Output;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
	/**
	 * @var    string
	 */
	protected static $tempPathname;

	public function __routePatternProvider()
	{
		return [
			['[show|ls]', 'ls'],					// aliases
			['[show|ls]', 'show'],					// aliases
			['queue( \S+)?', 'queue'],				// optional argument
			['queue( \S+)?', 'queue shuffle'],		// optional argument
			['queue \S+', 'queue shuffle'],			// required argument
		];
	}

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

	/**
	 * @expectedException	InvalidArgumentException
	 */
	public function testPassingNonObjectToBindToThrowsException()
	{
		$middleware = new Middleware( function(){} );

		$nonObject = [];
		$middleware->bindTo( $nonObject );
	}

	public function testBindToObject()
	{
		$closure = function( Input\InputInterface &$input, Output\OutputInterface &$output, &$time )
		{
			$time = $this->time;
		};

		$argTime = null;

		$boundObject = new \stdClass();
		$boundObject->time = (string)microtime( true );

		$input = new Input\Input( ['app'], [] );
		$output = new Output\Output();

		$middleware = new Middleware( $closure );
		$middleware->bindTo( $boundObject );
		$middleware->run( $input, $output, $argTime );

		$this->assertSame( $boundObject->time, $argTime );
	}

	public function testGetCallback()
	{
		$closure = function( Input\InputInterface &$input, Output\OutputInterface &$output )
		{
			$output->line( time() );
		};

		$middleware = new Middleware( $closure );
		$this->assertSame( $closure, $middleware->getCallback() );
	}

	public function testFlushOutputBuffer()
	{
		$closure = function( Input\InputInterface &$input, Output\OutputInterface &$output, $time )
		{
			$output->buffer( $time );
		};

		$argTime = (string)microtime( true );

		$input = new Input\Input( ['app'], [] );

		$output = new Output\Output();
		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$middleware = new Middleware( $closure );
		$middleware->run( $input, $output, $argTime );

		$this->assertFalse( file_exists( $streamTarget ) );

		$output->flush();

		$this->assertTrue( file_exists( $streamTarget ) );
		$this->assertEquals( $argTime, file_get_contents( $streamTarget ) );
	}

	public function testRunPassesArgumentsByReference()
	{
		$callback = function( Input\InputInterface &$input, Output\OutputInterface &$output, &$time )
		{
			$time = $input->getEnv( 'CRANBERRY_TIME' );
		};

		$argTime = null;
		$envTime = (string)microtime( true );

		$input = new Input\Input( ['command'], ['CRANBERRY_TIME' => $envTime] );
		$output = new Output\Output();

		$middleware = new Middleware( $callback );
		$middleware->run( $input, $output, $argTime );

		$this->assertSame( $envTime, $argTime );
	}

	public function testRunningCallbackWithNoReturnValueReturnsCONTINUE()
	{
		$callback = function( Input\InputInterface &$input, Output\OutputInterface &$output ){};

		$input = new Input\Input( ['command'], [] );
		$output = new Output\Output();

		$middleware = new Middleware( $callback );
		$returnValue = $middleware->run( $input, $output );

		$this->assertSame( Middleware::CONTINUE, $returnValue );
	}

	public function testRunningCallbackWithReturnValueEXITReturnsEXIT()
	{
		$callback = function( Input\InputInterface &$input, Output\OutputInterface &$output )
		{
			return Middleware::EXIT;
		};

		$input = new Input\Input( ['command'], [] );
		$output = new Output\Output();

		$middleware = new Middleware( $callback );
		$returnValue = $middleware->run( $input, $output );

		$this->assertSame( Middleware::EXIT, $returnValue );
	}

	public function testMatchesUndefinedRouteReturnsTrue()
	{
		$middleware = new Middleware( function(){} );

		$this->assertTrue( $middleware->matchesRoute( 'foo' ) );
	}

	public function testMatchMismatchedRouteReturnsFalse()
	{
		$middleware = new Middleware( function(){} );
		$middleware->setRoute( '[show|ls]' );

		$this->assertFalse( $middleware->matchesRoute( 'add' ) );
	}

	/**
	 * @dataProvider	__routePatternProvider
	 */
	public function testMatchMatchingRouteReturnsTrue( $pattern, $route )
	{
		$middleware = new Middleware( function(){} );
		$middleware->setRoute( $pattern );

		$this->assertTrue( $middleware->matchesRoute( $route ) );
	}
}
