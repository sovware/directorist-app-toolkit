<?php

namespace DirectoristAppToolkit\Controller\Admin_Settings;


class AppSettings {

    public function __construct() {
        add_action( 'atbdp_listing_type_settings_field_list', [ $this, 'add_menu_fields' ]);
        add_action( 'atbdp_extension_settings_submenu', [ $this, 'add_app_settings_menu_page' ]);
    }


    public function add_menu_fields( $fields = [] ) {
        // Firebase Credentials Fields
        $fields['app_firebase_project_id'] = [
            'label' => __('Project ID', 'directorist'),
            'type'  => 'text',
            'value' => '',
        ];
        $fields['app_firebase_authorization_key'] = [
            'label' => __('Authorization Key', 'directorist'),
            'type'  => 'text',
            'value' => '',
        ];
        
        // Color Section Fields
        $fields['app_primary_color'] = [
            'label' => __('Primary Color', 'directorist'),
            'type'  => 'color',
            'value' => '#000000',
        ];

        // Banner Section Fields
        $fields['app_home_banner_title'] = [
            'label' => __('Banner Title', 'directorist'),
            'type'  => 'text',
            'value' => 'Explore anything',
        ];

        $fields['app_home_banner_subtitle'] = [
            'label' => __('Banner Subtitle', 'directorist'),
            'type'  => 'text',
            'value' => 'Find the best match of your interest',
        ];

        $fields['app_home_banner_thumbnail'] = [
            'label'       => __('Banner Thumbnail', 'directorist'),
            'type'        => 'wp-media-picker',
            'value'       => '',
            'default-img' => DIRECTORIST_ASSETS . 'images/grid.jpg',
        ];

        // Label Section Fields
        $fields['app_signin_greetings_title'] = [
            'label' => __('Signin Greetings Title', 'directorist'),
            'type'  => 'text',
            'value' => 'Hi There',
        ];
        $fields['app_signin_greetings_subtitle'] = [
            'label' => __('Signin Greetings Subtitle', 'directorist'),
            'type'  => 'text',
            'value' => 'Its\' good to see you',
        ];
        $fields['app_signup_greetings_title'] = [
            'label' => __('Signup Greetings Title', 'directorist'),
            'type'  => 'text',
            'value' => 'Wellcome to Directorist',
        ];
        $fields['app_signup_greetings_subtitle'] = [
            'label' => __('Signup Greetings Subtitle', 'directorist'),
            'type'  => 'text',
            'value' => 'Get started in less then 30 seconds',
        ];

        // Other Section Fields
        $fields['app_support_link'] = [
            'label' => __('Support Link', 'directorist'),
            'type'  => 'text',
            'value' => home_url(),
        ];

        return $fields;
    }

    public function add_app_settings_menu_page( $layout = [] ) {
        // Settings Sections
        $sections = [];

        // Firebase Credentials
        $sections['firebase_credentials'] = [
            'title' => __('Firebase Credentials', 'directorist-app-toolkit'),
            'fields' =>  apply_filters( 'directorist_app_firebase_credentials_fields', [
                'app_firebase_project_id',
                'app_firebase_authorization_key',
            ]),
        ];

        // Color Settings Section
        $sections['color_settings'] = [
            'title' => __('Color Settings', 'directorist'),
            'fields' =>  apply_filters( 'directorist_app_color_settings_fields', [
                'app_primary_color',
            ]),
        ];

        // Banner Settings Section
        $sections['banner_settings'] = [
            'title' => __('Banner Settings', 'directorist'),
            'fields' =>  apply_filters( 'directorist_app_banner_settings_fields', [
                'app_home_banner_title', 
                'app_home_banner_subtitle',
                'app_home_banner_thumbnail',
            ]),
        ];

        // Label Settings Section
        $sections['label_settings'] = [
            'title' => __('Label Settings', 'directorist'),
            'fields' =>  apply_filters( 'directorist_app_label_settings_fields', [
                'app_signin_greetings_title',
                'app_signin_greetings_subtitle',
                'app_signup_greetings_title',
                'app_signup_greetings_subtitle',
            ]),
        ];

        // Other Settings Section
        $sections['other_settings'] = [
            'title' => __('Other Settings', 'directorist'),
            'fields' =>  apply_filters( 'directorist_app_banner_settings_fields', [
                'app_support_link',
            ]),
        ];

        // App Settings Menu Page
        $layout['directorist_app_settings'] = [
            'label'    => __('App Settings', 'directorist'),
            'icon'     => '<i class="fa fa-mobile"></i>',
            'sections' => apply_filters( 'directorist_app_settings_sections', $sections ),
        ];

        return $layout;
    }


}
