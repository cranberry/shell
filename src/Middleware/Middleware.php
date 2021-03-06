<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Middleware;

use Cranberry\Shell\Input\InputInterface;
use Cranberry\Shell\Output\OutputInterface;

class Middleware implements MiddlewareInterface
{
	/**
	 * @var	Callable
	 */
	protected $callback;

	/**
	 * @var	string
	 */
	protected $route;

	/**
	 * @param	Callable	$callback
	 *
	 * @param	string		$route
	 *
	 * @return	void
	 */
	public function __construct( Callable $callback, string $route=null )
	{
		$this->callback = $callback;

		if( $route != null )
		{
			$this->setRoute( $route );
		}
	}

	/**
	 * Binds the callback to a new object and scope
	 *
	 * @param	object	$object
	 *
	 * @return	boolean
	 */
	public function bindTo( $object ) : bool
	{
		if( !is_object( $object ) )
		{
			$exceptionMessage = sprintf( 'Argument 1 passed to %s() must be of the type object, %s passed', __METHOD__, gettype( $object ) );
			throw new \InvalidArgumentException( $exceptionMessage );
		}

		if( is_object( $this->callback ) )
		{
			$this->callback = $this->callback->bindTo( $object );
			return true;
		}

		return false;
	}

	/**
	 * Returns the callback
	 *
	 * @return	Callable
	 */
	public function getCallback() : Callable
	{
		return $this->callback;
	}

	/**
	 * Checks whether middleware matches a route
	 *
	 * Middleware which does not define a route pattern will match all routes
	 *
	 * @param	string	$route
	 *
	 * @param	boolean	$useRegex
	 *
	 * @return	boolean
	 */
	public function matchesRoute( string $route, bool $useRegex=true ) : bool
	{
		if( $this->route == null )
		{
			return true;
		}

		if( $useRegex )
		{
			$pattern = sprintf( '/%s/', $this->route );
			$result = preg_match( $pattern, $route );

			return $result === 1;
		}

		return $this->route === $route;
	}

	/**
	 * Calls the callback
	 *
	 * @param	InputInterface	$input	Passed to callback by reference
	 *
	 * @param	OutputInterface	$output	Passed to callback by reference
	 *
	 * @param	array			$optionalArguments	Array of additional arguments, passed by value
	 *
	 * @return	int
	 */
	public function run( InputInterface $input, OutputInterface $output, ...$optionalArguments ) : int
	{
		/* Populate arguments array manually; passing by reference not supported
		   by `array_unshift`, et al */
		$arguments[] = &$input;
		$arguments[] = &$output;

		$arguments = array_merge( $arguments, $optionalArguments );

		$returnValue = call_user_func_array( $this->callback, $arguments );
		if( $returnValue === self::CONTINUE )
		{
			return self::CONTINUE;
		}

		return self::EXIT;
	}

	/**
	 * Sets the route
	 *
	 * @param string	$route
	 *
	 * @return	void
	 */
	public function setRoute( string $route )
	{
		$this->route = $route;
	}
}
