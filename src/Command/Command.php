<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Command;

abstract class Command implements CommandInterface
{
	/**
	 * @var	string
	 */
	protected $description;

	/**
	 * @var	array
	 */
	protected $middleware=[];

	/**
	 * @var	string
	 */
	protected $name;

	/**
	 * @var	array
	 */
	protected $usage=[];

	/**
	 * @var	boolean
	 */
	protected $hasSubcommand=false;

	/**
	 * Returns command description
	 *
	 * @return	string
	 */
	public function getDescription() : string
	{
		return $this->description;
	}

	/**
	 * Returns array of Middleware objects
	 *
	 * @return	array
	 */
	public function getMiddleware() : array
	{
		return $this->middleware;
	}

	/**
	 * Returns command name
	 *
	 * @return	string
	 */
	public function getName() : string
	{
		return $this->name;
	}

	/**
	 * Returns command usage
	 *
	 * @return	string
	 */
	public function getUsage() : string
	{
		return $this->usage;
	}

	/**
	 * Finds whether the command supports subcommands
	 *
	 * @return	boolean
	 */
	public function hasSubcommand() : bool
	{
		return $this->hasSubcommand;
	}
}
