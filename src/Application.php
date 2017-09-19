<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

use Cranberry\Shell\Input;
use Cranberry\Shell\Output;
use Cranberry\Shell\Autoloader;

class Application
{
	/**
	 * @var	Cranberry\Shell\Input\InputInterface
	 */
	protected $input;

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
	 * Process middleware queue
	 *
	 * @return	void
	 */
	public function run()
	{
		foreach( $this->middlewareQueue as $middleware )
		{
			$returnValue = $middleware->run( $this->input, $this->output );

			if( $returnValue == Middleware\MiddlewareInterface::EXIT )
			{
				break;
			}
		}
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
