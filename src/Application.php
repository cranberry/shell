<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

use Cranberry\Shell\Input;
use Cranberry\Shell\Middleware;
use Cranberry\Shell\Output;
use Cranberry\Shell\Autoloader;

class Application
{
	/**
	 * @var	array
	 */
	protected $errorMiddlewareQueue=[];

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

		/* --version */
		$this->pushMiddleware( new Middleware\Middleware( function( $input, &$output )
		{
			if( $input->hasOption( 'version' ) )
			{
				$version = sprintf( '%s version %s', $this->getName(), $this->getVersion() );
				$output->write( $version . PHP_EOL );

				return Middleware\Middleware::EXIT;
			}
		}));
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
				foreach( $this->errorMiddlewareQueue as $errorMiddleware )
				{
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
