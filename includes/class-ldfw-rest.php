<?php
/**
 * REST API: voting, feedback, and counts on posts.
 *
 * @package LikeDislike
 */

defined( 'ABSPATH' ) || exit;

/**
 * Routes under like-dislike-for-wp/v1.
 *
 * Voting works without a nonce for visitors, so it keeps working on cached pages;
 * each visitor is limited to one vote per item by LDFW_Votes. Logged-in users send the
 * usual REST nonce so their vote counts as theirs.
 */
class LDFW_REST {

	const NS = 'like-dislike-for-wp/v1';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Registers the routes and the counts field on posts.
	 */
	public static function routes() {
		$item = array(
			'type' => array(
				'type'     => 'string',
				'enum'     => array( 'post', 'comment' ),
				'required' => true,
			),
			'id'   => array(
				'type'     => 'integer',
				'minimum'  => 1,
				'required' => true,
			),
		);

		register_rest_route(
			self::NS,
			'/vote',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'vote' ),
				'permission_callback' => '__return_true',
				'args'                => $item + array(
					'choice' => array(
						'type'     => 'string',
						'enum'     => array( 'like', 'dislike' ),
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/feedback',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'feedback' ),
				'permission_callback' => '__return_true',
				'args'                => $item + array(
					'text' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		foreach ( (array) LDFW_Settings::get( 'post_types' ) as $post_type ) {
			register_rest_field(
				$post_type,
				'like_dislike',
				array(
					'get_callback' => function ( $post ) {
						$counts = LDFW_Votes::counts( 'post', (int) $post['id'] );
						return array(
							'likes'    => $counts['like'],
							'dislikes' => $counts['dislike'],
						);
					},
					'schema'       => array(
						'description' => __( 'Like and dislike counts.', 'like-dislike-for-wp' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'likes'    => array( 'type' => 'integer' ),
							'dislikes' => array( 'type' => 'integer' ),
						),
					),
				)
			);
		}
	}

	/**
	 * POST /vote — likes, dislikes, switches or takes back a vote.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function vote( WP_REST_Request $request ) {
		$type = (string) $request['type'];
		$id   = (int) $request['id'];

		$ok = LDFW_Votes::can_vote( $type, $id );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		$result = LDFW_Votes::vote( $type, $id, (string) $request['choice'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = rest_ensure_response(
			array(
				'state'    => $result['state'],
				'likes'    => $result['counts']['like'],
				'dislikes' => $result['counts']['dislike'],
			)
		);
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	/**
	 * POST /feedback — what could be better, after a dislike.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public static function feedback( WP_REST_Request $request ) {
		$type = (string) $request['type'];
		$id   = (int) $request['id'];

		if ( ! LDFW_Settings::get( 'feedback' ) ) {
			return new WP_Error( 'ldfw_feedback_off', __( 'Feedback is turned off on this site.', 'like-dislike-for-wp' ), array( 'status' => 403 ) );
		}
		$ok = LDFW_Votes::can_vote( $type, $id );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		$text = trim( sanitize_textarea_field( (string) $request['text'] ) );
		$text = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 1000 ) : substr( $text, 0, 1000 );
		if ( '' === $text ) {
			return new WP_Error( 'ldfw_feedback_empty', __( 'Please write a few words first.', 'like-dislike-for-wp' ), array( 'status' => 400 ) );
		}

		$saved = LDFW_Votes::feedback( $type, $id, $text );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return array( 'ok' => true );
	}
}
