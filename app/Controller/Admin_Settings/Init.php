<?php

namespace DirectoristAppToolkit\Controller\Admin_Settings;

use DirectoristAppToolkit\Helper\Traits as HelperTraits;


class Init {

    use HelperTraits\Service_Registrar;

    public function __construct() {
        add_action( 'atbdp_listing_type_settings_field_list', [ $this, 'add_menu_fields' ]);
        add_action( 'atbdp_extension_settings_submenu', [ $this, 'add_app_settings_menu_page' ]);
    }


    public function add_menu_fields( $fields = [] ) {
        // Color Section Fields
        $fields['app_primary_color'] = [
            'label' => __('Primary Color', 'directorist'),
            'type'  => 'color',
            'value' => '#000000',
        ];

        $fields['app_accent_color'] = [
            'label' => __('Accent Color', 'directorist'),
            'type'  => 'color',
            'value' => '#FFFFFF',
        ];

        // Banner Section Fields
        $fields['app_banner_title'] = [
            'label' => __('Banner Title', 'directorist'),
            'type'  => 'text',
            'value' => 'Explore anything',
        ];

        $fields['app_banner_subtitle'] = [
            'label' => __('Banner Subtitle', 'directorist'),
            'type'  => 'text',
            'value' => 'Find the best match of your interest',
        ];

        $fields['app_banner_thumbnail'] = [
            'label'       => __('Banner Thumbnail', 'directorist'),
            'type'        => 'wp-media-picker',
            'value'       => '',
            'default-img' => DIRECTORIST_ASSETS . 'images/grid.jpg',
        ];

        return $fields;
    }

    public function add_app_settings_menu_page( $layout = [] ) {
        // Settings Sections
        $settings_sections = [];

        // Color Settings Section
        $settings_sections['color_settings'] = [
            'title' => __('Color Settings', 'directorist'),
            'fields' =>  apply_filters( 'directorist_app_color_settings_fields', [
                'app_primary_color', 
                'app_accent_color'
            ]),
        ];

        // Banner Settings Section
        $settings_sections['banner_settings'] = [
            'title' => __('Banner Settings', 'directorist'),
            'fields' =>  apply_filters( 'directorist_app_banner_settings_fields', [
                'app_banner_title', 
                'app_banner_subtitle',
                'app_banner_thumbnail'
            ]),
        ];


        // App Settings Menu Page
        $layout['directorist_app_settings'] = [
            'label'    => __('App Settings', 'directorist'),
            'icon'     => '<i class="fa fa-mobile"></i>',
            'sections' => apply_filters( 'directorist_app_settings_sections', $settings_sections ),
        ];

        return $layout;
    }


}
