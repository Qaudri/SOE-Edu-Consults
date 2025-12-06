<?php
namespace Bookster\Services;

use Bookster\Services\SettingsService;
use Bookster\Services\AgentsService;
use Bookster\Services\CustomersService;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Controllers\BaseRestController;
use Bookster\Engine\BEPages\AgentPage;
use Bookster\Engine\BEPages\ManagerPage;

/**
 * Localize Service
 *
 * @method static LocalizeService get_instance()
 */
class LocalizeService extends BaseService {
    use SingletonTrait;

    const VAR_PUBLIC_DATA  = 'booksterPublicData';
    const VAR_MANAGER_DATA = 'booksterManagerData';
    const VAR_META         = 'booksterMeta';
    const VAR_ADDONS       = 'booksterAddons';

    /** @var array save js variable name */
    private $localized = [];

    /** @var AuthService */
    private $auth_service;
    /** @var SettingsService */
    private $settings_service;
    /** @var AddonsService */
    private $addons_service;
    /** @var AgentsService */
    private $agents_service;
    /** @var CustomersService */
    private $customers_service;
    /** @var AppointmentsService */
    private $appointments_service;

    protected function __construct() {
        $this->auth_service         = AuthService::get_instance();
        $this->settings_service     = SettingsService::get_instance();
        $this->addons_service       = AddonsService::get_instance();
        $this->agents_service       = AgentsService::get_instance();
        $this->customers_service    = CustomersService::get_instance();
        $this->appointments_service = AppointmentsService::get_instance();
    }

    public function localize_public_data( $handle ) {
        if ( in_array( self::VAR_PUBLIC_DATA, $this->localized, true ) ) {
            return;
        }
        $this->localized = array_merge( $this->localized, [ self::VAR_PUBLIC_DATA ] );

        wp_localize_script(
            $handle,
            self::VAR_PUBLIC_DATA,
            $this->settings_service->get_public_data()
        );
    }

    public function localize_manager_data( $handle ) {
        if ( in_array( self::VAR_MANAGER_DATA, $this->localized, true ) ) {
            return;
        }
        $this->localized = array_merge( $this->localized, [ self::VAR_MANAGER_DATA ] );

        wp_localize_script(
            $handle,
            self::VAR_MANAGER_DATA,
            $this->settings_service->get_manager_data()
        );
    }

    public function localize_meta( $handle ) {
        if ( in_array( self::VAR_META, $this->localized, true ) ) {
            return;
        }
        $this->localized = array_merge( $this->localized, [ self::VAR_META ] );
        $agent_model     = $this->auth_service->get_agent_record_of_current_user();
        $customer_model  = $this->auth_service->get_customer_record_of_current_user();

        $customer_dashboard_page_id = $this->settings_service->get_permissions_settings()['customer_dashboard_page_id'];
        $bookster_meta              = [
            'restPath'     => [
                'root'  => esc_url_raw( rest_url() ),
                'base'  => BaseRestController::REST_NAMESPACE,
                'nonce' => wp_create_nonce( 'wp_rest' ),
            ],
            'wpPath'       => [
                'lossPasswordUrl' => wp_lostpassword_url(),
                'registerUrl'     => get_option( 'users_can_register' ) ? wp_registration_url() : null,
                'logoutUrl'       => str_replace( '&amp;', '&', wp_logout_url() ),
                'adminUrl'        => admin_url(),
                'managerAppUrl'   => admin_url( 'admin.php?page=' . ManagerPage::MENU_SLUG ),
                'agentAppUrl'     => admin_url( 'admin.php?page=' . AgentPage::MENU_SLUG ),
                'userProfileUrl'  => admin_url( 'user-edit.php' ),
                'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
                'siteUrl'         => site_url(),
            ],
            'appPath'      => [
                'bookster' => [
                    'base'   => BOOKSTER_PLUGIN_URL . 'assets/dist/bookster',
                    'assets' => BOOKSTER_PLUGIN_URL . 'assets',
                ],
            ],

            'auth'         => [
                'wpUserInfo'     => $this->auth_service->get_wp_user_info(),
                'agentRecord'    => null !== $agent_model ? $agent_model->to_array() : null,
                'customerRecord' => null !== $customer_model ? $customer_model->to_array() : null,
            ],
            'pages'        => [
                'customerDashboardUrl' => null !== $customer_dashboard_page_id ? get_permalink( $customer_dashboard_page_id ) : site_url(),
            ],
            'meta'         => [
                'siteTitle' => get_bloginfo( 'name' ),
                'isRtl'     => is_rtl(),
            ],
            'recordLabels' => $this->get_record_labels(),
            'features'     => $this->settings_service->get_core_features(),
        ];

        wp_localize_script(
            $handle,
            self::VAR_META,
            apply_filters( 'bookster_meta', $bookster_meta )
        );
    }
    public function get_record_labels() {
        $labels = [
            'service'   => __( 'Service', 'bookster' ),
            'services'  => __( 'Services', 'bookster' ),
            'agent'     => __( 'Agent', 'bookster' ),
            'agents'    => __( 'Agents', 'bookster' ),
            'customer'  => __( 'Customer', 'bookster' ),
            'customers' => __( 'Customers', 'bookster' ),
            'location'  => __( 'Location', 'bookster' ),
            'locations' => __( 'Locations', 'bookster' ),
        ];

        return apply_filters( 'bookster_record_labels', $labels );
    }

    public function localize_addons( $handle ) {
        if ( in_array( self::VAR_ADDONS, $this->localized, true ) ) {
            return;
        }
        $this->localized = array_merge( $this->localized, [ self::VAR_ADDONS ] );

        wp_localize_script(
            $handle,
            self::VAR_ADDONS,
            [
                'addonInfos' => $this->addons_service->get_addon_infos(),
            ]
        );
    }
}
