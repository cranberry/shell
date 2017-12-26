<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Input;

interface InputInterface
{
	/**
	 * @param	array	$arguments	Array of arguments; like $argv
	 * @param	array	$env		Array of environment variables; like getenv()
	 * @return	void
	 */
	public function __construct( array $arguments, array $env );

	/**
	 * Returns application name
	 *
	 * @return	string
	 */
	public function getApplicationName() : string;

	/**
	 * @param	int|string	$key
	 * @return	string
	 */
	public function getArgument( $key ) : string;

	/**
	 * @return	string
	 */
	public function getCommandName() : string;

	/**
	 * @param	string	$envName
	 * @return	string
	 */
	public function getEnv( string $envName ) : string;

	/**
	 * @param	string	$optionName
	 * @return	mixed
	 */
	public function getOption( string $optionName );

	/**
	 * Returns subcommand name
	 *
	 * @throws	OutOfBoundsException	If subcommand is not defined
	 *
	 * @return	string
	 */
	public function getSubcommandName() : string;

	/**
	 * @param	int|string	$key
	 * @return	boolean
	 */
	public function hasArgument( $key ) : bool;

	/**
	 * @return	boolean
	 */
	public function hasCommand() : bool;

	/**
	 * @param	string	$envName
	 * @return	boolean
	 */
	public function hasEnv( string $envName ) : bool;

	/**
	 * @param	string	$optionName
	 * @return	boolean
	 */
	public function hasOption( string $optionName ) : bool;

	/**
	 * Checks if subcommand is defined
	 *
	 * @return	boolean
	 */
	public function hasSubcommand() : bool;
}
