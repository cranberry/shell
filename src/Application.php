<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

use Cranberry\Shell\Input;
use Cranberry\Shell\Middleware;
use Cranberry\Shell\Output;

class Application
{
	const ERROR_STRING_INVALIDCOMMAND = "%1\$s: '%2\$s' is not a %1\$s command. See '%1\$s --help'.";
	const ERROR_STRING_INVALIDCOMMANDUSAGE = 'usage: %s %s %s';

	/**
	 * @var	array
	 */
	protected $commandDescriptionStrings=[];

	/**
	 * @var	array
	 */
	protected $commandUsageStrings=[];

	/**
	 * @var	array
	 */
	protected $errorMiddlewareQueue=[];

	/**
	 * @var	int
	 */
	private $exitCode=0;

	/**
	 * @var	Cranberry\Shell\Input\InputInterface
	 */
	protected $input;

	/**
	 * @var	array
	 */
	protected $middlewareParameters=[];

	/**
	 * @var	array
	 */
	protected $middlewareQueue=[];

	/**
	 * @var	string
	 */
	protected $name;

	/**
	 * @var	Cranberry\Shell\Output\OutputInterface
	 */
	protected $output;

	/**
	 * @var	string
	 */
	protected $version;

	/**
	 * @param	string	$name
	 *
	 * @param	string	$version
	 *
	 * @param	Cranberry\Shell\Input\InputInterface	$input
	 *
	 * @param	Cranberry\Shell\Output\OutputInterface	$output
	 *
	 * @return	void
	 */
	public function __construct( string $name, string $version, Input\InputInterface $input, Output\OutputInterface $output )
	{
		$this->name = $name;
		$this->version = $version;
		$this->input = $input;
		$this->output = $output;

		/*
		 * Global middleware
		 */

		/* --version */
		$this->pushMiddleware( new Middleware\Middleware( function( $input, &$output )
		{
			if( $input->hasOption( 'version' ) )
			{
				$version = sprintf( '%s version %s', $this->getName(), $this->getVersion() );
				$output->write( $version . PHP_EOL );

				return Middleware\Middleware::EXIT;
			}

			return Middleware\Middleware::CONTINUE;
		}));

		/* --help */
		$this->pushMiddleware( new Middleware\Middleware( function( $input, &$output )
		{
			if( $input->hasOption( 'help' ) )
			{
				$usage  = sprintf( 'usage: %s [--help] [--version] <command> [<args>]', $this->getName() ) . PHP_EOL;
				$usage .= PHP_EOL;
				$usage .= 'Commands are:' . PHP_EOL;
				$usage .= PHP_EOL;

				foreach( $this->commandDescriptionStrings as $commandName => $commandDescription )
				{
					$usage .= sprintf( '   %-10s %s', $commandName, $commandDescription ) . PHP_EOL;
				}

				$usage .= PHP_EOL;
				$usage .= sprintf( "See '%s --help <command>' to read about a specific command.", $this->getName() ) . PHP_EOL;

				$output->write( $usage );

				return Middleware\Middleware::EXIT;
			}

			return Middleware\Middleware::CONTINUE;
		}));

		/*
		 * Exception-handling middleware
		 */

		$invalidCommandMiddleware = new Middleware\Middleware( function( Input\InputInterface $input, Output\OutputInterface &$output, Exception\InvalidCommandException $exception )
		{
			$output->write( sprintf( self::ERROR_STRING_INVALIDCOMMAND, $this->getName(), $input->getCommand() ) . PHP_EOL );
		});
		$invalidCommandMiddleware->setRoute( Exception\InvalidCommandException::class );
		$this->pushErrorMiddleware( $invalidCommandMiddleware );

		$invalidCommandUsageMiddleware = new Middleware\Middleware( function( Input\InputInterface $input, Output\OutputInterface &$output, Exception\InvalidCommandUsageException $exception )
		{
			$commandName = $input->getCommand();
			$output->write( sprintf( self::ERROR_STRING_INVALIDCOMMANDUSAGE, $this->getName(), $commandName, $this->getCommandUsage( $commandName ) ) . PHP_EOL );
		});
		$invalidCommandUsageMiddleware->setRoute( Exception\InvalidCommandUsageException::class );
		$this->pushErrorMiddleware( $invalidCommandUsageMiddleware );
	}

	/**
	 * Terminate execution of application
	 *
	 * @return	void
	 */
	public function exit()
	{
		exit( $this->exitCode );
	}

	/**
	 * Return command description string
	 *
	 * @param	string	$commandName
	 *
	 * @throws	OutOfBoundsException	If usage string not defined
	 *
	 * @return	string
	 */
	public function getCommandDescription( string $commandName ) : string
	{
		if( !$this->hasCommandDescription( $commandName ) )
		{
			throw new \OutOfBoundsException( "Description string not defined for command '{$commandName}'" );
		}

		return $this->commandDescriptionStrings[$commandName];
	}

	/**
	 * Return command usage string
	 *
	 * @param	string	$commandName
	 *
	 * @throws	OutOfBoundsException	If usage string not defined
	 *
	 * @return	string
	 */
	public function getCommandUsage( string $commandName ) : string
	{
		if( !$this->hasCommandUsage( $commandName ) )
		{
			throw new \OutOfBoundsException( "Usage string not defined for command '{$commandName}'" );
		}

		return $this->commandUsageStrings[$commandName];
	}

