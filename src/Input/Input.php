<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Input;

class Input implements InputInterface
{
	/**
	 * @var	array
	 */
	protected $argumentNames=[];

	/**
	 * Array of arguments; like $argv
	 *
	 * @var	array
	 */
	protected $argv=[];

	/**
	 * @var	array
	 */
	protected $argvMembers;

	/**
	 * Array of environment variables; like getenv()
	 *
	 * @var	array
	 */
	protected $env=[];

	/**
	 * Whether to identify the second argument as a command when parsing
	 *
	 * @var	bool
	 */
	protected $shouldRecognizeCommand=false;

	/**
	 * Whether to identify the third argument as a subcommand when parsing
	 *
	 * @var	bool
	 */
	protected $shouldRecognizeSubcommand=false;

	/**
	 * Whether to re-parse arguments into members, or return cached members
	 *
 	 * @var	bool
	 */
	protected $shouldParseArgumentsIntoMembers=false;

	/**
	 * @param	array	$argv	Array of arguments; like $argv
	 *
	 * @param	array	$env	Array of environment variables; like getenv()
	 *
	 * @return	void
	 */
	public function __construct( array $argv, array $env )
	{
		if( count( $argv ) == 0 )
		{
			throw new \LengthException( 'Invalid arguments' );
		}

		$this->argv = $argv;
		$this->env = $env;
	}

	/**
	 * Returns application name
	 *
	 * @return	string
	 */
	public function getApplicationName() : string
	{
		$argvMembers = $this->getArgvMembers();
		return $argvMembers['application_name'];
	}

	/**
	 * Returns value of specified application option, if present
	 *
	 * @param	string	$optionName
	 *
	 * @throws	OutOfBoundsException	If application option not found
	 *
	 * @return	mixed
	 */
	public function getApplicationOption( string $optionName )
	{
		$argvMembers = $this->getArgvMembers();

		if( !isset( $argvMembers['application_options'][$optionName] ) )
		{
			throw new \OutOfBoundsException( "Application option '{$optionName}' not found" );
		}

		return $argvMembers['application_options'][$optionName];
	}

	/**
	 * Convenience method for getting numerically indexed or named arguments
	 *
	 * @param	int|string	$key
	 *
	 * @throws	InvalidArgumentException	If $key uses incorrect type
	 *
	 * @return	string
	 */
	public function getArgument( $key ) : string
	{
		if( is_string( $key ) )
		{
			return $this->getArgumentByName( $key );
		}

		if( is_int( $key ) )
		{
			return $this->getArgumentByIndex( $key );
		}

		$exceptionMessage = sprintf( 'Argument 1 passed to %s() must be of the type string or int, %s passed', __METHOD__, gettype( $key ) );
		throw new \InvalidArgumentException( $exceptionMessage );
	}

	/**
	 * Returns numerically indexed argument
	 *
	 * @param	int		$index
	 *
	 * @throws	OutOfBoundsException	If attempting to access invalid index
	 *
	 * @return	string
	 */
	public function getArgumentByIndex( int $index ) : string
	{
		$argvMembers = $this->getArgvMembers();

		if( !isset( $argvMembers['arguments'][$index] ) )
		{
			throw new \OutOfBoundsException( "Invalid argument index '{$index}'" );
		}

		return $argvMembers['arguments'][$index];
	}

	/**
	 * Returns named argument
	 *
	 * @param	string	$name
	 *
	 * @throws	OutOfBoundsException	If attempting to access invalid index
	 *
	 * @return	string
	 */
	public function getArgumentByName( string $name ) : string
	{
		if( !isset( $this->argumentNames[$name] ) )
		{
			throw new \OutOfBoundsException( "Invalid named argument '{$name}'" );
		}

		$index = $this->argumentNames[$name];
		return $this->getArgumentByIndex( $index );
	}

	/**
	 * Returns array containing all arguments
	 *
	 * @return	array
	 */
	public function getArguments() : array
	{
		$argvMembers = $this->getArgvMembers();
		return $argvMembers['arguments'];
	}

