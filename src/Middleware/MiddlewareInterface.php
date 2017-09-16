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
	 * @return	void
	 */
	public function __construct( Callable $callback );

	/**
	 * @param	object	$object
	 *
	 * @return	void
	 */
	public function bindTo( $object );

	/**
	 * @return	Callable
	 */
	public function getCallback() : Callable;

	/**
	 * @param	InputInterface	$input
	 * @param	array			$arguments	Array of additional arguments
	 *
	 * @return	int
	 */
	public function run( InputInterface &$input, &...$arguments ) : int;
}
