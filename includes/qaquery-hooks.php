<?php
/**
 * Question and answer query filters.
 *
 * @package AnsPress
 * @since 4.0.0
 */

/**
 * Query hooks
 */
class AP_QA_Query_Hooks {

	/**
	 * Alter WP_Query mysql query for question and answers.
	 *
	 * @param  array  $sql  Sql query.
	 * @param  Object $args Instance.
	 * @return array
	 */
	public static function sql_filter( $sql, $args ) {
		global $wpdb;

		if ( isset( $args->query['ap_query'] ) ) {
			$sql['join'] = $sql['join'] . " LEFT JOIN {$wpdb->ap_qameta} qameta ON qameta.post_id = {$wpdb->posts}.ID";
			$sql['fields'] = $sql['fields'] . ', qameta.*, qameta.votes_up - qameta.votes_down AS votes_net';
			$post_status = '';
			$query_status = $args->query['post_status'];

			// Build the post_status mysql query.
			if ( ! empty( $query_status ) ) {
				if ( is_array( $query_status ) ) {
					$i = 1;

					foreach ( get_post_stati() as $status ) {

						if ( in_array( $status, $args->query['post_status'], true ) ) {
							$post_status .= $wpdb->posts.".post_status = '" . $status . "'";

							if ( count( $query_status ) != $i ) {
								$post_status .= ' OR ';
							} else {
								$post_status .= ')';
							}
							$i++;
						}
					}
				} else {
					$post_status .= $wpdb->posts.".post_status = '".$query_status."' ";
				}
			}

			// Replace post_status query.
			if ( false !== ( $pos = strpos( $sql['where'], $post_status ) ) ) {
				$pos = $pos + strlen( $post_status );
				$author_query = $wpdb->prepare( " OR ( {$wpdb->posts}.post_author = %d AND {$wpdb->posts}.post_status NOT IN ('draft','trash','auto-draft','inherit') ) ", get_current_user_id() );
				$sql['where'] = substr_replace( $sql['where'], $author_query, $pos, 0 );
			}

			$ap_sortby = isset( $args->query['ap_sortby'] ) ? $args->query['ap_sortby'] : 'active';
			$answer_query = isset( $args->query['ap_answers_query'] );

			if ( 'answers' === $ap_sortby && ! $answer_query ) {
				$sql['orderby'] = 'IFNULL(qameta.answers, 0) DESC, ' . $sql['orderby'];
			} elseif ( 'views' === $ap_sortby && ! $answer_query ) {
				$sql['orderby'] = 'IFNULL(qameta.views, 0) DESC, ' . $sql['orderby'];
			} elseif ( 'unanswered' === $ap_sortby && ! $answer_query ) {
				$sql['orderby'] = 'IFNULL(qameta.answers, 0) ASC,' . $sql['orderby'];
			} elseif ( 'voted' === $ap_sortby ) {
				$sql['orderby'] = 'CASE WHEN IFNULL(votes_net, 0) >= 0 THEN 1 ELSE 2 END ASC, ABS(votes_net) DESC, ' . $sql['orderby'];
			} elseif ( 'unsolved' === $ap_sortby && ! $answer_query ) {
				$sql['orderby'] = "if( qameta.selected_id = '' or qameta.selected_id is null, 1, 0 ) ASC," . $sql['orderby'];
			} elseif ( 'oldest' === $ap_sortby ) {
				$sql['orderby'] = "{$wpdb->posts}.post_date ASC";
			} elseif ( 'newest' === $ap_sortby ) {
				$sql['orderby'] = "{$wpdb->posts}.post_date DESC";
			} else {
				$sql['orderby'] = 'qameta.last_updated DESC ';
			}
			// Keep featured posts on top.
			if ( ! $answer_query ) {
				$sql['orderby'] = 'CASE WHEN IFNULL(qameta.featured, 0) =1 THEN 1 ELSE 2 END ASC, ' . $sql['orderby'];
			}
			// Keep best answer to top.
			if ( $answer_query ) {
				$sql['orderby'] = 'qameta.selected <> 1 , ' . $sql['orderby'];
			}
		}

		return $sql;
	}

	/**
	 * Add qameta fields to post and prefetch metas and users.
	 *
	 * @param  array  $posts Post array.
	 * @param  object $instance QP_Query instance.
	 * @return array
	 */
	public static function posts_results( $posts, $instance ) {

		foreach ( (array) $posts as $k => $p ) {
			if ( in_array( $p->post_type, [ 'question', 'answer' ], true ) ) {
				// Convert object as array to prevent using __isset of WP_Post.
				$p_arr = (array) $p;
				foreach ( ap_qameta_fields() as $fields_name => $val ) {
					if ( ! isset( $p_arr[ $fields_name ] ) || empty( $p_arr[ $fields_name ] ) ) {
						$p->$fields_name = $val;
					}

					// Serialize fields and activities.
					$p->activities = maybe_unserialize( $p->activities );
					$p->fields = maybe_unserialize( $p->fields );

					$p->ap_qameta_wrapped = true;
					$p->votes_net = $p->votes_up - $p->votes_down;
					$posts[ $k ] = $p;
				}
			}
		}

		if ( isset( $instance->query['ap_question_query'] ) || isset( $instance->query['ap_answers_query'] ) ) {
			$instance->pre_fetch();
		}

		return $posts;
	}
}