	/**
	 * Returns exit code
	 *
	 * @return	int
	 */
	public function getExitCode() : int
	{
		return $this->exitCode;
	}

	/**
	 * Returns application name
	 *
	 * @return	string
	 */
	public function getName() : string
	{
		return $this->name;
	}

	/**
	 * Returns application version
	 *
	 * @return	string
	 */
	public function getVersion() : string
	{
		return $this->version;
	}

	/**
	 * Checks if command description string is defined
	 *
	 * @param	string	$commandName
	 *
	 * @return	boolean
	 */
	public function hasCommandDescription( string $commandName ) : bool
	{
		return array_key_exists( $commandName, $this->commandDescriptionStrings );
	}

	/**
	 * Checks if command usage string is defined
	 *
	 * @param	string	$commandName
	 *
	 * @return	boolean
	 */
	public function hasCommandUsage( string $commandName ) : bool
	{
		return array_key_exists( $commandName, $this->commandUsageStrings );
	}

	/**
	 * Push a Middleware object onto the end of the error queue
	 *
	 * @param	Cranberry\Shell\Middleware\MiddlewareInterface	$middleware
	 *
	 * @return	void
	 */
	public function pushErrorMiddleware( Middleware\MiddlewareInterface $middleware )
	{
		array_push( $this->errorMiddlewareQueue, $middleware );
	}

	/**
	 * Push a Middleware object onto the end of the run() queue
	 *
	 * @param	Cranberry\Shell\Middleware\MiddlewareInterface	$middleware
	 *
	 * @return	void
	 */
	public function pushMiddleware( Middleware\MiddlewareInterface $middleware )
	{
		array_push( $this->middlewareQueue, $middleware );
	}

	/**
	 * Push a parameter onto the array of parameters passed to Middleware::run()
	 *
	 * @param	mixed	$parameter
	 *
	 * @return	void
	 */
	public function registerMiddlewareParameter( &$parameter )
	{
		$this->middlewareParameters[] = &$parameter;
	}

	/**
	 * Process middleware queue
	 *
	 * @return	void
	 */
	public function run()
	{
		/* Add last-defense error-handling middleware */
		$this->pushMiddleware( new Middleware\Middleware( function( Input\InputInterface $input, Output\OutputInterface &$output )
		{
			if( $input->hasCommand() )
			{
				throw new Exception\InvalidCommandException( $input->getCommand() );
			}
		}));

		/*
		 * Route and execute middleware queue
		 */
		$route = '';

		if( $this->input->hasCommand() )
		{
			$route = $this->input->getCommand();
		}

		foreach( $this->middlewareQueue as $middleware )
		{
			if( !$middleware->matchesRoute( $route ) )
			{
				continue;
			}

			$middleware->bindTo( $this );

			$parameters = $this->middlewareParameters;

			array_unshift( $parameters, $this->output );
			array_unshift( $parameters, $this->input );

			try
			{
				$returnValue = call_user_func_array( [$middleware, 'run'], $parameters );

				if( $returnValue == Middleware\MiddlewareInterface::EXIT )
				{
					break;
				}
			}
			catch( \Exception $exception )
			{
				/* Terminate with exit code 1 (unless overridden by middleware) */
				$this->setExitCode( 1 );

				$errorRoute = get_class( $exception );

				foreach( $this->errorMiddlewareQueue as $errorMiddleware )
				{
					if( !$errorMiddleware->matchesRoute( $errorRoute, false ) )
					{
						continue;
					}

					$errorMiddleware->bindTo( $this );

					$parameters = $this->middlewareParameters;

					array_unshift( $parameters, $exception );
					array_unshift( $parameters, $this->output );
					array_unshift( $parameters, $this->input );

					call_user_func_array( [$errorMiddleware, 'run'], $parameters );
				}

				break;
			}
		}
	}

	/**
	 * Set the description string for a given command
	 *
	 * @param	string	$commandName
	 *
	 * @param	string	$commandDescription
	 *
	 * @return	void
	 */
	public function setCommandDescription( string $commandName, string $commandDescription )
	{
		$this->commandDescriptionStrings[$commandName] = $commandDescription;
	}

	/**
	 * Set the usage string for a given command
	 *
	 * @param	string	$commandName
	 *
	 * @param	string	$commandUsage
	 *
	 * @return	void
	 */
	public function setCommandUsage( string $commandName, string $commandUsage )
	{
		$this->commandUsageStrings[$commandName] = $commandUsage;
	}

	/**
	 * Set the application exit code
	 *
	 * @param	int	$exitCode
	 *
	 * @return	void
	 */
	public function setExitCode( int $exitCode )
	{
		$this->exitCode = $exitCode;
	}

	/**
	 * Prepend a Middleware object to the beginning of the error queue
	 *
	 * @param	Cranberry\Shell\Middleware\MiddlewareInterface	$middleware
	 *
	 * @return	void
	 */
	public function unshiftErrorMiddleware( Middleware\MiddlewareInterface $middleware )
	{
		array_unshift( $this->errorMiddlewareQueue, $middleware );
	}

	/**
	 * Prepend a Middleware object to the beginning of the run() queue
	 *
	 * @param	Cranberry\Shell\Middleware\MiddlewareInterface	$middleware
	 *
	 * @return	void
	 */
	public function unshiftMiddleware( Middleware\MiddlewareInterface $middleware )
	{
		array_unshift( $this->middlewareQueue, $middleware );
	}
}
