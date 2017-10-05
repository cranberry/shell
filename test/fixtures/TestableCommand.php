<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\ShellTest;

class TestableCommand extends \Cranberry\Shell\Command\Command
{
	/**
	 * Set command description
	 *
	 * @param	string	$description
	 */
	public function setDescription( string $description )
	{
		$this->description = $description;
	}

	/**
	 * Set array of Middleware objects
	 *
	 * @param	array	$middleware
	 */
	public function setMiddleware( array $middleware )
	{
		$this->middleware = $middleware;
	}

	/**
	 * Set command name
	 *
	 * @return	string
	 */
	public function setName( string $name )
	{
		$this->name = $name;
	}

	/**
	 * Set command usage
	 *
	 * @return	string
	 */
	public function setUsage( string $usage )
	{
		$this->usage = $usage;
	}

	/**
	 * Set subcommand usage
	 *
	 * @param	boolean	$hasSubcommand
	 *
	 * @return	void
	 */
	public function setHasSubcommand( bool $hasSubcommand )
	{
		$this->hasSubcommand = $hasSubcommand;
	}
}
