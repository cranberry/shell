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
	 * @var	string
	 */
	protected $commandName;

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
			$optionData = self::parseOptionString( $argument );

			if( $optionData === false )
			{
				break;
			}

			foreach( $optionData as $optionName => $optionValue )
			{
				$this->registerApplicationOption( $optionName, $optionValue );
			}
		}
		while( $argument !== false );

		if( $argument !== false )
		{
			$this->commandName = $argument;
		}
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
	 * @return	string
	 */
	public function getCommandName()
	{
		return $this->commandName;
	}

	/**
	 * @param	string	$optionString
	 * @return	array|false
	 */
	public static function parseOptionString( string $optionString )
	{
		/* $optionString must match `-a`, `-abc`, or `--foo` */
		if( substr( $optionString, 0, 1 ) != '-' )
		{
			return false;
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
	public function registerApplicationOption( $optionName, $optionValue )
	{
		$this->applicationOptions[$optionName] = $optionValue;
	}
}
