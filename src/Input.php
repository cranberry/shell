<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

class Input implements InputInterface
{
	/**
	 * @var	array
	 */
	protected $applicationOptions=[];

	/**
	 * @var	array
	 */
	protected $argumentNames=[];

	/**
	 * @var	array
	 */
	protected $arguments=[];

	/**
	 * @var	string
	 */
	protected $commandName;

	/**
	 * @var	array
	 */
	protected $commandOptions=[];

	/**
	 * @var	array
	 */
	protected $env=[];

	/**
	 * @param	array	$arguments	Array of arguments; like $argv
	 * @param	array	$env		Array of environment variables; like getenv()
	 * @return	void
	 */
	public function __construct( array $arguments, array $env )
	{
		if( count( $arguments ) == 0 )
		{
			throw new \LengthException( 'Invalid arguments' );
		}

		/* Process application options */
		do
		{
			$argument = next( $arguments );

			try
			{
				$optionData = self::parseOptionString( $argument );

				foreach( $optionData as $optionName => $optionValue )
				{
					$this->registerApplicationOption( $optionName, $optionValue );
				}
			}
			catch( \InvalidArgumentException $e )
			{
				break;
			}
		}
		while( $argument !== false );

		/* Process command name */
		if( $argument !== false )
		{
			$this->commandName = $argument;
		}

		do
		{
			$argument = next( $arguments );

			/* Process command option */
			try
			{
				$optionData = self::parseOptionString( $argument );

				foreach( $optionData as $optionName => $optionValue )
				{
					$this->registerCommandOption( $optionName, $optionValue );
				}
			}

			/* Process command argument */
			catch( \InvalidArgumentException $e )
			{
				$this->registerArgument( $argument );
			}
		}
		while( $argument !== false );

		/* Environment Variables */
		$this->env = $env;
	}

	/**
	 * @param	string	$optionName
	 * @return	mixed
	 */
	public function getApplicationOption( string $optionName )
	{
		if( !isset( $this->applicationOptions[$optionName] ) )
		{
			throw new \OutOfBoundsException( "Application option '{$optionName}' not found" );
		}

		return $this->applicationOptions[$optionName];
	}

	/**
	 * @param	int|string	$key
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
	 * @param	int		$index
	 * @return	string
	 */
	public function getArgumentByIndex( int $index ) : string
	{
		if( !isset( $this->arguments[$index] ) )
		{
			throw new \OutOfBoundsException( "Invalid command argument index '{$index}'" );
		}

		return $this->arguments[$index];
	}

	/**
	 * @param	string	$name
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
	 * @return	array
	 */
	public function getArguments() : array
	{
		return $this->arguments;
	}

	/**
	 * @return	string
	 */
	public function getCommand()
	{
		return $this->commandName;
	}

	/**
	 * @param	string	$optionName
	 * @return	mixed
	 */
	public function getCommandOption( string $optionName )
	{
		if( !isset( $this->commandOptions[$optionName] ) )
		{
			throw new \OutOfBoundsException( "Command option '{$optionName}' not found" );
		}

		return $this->commandOptions[$optionName];
	}

	/**
	 * @return	array
	 */
	public function getCommandOptions() : array
	{
		return $this->commandOptions;
	}

	/**
	 * @param	string	$envName
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
	 * Get value of $optionName as either command or application option. Prefers
	 * value of command option over application option if both exist.
	 *
	 * @param	string	$optionName
	 * @return	mixed
	 */
	public function getOption( string $optionName )
	{
		/* Command option value is preferred over application */
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
	 * @param	string	$optionName
	 * @return	boolean
	 */
	public function hasApplicationOption( string $optionName ) : bool
	{
		return isset( $this->applicationOptions[$optionName] );
	}

	/**
	 * @return	boolean
	 */
	public function hasCommand() : bool
	{
		return $this->commandName !== null;
	}

	/**
	 * @param	string	$optionName
	 * @return	boolean
	 */
	public function hasCommandOption( string $optionName ) : bool
	{
		return isset( $this->commandOptions[$optionName] );
	}

	/**
	 * @param	string	$envName
	 * @return	boolean
	 */
	public function hasEnv( string $envName ) : bool
	{
		return isset( $this->env[$envName] );
	}

	/**
	 * Evaluate existence of $optionName as either application or command option
	 *
	 * @param	string	$optionName
	 * @return	boolean
	 */
	public function hasOption( string $optionName ) : bool
	{
		$hasOption = false;
		$hasOption = $hasOption || $this->hasApplicationOption( $optionName );
		$hasOption = $hasOption || $this->hasCommandOption( $optionName );

		return $hasOption;
	}

	/**
	 * @param	int		$index
	 * @param	string	$name
	 * @return	void
	 */
	public function nameArgument( int $index, string $name )
	{
		$this->argumentNames[$name] = $index;
	}

	/**
	 * @param	string	$optionString
	 * @return	array
	 */
	public static function parseOptionString( string $optionString ) : array
	{
		/* $optionString must match `-a`, `-abc`, or `--foo` */
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
	 * @param	string	$optionName
	 * @param	mixed	$optionValue
	 * @return	void
	 */
	public function registerApplicationOption( string $optionName, $optionValue )
	{
		$this->applicationOptions[$optionName] = $optionValue;
	}

	/**
	 * @param	string	$argument
	 * @return	void
	 */
	public function registerArgument( string $argument )
	{
		if( strlen( $argument ) < 1 )
		{
			return;
		}

		$this->arguments[] = $argument;
	}

	/**
	 * @param	string	$optionName
	 * @param	mixed	$optionValue
	 * @return	void
	 */
	public function registerCommandOption( string $optionName, $optionValue )
	{
		$this->commandOptions[$optionName] = $optionValue;
	}
}
