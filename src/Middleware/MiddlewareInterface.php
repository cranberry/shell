<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Middleware;

use Cranberry\Shell\InputInterface;
use Cranberry\Shell\OutputInterface;

interface MiddlewareInterface
{
	const CONTINUE = 0;
	const EXIT = 1;

	/**
	 * @param	Callable	$callback
	 *
	 * @return	void
	 */
	public function __construct( Callable $callback );

	/**
	 * Binds the callback to a new object and scope
	 *
	 * @param	object	$object
	 *
	 * @return	void
	 */
	public function bindTo( $object );

	/**
	 * Returns the callback
	 *
	 * @return	Callable
	 */
	public function getCallback() : Callable;

	/**
	 * Calls the callback
	 *
	 * @param	InputInterface	$input	Passed to callback by reference
	 *
	 * @param	array			$optionalArguments	Array of additional arguments, passed by reference
	 *
	 * @return	int
	 */
	public function run( InputInterface &$input, &...$arguments ) : int;
}
