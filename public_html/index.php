<?php
/**
 * Main index
 *
 * @author Timo Tijhof, 2015
 * @license http://krinkle.mit-license.org/
 * @package wmf-tool-tour
 */

/**
 * Configuration
 * -------------------------------------------------
 */

// BaseTool
require_once __DIR__ . '/../vendor/autoload.php';

// Class for this tool
require_once __DIR__ . '/../class.php';
$tool = new TourTool();

// Local settings
#require_once __DIR__ . '/../config.php';

$kgBase = BaseTool::newFromArray( array(
	'displayTitle' => 'Wiki Tour',
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '0.1.0',
	'styles' => array(
		'https://tools-static.wmflabs.org/cdnjs/ajax/libs/bootstrap-table/1.8.1/bootstrap-table.min.css',
	),
	'scripts' => array(
		'https://tools-static.wmflabs.org/cdnjs/ajax/libs/bootstrap-table/1.8.1/bootstrap-table.min.js'
	)
) );
$kgBase->setSourceInfoGithub( 'Krinkle', 'wmf-tool-tour', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$tool->run();
$kgBase->flushMainOutput();
