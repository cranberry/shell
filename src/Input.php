<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

class Input
{
	/**
	 * @var	array
	 */
	protected $applicationOptions=[];

	/**
	 * @var	array
	 */
	protected $commandArgumentNames=[];

	/**
	 * @var	array
	 */
	protected $commandArguments=[];

	/**
	 * @var	string
	 */
	protected $commandName;

	/**
	 * @var	array
	 */
	protected $commandOptions=[];

	/**
	 * @param	array	$arguments	Array of arguments; like $argv
	 * @return	void
	 */
	public function __construct( array $arguments )
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
				$this->registerCommandArgument( $argument );
			}
		}
		while( $argument !== false );
	}

	/**
	 * @param	string	$optionName
	 * @return	mixed
	 */
	public function getApplicationOption( $optionName )
	{
		if( isset( $this->applicationOptions[$optionName] ) )
		{
			return $this->applicationOptions[$optionName];
		}
	}

	/**
	 * @return	array
	 */
	public function getCommandArguments() : array
	{
		return $this->commandArguments;
	}

	/**
	 * @param	int|string	$query
	 * @return	string
	 */
	public function getCommandArgument( $query ) : string
	{
		if( is_string( $query ) )
		{
			return $this->getCommandArgumentByName( $query );
		}

		if( is_int( $query ) )
		{
			return $this->getCommandArgumentByIndex( $query );
		}

		$exceptionMessage = sprintf( 'Argument 1 passed to %s() must be of the type string or int, %s passed', __METHOD__, gettype( $query ) );
		throw new \InvalidArgumentException( $exceptionMessage );
	}

	/**
	 * @param	int		$index
	 * @return	string
	 */
	public function getCommandArgumentByIndex( int $index ) : string
	{
		if( !isset( $this->commandArguments[$index] ) )
		{
			throw new \OutOfBoundsException( "Invalid command argument index '{$index}'" );
		}

		return $this->commandArguments[$index];
	}

	/**
	 * @param	string	$name
	 * @return	string
	 */
	public function getCommandArgumentByName( $name ) : string
	{
		if( !isset( $this->commandArgumentNames[$name] ) )
		{
			throw new \OutOfBoundsException( "Invalid named argument '{$name}'" );
		}

		$index = $this->commandArgumentNames[$name];
		return $this->getCommandArgumentByIndex( $index );
	}

	/**
	 * @return	string
	 */
	public function getCommandName()
	{
		return $this->commandName;
	}

	/**
	 * @param	string	$optionName
	 * @return	mixed|null
	 */
	public function getCommandOption( $optionName )
	{
		if( isset( $this->commandOptions[$optionName] ) )
		{
			return $this->commandOptions[$optionName];
		}
	}

	/**
	 * @param	int		$index
	 * @param	string	$name
	 * @return	void
	 */
	public function nameCommandArgument( int $index, string $name )
	{
		$this->commandArgumentNames[$name] = $index;
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
	public function registerCommandArgument( string $argument )
	{
		if( strlen( $argument ) < 1 )
		{
			return;
		}

		$this->commandArguments[] = $argument;
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
