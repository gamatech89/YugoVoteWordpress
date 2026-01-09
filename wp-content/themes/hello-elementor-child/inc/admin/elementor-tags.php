<?php
// 1. Hook into Elementor to register tags
add_action( 'elementor/dynamic_tags/register', 'register_voting_custom_tags' );

function register_voting_custom_tags( $dynamic_tags_manager ) {

    // Define the Class for the Category Logo
    class Elementor_Voting_Cat_Logo_Tag extends \Elementor\Core\DynamicTags\Data_Tag {

        public function get_name() {
            return 'voting-category-logo';
        }

        public function get_title() {
            return 'Voting Category Logo';
        }

        public function get_group() {
            return 'voting-vars'; // We create a custom group below
        }

        public function get_categories() {
            // This allows the tag to show up in Image controls
            return [ \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY ];
        }

        protected function register_controls() {
            // No extra controls needed
        }

        public function get_value( array $options = [] ) {
            // 1. Get the current Term ID (works inside Loop Grid too)
            $term_id = get_queried_object_id();

            // 2. Fetch your custom meta (returns Attachment ID)
            $image_id = get_term_meta( $term_id, 'category_logo', true );

            if ( empty( $image_id ) ) {
                return [];
            }

            // 3. Return the array format Elementor expects for Images
            $src = wp_get_attachment_image_src( $image_id, 'full' );
            
            return [
                'id' => $image_id,
                'url' => $src[0],
            ];
        }
    }

    // Define the Class for Category List Count (Text Tag)
    class Elementor_Voting_Cat_Count_Tag extends \Elementor\Core\DynamicTags\Tag {
        public function get_name() { return 'voting-category-count'; }
        public function get_title() { return 'Voting Category Count'; }
        public function get_group() { return 'voting-vars'; }
        public function get_categories() { return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ]; }

        public function render() {
            $term = get_queried_object();
            if ( $term && isset( $term->count ) ) {
                echo $term->count . ' Lista';
            } else {
                echo '0';
            }
        }
    }

    // 2. Register the Group
    $dynamic_tags_manager->register_group(
        'voting-vars',
        [ 'title' => 'Voting System Data' ]
    );

    // 3. Register the Tags
    $dynamic_tags_manager->register( new Elementor_Voting_Cat_Logo_Tag() );
    $dynamic_tags_manager->register( new Elementor_Voting_Cat_Count_Tag() );
}
?>