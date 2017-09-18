<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Output;

interface OutputInterface
{
	/**
	 * Write string to buffer
	 *
	 * @param	string	$string
	 *
	 * @return	void
	 */
	public function buffer( string $string );

	/**
	 * Flush the write buffer
	 *
	 * Attempt to push buffered output to the stream
	 *
	 * @return	void
	 */
	public function flush();

	/**
	 * Set the output stream
	 *
	 * @param	string	$protocol
	 *
	 * @param	string	$target
	 *
	 * @param	string	$mode
	 *
	 * @return	void
	 */
	public function setStream( string $protocol, string $target, string $mode='a' );

	/**
	 * Write string to stream
	 *
	 * @param	string	$string
	 *
	 * @return	void
	 */
	public function write( string $string );
}
