<?php
/*
 * This file is part of Cranberry\Shell
 */

$projectDir = dirname( __DIR__ );

require_once( "{$projectDir}/src/Autoloader.php" );
Cranberry\Shell\Autoloader::register();
include_once( __DIR__ . '/fixtures/OutputStub.php' );
