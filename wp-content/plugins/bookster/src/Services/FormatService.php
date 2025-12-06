<?php
namespace Bookster\Services;

use Bookster\Features\Utils\SingletonTrait;

/**
 * Format Service
 *
 * @method static FormatService get_instance()
 */
class FormatService extends BaseService {
    use SingletonTrait;

    /** @var SettingsService */
    private $settings_service;

    protected function __construct() {
        $this->settings_service = SettingsService::get_instance();
    }

    /**
     * Convert Dayjs Format to PHP Date Format
     */
    public function get_date_format() {
        $public_data = $this->settings_service->get_public_data();
        $date_format = $public_data['generalSettings']['date_format'];

        switch ( $date_format ) {
            case 'MM-DD-YYYY':
                return 'm-d-Y';
            case 'YYYY-MM-DD':
                return 'Y-m-d';
            case 'DD-MM-YYYY':
                return 'd-m-Y';
            case 'MMMM D, YYYY':
                return 'F j, Y';
            default:
                return 'm-d-Y';
        }
    }

    public function get_time_format() {
        $public_data = $this->settings_service->get_public_data();
        $time_system = $public_data['generalSettings']['time_system'];

        if ( '12h' === $time_system ) {
            return 'h:i A';
        }
        return 'H:i';
    }
}
