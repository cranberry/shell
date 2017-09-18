<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Middleware;

use Cranberry\Shell\Input;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
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
		$closure = function( Input\InputInterface &$input, &$time )
		{
			$time = $this->time;
		};

		$argTime = null;

		$boundObject = new \stdClass();
		$boundObject->time = (string)microtime( true );

		$input = new Input\Input( ['app'], [] );

		$middleware = new Middleware( $closure );
		$middleware->bindTo( $boundObject );
		$middleware->run( $input, $argTime );

		$this->assertSame( $boundObject->time, $argTime );
	}

	public function testGetCallback()
	{
		$closure = function( Input\InputInterface &$input )
		{
			$output->line( time() );
		};

		$middleware = new Middleware( $closure );
		$this->assertSame( $closure, $middleware->getCallback() );
	}

	public function testRunPassesArgumentsByReference()
	{
		$callback = function( Input\InputInterface &$input, &$time )
		{
			$time = $input->getEnv( 'CRANBERRY_TIME' );
		};

		$argTime = null;
		$envTime = (string)microtime( true );

		$input = new Input\Input( ['command'], ['CRANBERRY_TIME' => $envTime] );

		$middleware = new Middleware( $callback );
		$middleware->run( $input, $argTime );

		$this->assertSame( $envTime, $argTime );
	}

	public function testRunningCallbackWithNoReturnValueReturnsCONTINUE()
	{
		$callback = function( Input\InputInterface &$input ){};
		$input = new Input\Input( ['command'], [] );

		$middleware = new Middleware( $callback );
		$returnValue = $middleware->run( $input );

		$this->assertSame( Middleware::CONTINUE, $returnValue );
	}

	public function testRunningCallbackWithReturnValueEXITReturnsEXIT()
	{
		$callback = function( Input\InputInterface &$input )
		{
			return Middleware::EXIT;
		};
		$input = new Input\Input( ['command'], [] );

		$middleware = new Middleware( $callback );
		$returnValue = $middleware->run( $input );

		$this->assertSame( Middleware::EXIT, $returnValue );
	}
}
