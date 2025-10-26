<?php
namespace ebi\flow;

class HttpStatus{
	/**
	 * 400 bad request
	 */
	public function bad_request(): void{
		\ebi\HttpHeader::send_status(400);
	}
	/**
	 * 403 forbidden
	 */
	public function forbidden(): void{
		\ebi\HttpHeader::send_status(403);
	}
	/**
	 * 404 not found
	 */
	public function not_found(): void{
		\ebi\HttpHeader::send_status(404);
	}
	/**
	 * 405 method not allowed
	 */
	public function method_not_allowed(): void{
		\ebi\HttpHeader::send_status(405);
	}
	/**
	 * 406 not acceptable
	 */
	public function not_acceptable(): void{
		\ebi\HttpHeader::send_status(406);
	}
	/**
	 * 409 conflict
	 */
	public function conflict(): void{
		\ebi\HttpHeader::send_status(409);
	}
	/**
	 * 410 gone
	 */
	public function gone(): void{
		\ebi\HttpHeader::send_status(410);
	}
	/**
	 * 415 unsupported media type
	 */
	public function unsupported_media_type(): void{
		\ebi\HttpHeader::send_status(415);
	}
	/**
	 * 500 internal server error
	 */
	public function internal_server_error(): void{
		\ebi\HttpHeader::send_status(500);
	}
	/**
	 * 503 service unavailable
	 */
	public function service_unavailable(): void{
		\ebi\HttpHeader::send_status(503);
	}	
}