	/**
	 * Returns an associative array containing identified members of the input
	 * arguments.
	 *
	 * @return	array
	 */
	protected function getArgvMembers() : array
	{
		/* We have a cached copy from a previous parsing */
		if( $this->argvMembers != null )
		{
			/* According to our simple heuristics, we don't need to parse again */
			if( !$this->shouldParseArgumentsIntoMembers )
			{
				return $this->argvMembers;
			}
		}

		/* Reset the internal pointer to prevent weird surprises */
		$arguments = $this->argv;

		$argvMembers = [
			'application_name'		=> null,
			'application_options'	=> [],
			'command_name'			=> null,
			'command_options'		=> [],
			'subcommand_name'		=> null,
			'subcommand_options'	=> [],
			'arguments'				=> [],
		];

		/* Application Name */
		$argvMembers['application_name'] = array_shift( $arguments );
		$optionBucket = 'application_options';

		foreach( $arguments as $argument )
		{
			try
			{
				$optionData = self::getOptionStringValues( $argument );

				foreach( $optionData as $optionName => $optionValue )
				{
					$argvMembers[$optionBucket][$optionName] = $optionValue;
				}
			}

			// The current argument does not match the option string
			// format (`-a`, `-abc`, `--foo`, `--foo=bar`).
			catch( \InvalidArgumentException $e )
			{
				if( $argvMembers['command_name'] == null && $this->shouldRecognizeCommand )
				{
					$argvMembers['command_name'] = $argument;

					/* Future options should be flagged at the command level */
					$optionBucket = 'command_options';
				}
				else if( $argvMembers['subcommand_name'] == null && $this->shouldRecognizeSubcommand )
				{
					$argvMembers['subcommand_name'] = $argument;

					/* Future options should be flagged at the subcommand level */
					$optionBucket = 'subcommand_options';
				}
				else
				{
					$argvMembers['arguments'][] = $argument;
				}
			}
		}

		$this->argvMembers = $argvMembers;
		$this->shouldParseArgumentsIntoMembers = false;

		return $this->argvMembers;
	}

	/**
	 * Returns name of command if present
	 *
	 * @throws	OutOfBoundsException	If command not recognized or included in arguments
	 *
	 * @return	string
	 */
	public function getCommandName() : string
	{
		$argvMembers = $this->getArgvMembers();
		if( $argvMembers['command_name'] === null )
		{
			throw new \OutOfBoundsException( "Command name not defined" );
		}

		return $argvMembers['command_name'];
	}

	/**
	 * Returns value of given command option if defined
	 *
	 * @param	string	$optionName
	 *
	 * @throws	OutOfBoundsException	If option not defined
	 *
	 * @return	mixed
	 */
	public function getCommandOption( string $optionName )
	{
		$argvMembers = $this->getArgvMembers();
		if( !isset( $argvMembers['command_options'][$optionName] ) )
		{
			throw new \OutOfBoundsException( "Command option '{$optionName}' not found" );
		}

		return $argvMembers['command_options'][$optionName];
	}

	/**
	 * Returns value of given environment variable if defined
	 *
	 * @param	string	$envName
	 *
	 * @throws	OutOfBoundsException	If environment variable not defined
	 *
	 * @return	string
	 */
	public function getEnv( string $envName ) : string
	{
		if( !isset( $this->env[$envName] ) )
		{
			throw new \OutOfBoundsException( "Environment variable '{$envName}' not found" );
		}

		return $this->env[$envName];
	}

	/**
	 * Returns value of $optionName as either subcommand, command or application
	 * option, and prefers them in that order if there are collisions.
	 *
	 * @param	string	$optionName
	 *
	 * @throws	OutOfBoundsException	If the option is not found anywhere
	 *
	 * @return	mixed
	 */
	public function getOption( string $optionName )
	{
		/* Order of preference: subcommand > command > application */

		if( $this->hasSubcommandOption( $optionName ) )
		{
			return $this->getSubcommandOption( $optionName );
		}
		if( $this->hasCommandOption( $optionName ) )
		{
			return $this->getCommandOption( $optionName );
		}
		if( $this->hasApplicationOption( $optionName ) )
		{
			return $this->getApplicationOption( $optionName );
		}

		throw new \OutOfBoundsException( "Option '{$optionName}' not found" );
	}

