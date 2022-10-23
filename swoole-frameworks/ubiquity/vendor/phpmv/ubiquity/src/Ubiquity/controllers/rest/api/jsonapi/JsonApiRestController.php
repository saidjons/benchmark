<?php

/**
 * JsonApi implementation
 */
namespace Ubiquity\controllers\rest\api\jsonapi;


use Ubiquity\controllers\rest\formatters\JsonApiRequestFormatter;
use Ubiquity\controllers\rest\formatters\RequestFormatter;
use Ubiquity\controllers\rest\RestBaseController;
use Ubiquity\controllers\rest\RestServer;
use Ubiquity\controllers\rest\traits\DynamicResourceTrait;

/**
 * Rest JsonAPI implementation.
 * Ubiquity\controllers\rest\api\jsonapi$JsonApiRestController
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.1.3
 * @since Ubiquity 2.0.11
 *
 */
abstract class JsonApiRestController extends RestBaseController {
use DynamicResourceTrait;
	const API_VERSION = 'JsonAPI 1.0';

	/**
	 *
	 * @return RestServer
	 */
	protected function getRestServer(): RestServer {
		return new JsonApiRestServer ( $this->config );
	}

	protected function getRequestFormatter(): RequestFormatter {
		return new JsonApiRequestFormatter();
	}

	/**
	 * Returns the api version.
	 *
	 * @return string
	 */
	public static function _getApiVersion() {
		return self::API_VERSION;
	}

	/**
	 * Returns the template for creating this type of controller
	 *
	 * @return string
	 */
	public static function _getTemplateFile() {
		return 'restApiController.tpl';
	}
}

