<?php

declare(strict_types=1);

namespace QuadLayers\TTF\Api\Rest\Endpoints\Frontend\User_Video_List;

final class Test_Callback {
	/** @var mixed */
	public $response;

	/** @var int */
	public $requests = 0;

	/** @param mixed $response */
	public function __construct( $response ) {
		$this->response = $response;
	}

	/** @return mixed */
	public function callback( \WP_REST_Request $request ) {
		$this->requests++;

		return $this->response;
	}
}