	/**
	 * Parses string for option
	 *
	 * @param	string	$optionString
	 *
	 * @return	array
	 */
	public static function getOptionStringValues( string $optionString ) : array
	{
		/* $optionString must match `-a`, `-abc`, `--foo`, or `--foo=bar` */
		if( substr( $optionString, 0, 1 ) != '-' )
		{
			throw new \InvalidArgumentException( 'Option string should match format "-a", "-abc", "--foo", or "--foo=bar"' );
		}

		$results = [];
		$optionString = substr( $optionString, 1 );

		/* Short Option(s) */
		if( substr( $optionString, 0, 1 ) != '-' )
		{
			$optionStringLength = mb_strlen( $optionString );
			while( $optionStringLength > 0 )
			{
				$shortOptionName = mb_substr( $optionString, 0, 1, 'UTF-8' );

				/*
				 * Short option values default to `true`
				 */
				if( !isset( $results[$shortOptionName] ) )
				{
					$results[$shortOptionName] = true;
				}

				/*
				 * If a short option is repeated, however, switch value to an
				 * integer corresponding to the number of instances
				 *
				 * ex., -vv=2, -vvv=3, etc.
				 */
				else
				{
					if( $results[$shortOptionName] === true )
					{
						$results[$shortOptionName] = 2;
					}
					else
					{
						$results[$shortOptionName]++;
					}
				}

				$optionString = mb_substr( $optionString, 1 , $optionStringLength, 'UTF-8' );
				$optionStringLength = mb_strlen( $optionString );
			}
		}
		/* Long Option */
		else
		{
			$optionString = substr( $optionString, 1 );

			$longOptionPieces = explode( '=', $optionString );
			$longOptionName = $longOptionPieces[0];

			if( count( $longOptionPieces ) > 1 )
			{
				$longOptionValue = $longOptionPieces[1];
			}
			else
			{
				$longOptionValue = true;
			}

			$results[$longOptionName] = $longOptionValue;
		}

		return $results;
	}

	/**
	 * Returns subcommand name
	 *
	 * @throws	OutOfBoundsException	If subcommand is not defined
	 *
	 * @return	string
	 */
	public function getSubcommandName() : string
	{
		$argvMembers = $this->getArgvMembers();
		if( $argvMembers['subcommand_name'] === null )
		{
			throw new \OutOfBoundsException( "Subcommand name not defined" );
		}

		return $argvMembers['subcommand_name'];
	}

	/**
	 * Returns value of given subcommand option if defined
	 *
	 * @param	string	$optionName
	 *
	 * @throws	OutOfBoundsException	If subcommand option is not defined
	 *
	 * @return	mixed
	 */
	public function getSubcommandOption( string $optionName )
	{
		$argvMembers = $this->getArgvMembers();
		if( !isset( $argvMembers['subcommand_options'][$optionName] ) )
		{
			throw new \OutOfBoundsException( "Command option '{$optionName}' not found" );
		}

		return $argvMembers['subcommand_options'][$optionName];
	}

	/**
	 * Returns whether arguments contain a given application option
	 *
	 * @param	string	$optionName		ex., 'foo' or 'f'
	 *
	 * @return	boolean
	 */
	public function hasApplicationOption( string $optionName ) : bool
	{
		$argvMembers = $this->getArgvMembers();
		return isset( $argvMembers['application_options'][$optionName] );
	}

