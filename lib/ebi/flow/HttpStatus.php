<?php
namespace ebi\flow;

class HttpStatus{
	/**
	 * 401 unauthorized
	 */
	public function unauthorized(): void{
		\ebi\HttpHeader::send_status(401);
	}
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
	 * 421 misdirected request
	 */
	public function misdirected_request(): void{
		\ebi\HttpHeader::send_status(421);
	}
	/**
	 * 422 unprocessable content
	 */
	public function unprocessable_content(): void{
		\ebi\HttpHeader::send_status(422);
	}
	/**
	 * 425 too early
	 */
	public function too_early(): void{
		\ebi\HttpHeader::send_status(425);
	}
	/**
	 * 428 precondition required
	 */
	public function precondition_required(): void{
		\ebi\HttpHeader::send_status(428);
	}
	/**
	 * 429 too many requests
	 */
	public function too_many_requests(): void{
		\ebi\HttpHeader::send_status(429);
	}
	/**
	 * 431 request header fields too large
	 */
	public function request_header_fields_too_large(): void{
		\ebi\HttpHeader::send_status(431);
	}
	/**
	 * 451 unavailable for legal reasons
	 */
	public function unavailable_for_legal_reasons(): void{
		\ebi\HttpHeader::send_status(451);
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
	 * 502 bad gateway
	 */
	public function bad_gateway(): void{
		\ebi\HttpHeader::send_status(502);
	}
	/**
	 * 503 service unavailable
	 */
	public function service_unavailable(): void{
		\ebi\HttpHeader::send_status(503);
	}
	/**
	 * 504 gateway timeout
	 */
	public function gateway_timeout(): void{
		\ebi\HttpHeader::send_status(504);
	}
	/**
	 * 507 insufficient storage
	 */
	public function insufficient_storage(): void{
		\ebi\HttpHeader::send_status(507);
	}
	/**
	 * 511 network authentication required
	 */
	public function network_authentication_required(): void{
		\ebi\HttpHeader::send_status(511);
	}
}
