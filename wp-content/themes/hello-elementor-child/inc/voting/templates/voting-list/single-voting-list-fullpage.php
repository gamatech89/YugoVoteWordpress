<?php
/**
 * Single Voting List - Fullpage Template
 * 
 * This template is used when ?fullpage=1 is in the URL
 * It provides a complete page layout for testing the new design
 * 
 * @package HelloElementorChild
 */

get_header();

// Set the voting list ID for the template
set_query_var('voting_list_id', get_the_ID());

// Include the fullpage template
get_template_part('inc/voting/templates/voting-list/voting-list-fullpage');

get_footer();
