<?php
namespace Bookster\Services;

use Bookster\Features\Constants\SettingsData;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Auth\AuthFns;
use Bookster\Features\Auth\Caps;
use Bookster\Features\Auth\Roles;
use Bookster\Features\Enums\PaymentGatewayEnum;

/**
 * Settings Service
 *
 * @method static SettingsService get_instance()
 */
class SettingsService extends BaseService {
    use SingletonTrait;

    public const CORE_SETTINGS_GENERAL_OPTION     = 'bookster_core_settings_general';
    public const CORE_SETTINGS_SCHEDULE_OPTION    = 'bookster_core_settings_schedule';
    public const CORE_SETTINGS_PERMISSIONS_OPTION = 'bookster_core_settings_permissions';
    public const CORE_SETTINGS_HOLIDAYS_OPTION    = 'bookster_core_settings_holidays';
    public const CORE_SETTINGS_PAYMENT_OPTION     = 'bookster_core_settings_payment';
    public const CORE_SETTINGS_CUSTOMIZE_OPTION   = 'bookster_core_settings_customize';

    public const CORE_INTRO_STATE_OPTION = 'bookster_core_intro_state';

    private $public_data;
    private $manager_data;

    /** @var EmailSettingsService */
    private $email_settings_service;

    protected function __construct() {
        $this->email_settings_service = EmailSettingsService::get_instance();
    }

    private function load_data() {
        $general_settings                      = $this->get_general_settings();
        $schedule_settings                     = $this->get_schedule_settings();
        $holidays_settings                     = $this->get_holidays_settings();
        $permissions_settings                  = $this->get_permissions_settings();
        $payment_settings                      = $this->get_payment_settings();
        $customize_settings                    = $this->get_customize_settings();
        $customize_settings['content']['help'] = wp_kses_post( $customize_settings['content']['help'] );
        $email_settings                        = $this->email_settings_service->get_email_settings();

        $this->public_data = [
            'generalSettings'     => $general_settings,
            'scheduleSettings'    => $schedule_settings,
            'holidaysSettings'    => $holidays_settings,
            'permissionsSettings' => $permissions_settings,
            'paymentSettings'     => $payment_settings,
            'customizeSettings'   => $customize_settings,

            'paymentGateway'      => [
                'later' => true === $payment_settings['payment-later']['enabled'] ? PaymentGatewayEnum::LATER : null,
                'now'   => null,
            ],
        ];

        $this->manager_data = [
            'generalSettings'     => $general_settings,
            'scheduleSettings'    => $schedule_settings,
            'holidaysSettings'    => $holidays_settings,
            'permissionsSettings' => $permissions_settings,
            'paymentSettings'     => $payment_settings,
            'customizeSettings'   => $customize_settings,
            'emailSettings'       => $email_settings,
        ];

        $this->public_data  = apply_filters( 'bookster_public_data', $this->public_data );
        $this->manager_data = apply_filters( 'bookster_manager_data', $this->manager_data );
    }

    public function get_public_data() {
        if ( ! $this->public_data ) {
            $this->load_data();
        }

        return $this->public_data;
    }

    public function get_manager_data() {
        if ( ! $this->manager_data ) {
            $this->load_data();
        }

        return $this->manager_data;
    }

    /** Use filter, allow multiple addons to enable core features */
    public function get_core_features() {
        $features = [ 'displayFields' => false ];

        return apply_filters( 'bookster_core_features', $features );
    }

    public function update_general_settings( $settings ) {
        update_option( self::CORE_SETTINGS_GENERAL_OPTION, $settings );
    }

    public function update_schedule_settings( $settings ) {
        update_option( self::CORE_SETTINGS_SCHEDULE_OPTION, $settings );
    }

    public function update_permissions_settings( $settings ) {
        update_option( self::CORE_SETTINGS_PERMISSIONS_OPTION, $settings );
        AuthFns::update_role_caps( Roles::AGENT_ROLE, Caps::get_agent_caps() );
    }

    public function update_payment_settings( $settings ) {
        update_option( self::CORE_SETTINGS_PAYMENT_OPTION, $settings );
    }

    public function update_holidays_settings( $settings ) {
        update_option( self::CORE_SETTINGS_HOLIDAYS_OPTION, $settings );
    }

    public function update_customize_settings( $customize ) {
        update_option( self::CORE_SETTINGS_CUSTOMIZE_OPTION, $customize );
    }

    public function get_intro_state() {
        return get_option(
            self::CORE_INTRO_STATE_OPTION,
            [
                'skipped'   => false,
                'completed' => false,
            ]
        );
    }

    public function need_to_run_intro() {
        $intro_state = $this->get_intro_state();

        return ! $intro_state['skipped'] && ! $intro_state['completed'];
    }

    public function update_intro_state( $args ) {
        update_option(
            self::CORE_INTRO_STATE_OPTION,
            [
                'skipped'   => $args['skipped'] ?? false,
                'completed' => $args['completed'] ?? false,
            ]
        );
    }

    private function get_general_settings() {
        return array_replace_recursive(
            SettingsData::DEFAULT_GENERAL_SETTINGS,
            get_option( self::CORE_SETTINGS_GENERAL_OPTION, [] )
        );
    }

    private function get_schedule_settings() {
        $schedule_settings_option = get_option( self::CORE_SETTINGS_SCHEDULE_OPTION, [] );
        if ( count( $schedule_settings_option ) > 0 ) {
            return $schedule_settings_option;
        }
        return array_replace_recursive(
            [ 'weekly' => SettingsData::DEFAULT_WEEKLY_SETTINGS ],
            $schedule_settings_option
        );
    }

    public function get_permissions_settings() {
        return array_replace_recursive(
            SettingsData::DEFAULT_PERMISSION_SETTINGS,
            get_option( self::CORE_SETTINGS_PERMISSIONS_OPTION, [] )
        );
    }

    private function get_payment_settings() {
        return array_replace_recursive(
            SettingsData::DEFAULT_PAYMENT_SETTINGS,
            get_option( self::CORE_SETTINGS_PAYMENT_OPTION, [] )
        );
    }

    private function get_holidays_settings() {
        return array_replace_recursive(
            SettingsData::get_default_holidays_settings(),
            get_option( self::CORE_SETTINGS_HOLIDAYS_OPTION, [] )
        );
    }

    private function get_customize_settings() {
        return array_replace_recursive(
            SettingsData::get_default_customize_settings(),
            get_option( self::CORE_SETTINGS_CUSTOMIZE_OPTION, [] )
        );
    }
}
