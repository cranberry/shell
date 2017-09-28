<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Middleware;

use Cranberry\Shell\Input\InputInterface;
use Cranberry\Shell\Output\OutputInterface;

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
	 * @return	boolean
	 */
	public function bindTo( $object ) : bool;

	/**
	 * Returns the callback
	 *
	 * @return	Callable
	 */
	public function getCallback() : Callable;

	/**
	 * Finds whether middleware matches route
	 *
	 * @param	string	$route
	 *
	 * @param	boolean	$useRegex
	 *
	 * @return	boolean
	 */
	public function matchesRoute( string $route, bool $useRegex=true ) : bool;

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
	public function run( InputInterface $input, OutputInterface $output, ...$optionalArguments ) : int;

	/**
	 * Sets the route
	 *
	 * @param	string	$route
	 *
	 * @return	void
	 */
	public function setRoute( string $route );
}
