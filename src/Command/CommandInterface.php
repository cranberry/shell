<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Command;

interface CommandInterface
{
	/**
	 * Returns command description
	 *
	 * @return	string
	 */
	public function getDescription() : string;

	/**
	 * Returns array of middleware objects
	 *
	 * @return	array
	 */
	public function getMiddleware() : array;

	/**
	 * Returns command name
	 *
	 * @return	string
	 */
	public function getName() : string;

	/**
	 * Returns command usage string
	 *
	 * @return	string
	 */
	public function getUsage() : string;

	/**
	 * Finds whether the command supports subcommands
	 *
	 * @return	boolean
	 */
	public function hasSubcommand() : bool;
}
