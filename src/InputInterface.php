<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

interface InputInterface
{
	/**
	 * @param	array	$arguments	Array of arguments; like $argv
	 * @param	array	$env		Array of environment variables; like getenv()
	 * @return	void
	 */
	public function __construct( array $arguments, array $env );

	/**
	 * @param	int|string	$argumentIndex
	 * @return	string
	 */
	public function getArgument( $argumentIndex ) : string;

	/**
	 * @return	string
	 */
	public function getCommand();

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
	 * @return	string
	 */
	public function getSubCommand();

	/**
	 * @param	int|string	$argumentIndex
	 * @return	boolean
	 */
	public function hasArgument( $argumentIndex ) : bool;

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
	 * @return	boolean
	 */
	public function hasSubCommand() : bool;
}
