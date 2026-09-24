<?php
/**
 * Integrate App Toolkit platform settings with Ads Manager.
 *
 * @package DirectoristAppToolkit
 */

namespace DirectoristAppToolkit\Controller\Ads_Manager;

defined( 'ABSPATH' ) || exit;

class Init {

    const PLATFORM_META_KEY = 'swbdpam_platform';

    public function __construct() {
        add_action( 'directorist_ads_manager_after_ad_type_fields', array( $this, 'render_platform_field' ) );
        add_action( 'save_post', array( $this, 'save_platform' ) );
        add_filter( 'directorist_ads_manager_ads_query_args', array( $this, 'filter_ads_query' ), 10, 3 );
        add_filter( 'directorist_ads_manager_shortcode_ad_allowed', array( $this, 'filter_shortcode_ad' ), 10, 2 );
        add_filter( 'directorist_ads_manager_rest_ad_data', array( $this, 'add_rest_platform' ), 10, 2 );
    }

    /**
     * Add Platform to the Type of Ad metabox.
     *
     * @param \WP_Post $post Current ad.
     */
    public function render_platform_field( $post ) {
        $platform = get_post_meta( $post->ID, self::PLATFORM_META_KEY, true );
        if ( ! in_array( $platform, array( 'web', 'mobile', 'all' ), true ) ) {
            $platform = 'all';
        }

        $options = array(
            'web'    => __( 'Web', 'directorist-app-toolkit' ),
            'mobile' => __( 'Mobile', 'directorist-app-toolkit' ),
            'all'    => __( 'All', 'directorist-app-toolkit' ),
        );
        ?>
        <div class="swbdpam-ad-type-content-wrapper">
            <div class="swbdpam-ad-type-image">
                <div class="swbdpam-form-group">
                    <div class="swbdpam-form-group__label">
                        <span><?php esc_html_e( 'Platform', 'directorist-app-toolkit' ); ?></span>
                    </div>
                    <div class="swbdpam-form-group__elm">
                        <div class="radio-group">
                            <?php foreach ( $options as $value => $label ) : ?>
                                <div class="radio-elm swbdpam-custom-radio-cont">
                                    <input type="radio" name="swbdpam_platform" id="swbdpam-platform-<?php echo esc_attr( $value ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $platform, $value ); ?>>
                                    <label for="swbdpam-platform-<?php echo esc_attr( $value ); ?>"><span class="swbdpam-custom-radio"></span><?php echo esc_html( $label ); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save the field with Ads Manager's Type of Ad nonce and permissions.
     *
     * @param int $post_id Ad ID.
     */
    public function save_platform( $post_id ) {
        if ( ! defined( 'SWBDPAM_POST_TYPE' ) || SWBDPAM_POST_TYPE !== get_post_type( $post_id ) ) {
            return;
        }

        if ( ! class_exists( 'SWBDPAMHelperFunctions' ) || ! \SWBDPAMHelperFunctions::is_secured( 'swbdpam_ad_types_cmb_field', 'swbdpam_ad_types_cmb_action', $post_id ) ) {
            return;
        }

        $platform = isset( $_POST['swbdpam_platform'] ) && is_string( $_POST['swbdpam_platform'] )
            ? sanitize_key( wp_unslash( $_POST['swbdpam_platform'] ) )
            : 'all';
        if ( ! in_array( $platform, array( 'web', 'mobile', 'all' ), true ) ) {
            $platform = 'all';
        }

        update_post_meta( $post_id, self::PLATFORM_META_KEY, $platform );
    }

    /**
     * Narrow front-end ad queries and optionally the public REST collection.
     *
     * @param array                 $args    WP_Query arguments.
     * @param string                $context Query source.
     * @param \WP_REST_Request|null $request REST request, when applicable.
     * @return array
     */
    public function filter_ads_query( $args, $context, $request ) {
        $web_contexts = array( 'placement', 'widget', 'widget_selection' );

        if ( in_array( $context, $web_contexts, true ) ) {
            return $this->add_platform_query( $args, 'web', true );
        }

        if ( 'rest' !== $context || ! $request instanceof \WP_REST_Request ) {
            return $args;
        }

        $platform = $request->get_param( 'platform' );
        if ( 'web' === $platform ) {
            return $this->add_platform_query( $args, 'web', true );
        }

        if ( 'mobile' === $platform ) {
            return $this->add_platform_query( $args, 'mobile', false );
        }

        return $args;
    }

    /**
     * Shortcodes use an ad ID directly, so filter that candidate before rendering.
     *
     * @param bool $allowed Current result.
     * @param int  $ad_id   Ad ID.
     * @return bool
     */
    public function filter_shortcode_ad( $allowed, $ad_id ) {
        if ( ! $allowed ) {
            return false;
        }

        return in_array( get_post_meta( $ad_id, self::PLATFORM_META_KEY, true ), array( 'web', 'all', '' ), true );
    }

    /**
     * Expose the saved value; an empty string identifies a legacy ad.
     *
     * @param array    $data Response item.
     * @param \WP_Post $ad   Ad post.
     * @return array
     */
    public function add_rest_platform( $data, $ad ) {
        $platform = get_post_meta( $ad->ID, self::PLATFORM_META_KEY, true );
        $data['platform'] = in_array( $platform, array( 'web', 'mobile', 'all' ), true ) ? $platform : '';
        return $data;
    }

    /**
     * Add a platform group without replacing existing page or placement clauses.
     *
     * @param array  $args           WP_Query arguments.
     * @param string $platform       Requested platform.
     * @param bool   $include_legacy Include ads without a saved platform.
     * @return array
     */
    private function add_platform_query( $args, $platform, $include_legacy ) {
        $platform_query = array(
            'relation' => 'OR',
            array(
                'key'   => self::PLATFORM_META_KEY,
                'value' => $platform,
            ),
            array(
                'key'   => self::PLATFORM_META_KEY,
                'value' => 'all',
            ),
        );

        if ( $include_legacy ) {
            $platform_query[] = array(
                'key'     => self::PLATFORM_META_KEY,
                'compare' => 'NOT EXISTS',
            );
            $platform_query[] = array(
                'key'   => self::PLATFORM_META_KEY,
                'value' => '',
            );
        }

        if ( empty( $args['meta_query'] ) ) {
            $args['meta_query'] = $platform_query;
        } else {
            $args['meta_query'] = array(
                'relation' => 'AND',
                $args['meta_query'],
                $platform_query,
            );
        }

        return $args;
    }
}
