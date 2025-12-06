<?php
namespace Bookster\Controllers;

use Bookster\Features\Auth\RestAuth;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Services\SettingsService;
use Bookster\Services\EmailSettingsService;
use Bookster\Services\EmailService;
use Bookster\Engine\BEPages\IntroPage;

/**
 * Settings Controller
 *
 * @method static SettingsController get_instance()
 */
class SettingsController extends BaseRestController {
    use SingletonTrait;

    /** @var SettingsService */
    private $settings_service;
    /** @var EmailSettingsService */
    private $email_settings_service;
    /** @var EmailService */
    private $email_service;

    protected function __construct() {
        $this->settings_service       = SettingsService::get_instance();
        $this->email_settings_service = EmailSettingsService::get_instance();
        $this->email_service          = EmailService::get_instance();
        $this->init_hooks();
    }

    protected function init_hooks() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/general',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_general_settings' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/schedule',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_schedule_settings' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/holidays',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_holidays_settings' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/permissions',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_permissions_settings' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/payment',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_payment_settings' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/intro',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_intro_state' ],
                    'permission_callback' => [ IntroPage::class, 'require_caps_for_intro' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/customize',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_customize_settings' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/email-general-options',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_email_general_options' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/email-notice-options',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_email_notice_options' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/email-notice-options/enabled',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_email_notice_enabled' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            '/settings/send-preview-email',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_send_preview_email' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_settings_cap' ],
                ],
            ]
        );
    }

    public function patch_general_settings( \WP_REST_Request $request ) {
        $json_params = $request->get_json_params();
        $this->settings_service->update_general_settings( $json_params['general'] );
        do_action( 'bookster_update_general_settings', $json_params );
    }

    public function patch_schedule_settings( \WP_REST_Request $request ) {
        $json_params = $request->get_json_params();
        $this->settings_service->update_schedule_settings( $json_params['schedule'] );
        do_action( 'bookster_update_schedule_settings', $json_params );
    }

    public function patch_holidays_settings( \WP_REST_Request $request ) {
        $json_params = $request->get_json_params();
        $this->settings_service->update_holidays_settings( $json_params['holidays'] );
        do_action( 'bookster_update_holidays_settings', $json_params );
    }

    public function patch_permissions_settings( \WP_REST_Request $request ) {
        $json_params = $request->get_json_params();
        $this->settings_service->update_permissions_settings( $json_params['permissions'] );
        do_action( 'bookster_update_permissions_settings', $json_params );
    }

    public function patch_payment_settings( \WP_REST_Request $request ) {
        $json_params = $request->get_json_params();
        $this->settings_service->update_payment_settings( $json_params['payment'] );
        do_action( 'bookster_update_payment_settings', $json_params );
    }

    public function patch_intro_state( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        $this->settings_service->update_intro_state( $args );
    }

    public function patch_customize_settings( \WP_REST_Request $request ) {
        $json_params = $request->get_json_params();
        $this->settings_service->update_customize_settings( $json_params['customize'] );
        do_action( 'bookster_update_customize_settings', $json_params );
    }

    public function patch_email_general_options( \WP_REST_Request $request ) {
        $json_params = $request->get_json_params();
        $this->email_settings_service->update_general_options( $json_params['general_options'] );
        do_action( 'bookster_update_email_general_options', $json_params );
    }

    public function patch_email_notice_options( \WP_REST_Request $request ) {
        $json_params   = $request->get_json_params();
        $manager_email = isset( $json_params['manager_email'] ) ? $json_params['manager_email'] : null;
        $this->email_settings_service->update_notice_options( $json_params['notice_event'], $json_params['notice_options'], $manager_email );
        do_action( 'bookster_update_email_notice_options', $json_params );
    }

    public function patch_email_notice_enabled( \WP_REST_Request $request ) {
        $json_params = $request->get_json_params();
        $this->email_settings_service->update_notice_enabled( $json_params['notice_event'], $json_params['enabled'] );
    }

    public function send_preview_email( \WP_REST_Request $request ) {
        $json_params     = $request->get_json_params();
        $general_options = isset( $json_params['general_options'] ) ? $json_params['general_options'] : null;
        return $this->email_service->send_preview_email( $json_params['recipient'], $json_params['template_options'], $general_options );
    }

    public function exec_patch_general_settings( $request ) {
        return $this->exec_write( [ $this, 'patch_general_settings' ], $request );
    }

    public function exec_patch_schedule_settings( $request ) {
        return $this->exec_write( [ $this, 'patch_schedule_settings' ], $request );
    }

    public function exec_patch_permissions_settings( $request ) {
        return $this->exec_write( [ $this, 'patch_permissions_settings' ], $request );
    }

    public function exec_patch_payment_settings( $request ) {
        return $this->exec_write( [ $this, 'patch_payment_settings' ], $request );
    }

    public function exec_patch_holidays_settings( $request ) {
        return $this->exec_write( [ $this, 'patch_holidays_settings' ], $request );
    }

    public function exec_patch_intro_state( $request ) {
        return $this->exec_write( [ $this, 'patch_intro_state' ], $request );
    }

    public function exec_patch_customize_settings( $request ) {
        return $this->exec_write( [ $this, 'patch_customize_settings' ], $request );
    }

    public function exec_patch_email_general_options( $request ) {
        return $this->exec_write( [ $this, 'patch_email_general_options' ], $request );
    }
    public function exec_patch_email_notice_options( $request ) {
        return $this->exec_write( [ $this, 'patch_email_notice_options' ], $request );
    }
    public function exec_patch_email_notice_enabled( $request ) {
        return $this->exec_write( [ $this, 'patch_email_notice_enabled' ], $request );
    }
    public function exec_send_preview_email( $request ) {
        return $this->exec_read( [ $this, 'send_preview_email' ], $request );
    }
}
