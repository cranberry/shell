<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

use Cranberry\Shell\Input;
use Cranberry\Shell\Output;

class ApplicationFactory
{
	/**
	 * Returns a new Application object
	 *
	 * Written to be compatible with PHP 5 so older versions can fail gracefully
	 *
 	 * @param	string	$name
 	 *
 	 * @param	string	$version
	 *
	 * @param	string	$phpMinimum
 	 *
 	 * @param	array	$argv
	 *
	 * @param	array	$env
	 *
	 * @throws	RuntimeException	If $phpMinimum is greater than phpversion()
 	 *
	 * @return	Cranberry\Shell\Application
	 */
	static public function create( $name, $version, $phpMinimum, array $argv, array $env )
	{
		$phpVersion = phpversion();
		if( version_compare( $phpVersion, $phpMinimum, '<' ) )
		{
			$exceptionMessage = sprintf( '%s: Requires PHP %s or newer, %s found.', $name, $phpMinimum, $phpVersion );
			throw new \RuntimeException( $exceptionMessage, 1 );
		}

		$input = new Input\Input( $argv, $env );
		$output = new Output\Output();

		return new Application( $name, $version, $input, $output );
	}
}
