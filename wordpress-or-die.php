<?php
/*
Plugin Name: WordPress or Die
Description: Enables voting on posts similar to the functionality on Funny or Die.
Version: 0.1
Author: Daniel Immke
Author URI: http://daniel.do/
Author Email: me@daniel.do
License: GPLv2 or later

Copyright 2012  Daniel Immke  (email : me@daniel.do)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

class WordPress_Or_Die {

	function __construct() {

		// Add actions and hooks.
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_plugin_styles' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_plugin_scripts' ) );
		add_action('wp_ajax_add-vote', array( &$this, 'add_vote' ) );
		add_action('wp_ajax_nopriv_add-vote', array( &$this, 'add_vote' ) );

	    add_filter( 'the_content', array( &$this, 'create_vote_markup' ) );

	}

	// Add relevant stylesheet and javascript file.
	public function register_plugin_styles() {
		wp_register_style( 'wpordie-plugin-styles', plugins_url( 'wordpress-or-die/css/vote.css' ) );
		wp_enqueue_style( 'wpordie-plugin-styles' );
	}

	public function register_plugin_scripts() {
	
		wp_register_script( 'wpordie-vote-ajax', plugins_url( 'wordpress-or-die/js/vote.js' ), array( 'jquery' ) );
		wp_enqueue_script( 'wpordie-vote-ajax' );
		
		wp_localize_script('wpordie-vote-ajax', 'wpordie_var', 
			array(
				'url' 	=> admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'ajax-nonce' )
			)
		);
		
	}

	/**
	 * Generate markup for insertion into posts.
	 *
	 * @param 	$content	The content of the post.
	 * @returns				The modified post content.
	 */
	function create_vote_markup( $content ) {

		$post_id = get_the_ID();

		// Edit these to change text. Will be editable in options page.
		$vote_positive = "Good";
		$vote_negative = "Bad";

		$percentage = $this->calculate_percentage($post_id);

		// Only display a percentage if votes have been cast.
		if( isset( $percentage ) ) {
			
			$vote_markup = "<div id='polling-place' data-post-id='$post_id'><div id='polling-label'>Vote on this post</div><a href='#' id='vote-negative'>$vote_negative</a><div id='polling-percentage'><div id='percentage-filled' style='width: $percentage%;'><span>$percentage%</span></div></div><a href='#' id='vote-positive'>$vote_positive</a></div>";
		
		// If there are no votes, display empty elements so that jQuery can fill in from the first vote.
		} else {
			
			$vote_markup = "<div id='polling-place' data-post-id='$post_id'><div id='polling-label'>Vote on this post</div><a href='#' id='vote-negative'>$vote_negative</a><div id='polling-percentage'><div id='percentage-filled'><span></span></div></div><a href='#' id='vote-positive'>$vote_positive</a></div>";

		}
		
		// Check if it is a post before adding markup. Support for all content types is planned.
		if( is_single() ) {
			$content .= $vote_markup;
		}

		return $content;

	}

	/**
	 * Store a vote in either the positive or negative custom fields.
	 */
	function add_vote() {

		$nonce = $_POST['nonce'];

		$post_id = $_POST['post_id'];

		$vote_type = $_POST['vote_type'];
		
		$ip_address = $_SERVER['REMOTE_ADDR'];

		$already_voted = $this->already_voted( $post_id, $ip_address, $vote_type );

		// Authenticate with nonce.
		if ( false == wp_verify_nonce( $nonce, 'ajax-nonce' ) || true == $already_voted ) {
		
			echo "failed";
			exit;

		}

		// If the vote was positive, allocate to the positive custom field key.
		if( "vote-positive" == $vote_type ) {

			$votes_number = get_post_meta( $post_id, 'wpordie-positive-votes', true );
			if( ! $votes_number ) {

				add_post_meta( $post_id, 'wpordie-positive-votes', 1 );

			} else {

				update_post_meta( $post_id, 'wpordie-positive-votes', ++$votes_number );

			}

		}

		// And the same with negative votes.
		if( "vote-negative" == $vote_type ) {

			$votes_number = get_post_meta( $post_id, 'wpordie-negative-votes', true );
			if( ! $votes_number ) {

				add_post_meta( $post_id, 'wpordie-negative-votes', 1 );

			} else {

				update_post_meta( $post_id, 'wpordie-negative-votes', ++$votes_number );

			}

		}

		// After adding votes, call calculate_percentage() and send the updated percentage back to the browser.
		echo $this->calculate_percentage( $post_id );

		exit;

	}

	/**
	 * Of the percentage of people who voted, how many voted positively? Simple formula to derive our percentage.
	 *
	 * @param	$post_id	The ID of the post that we're rating
	 *
	 * @return				The percentage of the votes on the post.
	 */
	function calculate_percentage( $post_id ) {

		$votes_positive_number = get_post_meta( $post_id, 'wpordie-positive-votes', true );

		$votes_negative_number = get_post_meta( $post_id, 'wpordie-negative-votes', true );

		$votes_total = $votes_negative_number + $votes_positive_number;

		if( ! empty( $votes_total ) ) {

			// Get the percentage.
			$percentage = $votes_positive_number / ( $votes_total * 100 );

			// Round for simplicity.
			$percentage = round( $percentage, 0 );

			// Spit out an integer for use in other functions.
			return $percentage;
		}

	}

	/**
	 * Prevents voting more than once. Underscore prefix to hide these. Thanks to Tom Mcfarlin for help with this!
	 *
	 * @param	$post_id	The ID of the post that's being evaluated
	 * @param	$ip_address	The IP off the address being evaluated
	 * @param	$vote_type	How the person is voting
	 *
	 * @return				Whether or not the person has already voted
	 */
	function already_voted( $post_id, $ip_address, $vote_type ) {

		$already_voted = true;

		if( ! get_post_meta( $post_id, "_$ip_address", false ) ) {
			
			// Add their IP address and vote value.
			update_post_meta( $post_id, "_$ip_address", $vote_type );
			
			$already_voted = false;
			
		}
		
		return $already_voted;
	
	}
	
}

new WordPress_Or_Die();
?>