	/**
	 * Returns whether arguments contain a given argument, whether by index or
	 * by name
	 *
	 * @param	int|string	$key
	 *
	 * @return	boolean
	 */
	public function hasArgument( $key ) : bool
	{
		$argvMembers = $this->getArgvMembers();

		if( is_int( $key ) )
		{
			return isset( $argvMembers['arguments'][$key] );
		}
		else if( is_string( $key ) )
		{
			if( isset( $this->argumentNames[$key] ) )
			{
				return isset( $this->argv[$this->argumentNames[$key]] );
			}

			return false;
		}
	}

	/**
	 * Returns whether arguments contain a command
	 *
	 * Always returns `false` if self::recognizeCommand has not been called.
	 *
	 * @return	boolean
	 */
	public function hasCommand() : bool
	{
		if( !$this->shouldRecognizeCommand )
		{
			return false;
		}

		$argvMembers = $this->getArgvMembers();
		return $argvMembers['command_name'] !== null;
	}

	/**
	 * Returns whether arguments contain a given command option
	 *
	 * @param	string	$optionName
	 *
	 * @return	boolean
	 */
	public function hasCommandOption( string $optionName ) : bool
	{
		$argvMembers = $this->getArgvMembers();
		return isset( $argvMembers['command_options'][$optionName] );
	}

	/**
	 * Returns whether arguments contain a given environmental variable
	 *
	 * @param	string	$envName
	 *
	 * @return	boolean
	 */
	public function hasEnv( string $envName ) : bool
	{
		return isset( $this->env[$envName] );
	}

	/**
	 * Returns whether $optionName is found as subcommand, command, or
	 * application option
	 *
	 * @param	string	$optionName
	 *
	 * @return	boolean
	 */
	public function hasOption( string $optionName ) : bool
	{
		$hasOption = false;
		$hasOption = $hasOption || $this->hasApplicationOption( $optionName );
		$hasOption = $hasOption || $this->hasCommandOption( $optionName );
		$hasOption = $hasOption || $this->hasSubcommandOption( $optionName );

		return $hasOption;
	}

	/**
	 * Returns whether arguments contain a subcommand
	 *
	 * Always returns `false` if self::recognizeSubcommand has not been called
	 *
	 * @return	boolean
	 */
	public function hasSubcommand() : bool
	{
		if( !$this->shouldRecognizeSubcommand )
		{
			return false;
		}

		$argvMembers = $this->getArgvMembers();
		return $argvMembers['subcommand_name'] !== null;
	}

	/**
	 * Returns whether arguments contain a given subcommand option
	 *
	 * @param	string	$optionName
	 *
	 * @return	boolean
	 */
	public function hasSubcommandOption( string $optionName ) : bool
	{
		$argvMembers = $this->getArgvMembers();
		return isset( $argvMembers['subcommand_options'][$optionName] );
	}

	/**
	 * Assigns friendly name to argument by index
	 *
	 * @param	int		$index
	 *
	 * @param	string	$name
	 *
	 * @return	void
	 */
	public function nameArgument( int $index, string $name )
	{
		$this->argumentNames[$name] = $index;
	}

	/**
	 * Specify whether arguments should adhere to `<app> <command>` pattern
	 *
	 * @param	bool	$shouldRecognizeCommand
	 *
	 * @return	void
	 */
	public function recognizeCommand( bool $shouldRecognizeCommand )
	{
		$this->shouldRecognizeCommand = $shouldRecognizeCommand;
		$this->shouldParseArgumentsIntoMembers = true;
	}

	/**
	 * Specify whether arguments should adhere to `<app> <command> <subcommand>`
	 * pattern.
	 *
	 * Setting subcommand recognition to `true` implies command recognition
	 * should also be `true`
	 *
	 * @param	bool	$shouldRecognizeSubcommand
	 *
	 * @return	void
	 */
	public function recognizeSubcommand( bool $shouldRecognizeSubcommand )
	{
		if( $shouldRecognizeSubcommand )
		{
			$this->recognizeCommand( true );
		}

		$this->shouldRecognizeSubcommand = $shouldRecognizeSubcommand;
		$this->shouldParseArgumentsIntoMembers = true;
	}
}
