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
	 * @param	Callable	$callback
	 *
	 * @return	void
	 */
	public function __construct( Callable $callback )
	{
		$this->callback = $callback;
	}

	/**
	 * Binds the callback to a new object and scope
	 *
	 * @param	object	$object
	 *
	 * @return	void
	 */
	public function bindTo( $object )
	{
		if( !is_object( $object ) )
		{
			$exceptionMessage = sprintf( 'Argument 1 passed to %s() must be of the type object, %s passed', __METHOD__, gettype( $object ) );
			throw new \InvalidArgumentException( $exceptionMessage );
		}

		$this->callback = $this->callback->bindTo( $object );
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
	 * Calls the callback
	 *
	 * @param	InputInterface	$input	Passed to callback by reference
	 *
	 * @param	OutputInterface	$output	Passed to callback by reference
	 *
	 * @param	array			$optionalArguments	Array of additional arguments, passed by reference
	 *
	 * @return	int
	 */
	public function run( InputInterface $input, OutputInterface $output, &...$optionalArguments ) : int
	{
		/* Populate arguments array manually; passing by reference not supported
		   by `array_unshift`, et al */
		$requiredArguments[] = &$input;
		$requiredArguments[] = &$output;

		$allArguments = array_merge( $requiredArguments, $optionalArguments );

		$returnValue = call_user_func_array( $this->callback, $allArguments );
		if( $returnValue === self::EXIT )
		{
			return self::EXIT;
		}

		return self::CONTINUE;
	}
}
