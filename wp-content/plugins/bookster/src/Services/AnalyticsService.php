<?php
namespace Bookster\Services;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Models\AppointmentModel;
use Bookster\Models\ServiceModel;
use Bookster\Models\BookingModel;
use Bookster\Models\CustomerModel;
use Bookster\Models\AssignmentModel;
use Bookster\Models\AgentModel;

/**
 * AnalyticsService Service
 *
 * @method static AnalyticsService get_instance()
 */
class AnalyticsService extends BaseService {
    use SingletonTrait;

    /** @var AppointmentsService */
    private $appointments_service;
    /** @var CustomersService */
    private $customers_service;

    protected function __construct() {
        $this->appointments_service = AppointmentsService::get_instance();
        $this->customers_service    = CustomersService::get_instance();
    }

    public function query_performance_report( $from_datetime, $to_datetime, $agent_id ) {
        global $wpdb;
        $appt_tablename     = AppointmentModel::get_tablename();
        $booking_tablename  = BookingModel::get_tablename();
        $customer_tablename = CustomerModel::get_tablename();
        $assg_tablename     = AssignmentModel::get_tablename();

        $query =
        "SELECT
            COUNT(DISTINCT filtered.appointment_id) AS 'apptCount',
            COUNT(DISTINCT filtered.booking_id) AS 'bookingCount',
            COUNT(DISTINCT filtered.customer_id) AS 'customerCount',
            COALESCE(SUM(filtered.total_amount),0) AS 'revenue',
            (
                SELECT COUNT(DISTINCT customer_id)
                FROM $customer_tablename
                WHERE created_at BETWEEN %s AND %s
            ) AS 'newCustomerCount'
        FROM (
            SELECT appt.appointment_id, appt.service_id, appt.book_status, appt.datetime_start,
                booking.booking_id, booking.customer_id, booking.total_amount,
                (
                SELECT assignment.agent_id
                FROM $assg_tablename AS `assignment`
                WHERE appt.appointment_id = assignment.appointment_id
                LIMIT 1
                ) AS `agent_id`
            FROM $appt_tablename AS `appt`
            INNER JOIN $booking_tablename AS `booking`
                ON appt.appointment_id = booking.appointment_id
            WHERE appt.book_status IN ('pending', 'approved')
            AND appt.datetime_start BETWEEN %s AND %s
        ) AS filtered";
        $args  = [ $from_datetime, $to_datetime, $from_datetime, $to_datetime ];

        if ( null !== $agent_id ) {
            $query .= ' WHERE filtered.agent_id = %d';
            $args[] = $agent_id;
        }

        $prepared_query = $wpdb->prepare( $query, $args );
        $report         = $wpdb->get_row( $prepared_query, ARRAY_A );
        return $this->cast_performance_report( $report );
    }

    public function query_performance_intervals( $from_datetime, $to_datetime, $interval_step, $agent_id ) {
        global $wpdb;
        $appt_tablename    = AppointmentModel::get_tablename();
        $booking_tablename = BookingModel::get_tablename();
        $assg_tablename    = AssignmentModel::get_tablename();

        if ( 'hour' === $interval_step ) {
            $interval_select = "HOUR(filtered.datetime_start) AS 'intervalLabel', ";
        } else {
            $interval_select = "DATE(filtered.datetime_start) AS 'intervalLabel', ";
        }

        $query =
        "SELECT $interval_select
            COUNT(DISTINCT filtered.appointment_id) AS 'subtotalApptCount',
            COUNT(DISTINCT filtered.booking_id) AS 'subtotalBookingCount',
            COUNT(DISTINCT filtered.customer_id) AS 'subtotalCustomerCount',
            COALESCE(SUM(filtered.total_amount),0) AS 'subtotalRevenue'
        FROM (
            SELECT appt.appointment_id, appt.service_id, appt.book_status, appt.datetime_start,
                booking.booking_id, booking.customer_id, booking.total_amount,
                (
                SELECT assignment.agent_id
                FROM $assg_tablename AS `assignment`
                WHERE appt.appointment_id = assignment.appointment_id
                LIMIT 1
                ) AS `agent_id`
            FROM $appt_tablename AS `appt`
            INNER JOIN $booking_tablename AS `booking`
                ON appt.appointment_id = booking.appointment_id
            WHERE appt.book_status IN ('pending', 'approved')
            AND appt.datetime_start BETWEEN %s AND %s
        ) AS filtered";
        $args  = [ $from_datetime, $to_datetime ];

        if ( null !== $agent_id ) {
            $query .= ' WHERE filtered.agent_id = %d';
            $args[] = $agent_id;
        }

        if ( 'hour' === $interval_step ) {
            $query .= ' GROUP BY HOUR(filtered.datetime_start) ORDER BY HOUR(filtered.datetime_start) ASC';
        } else {
            $query .= ' GROUP BY DATE(filtered.datetime_start) ORDER BY DATE(filtered.datetime_start) ASC';
        }

        $prepared_query = $wpdb->prepare( $query, $args );
        $intervals      = $wpdb->get_results( $prepared_query, ARRAY_A );
        return $this->cast_performance_intervals( $intervals, $from_datetime, $to_datetime, $interval_step );
    }

    public function query_leaderboard_agents( $from_datetime, $to_datetime, $prev_from_datetime, $prev_to_datetime ) {
        global $wpdb;
        $appt_tablename    = AppointmentModel::get_tablename();
        $booking_tablename = BookingModel::get_tablename();
        $assg_tablename    = AssignmentModel::get_tablename();
        $agent_tablename   = AgentModel::get_tablename();

        $query =
        "SELECT agent.agent_id as 'id', CONCAT(agent.first_name, ' ', agent.last_name) as 'name',
            COUNT(DISTINCT filtered.appointment_id) AS 'currentApptCount',
            COUNT(DISTINCT filtered.booking_id) AS 'currentBookingCount',
            COUNT(DISTINCT filtered.customer_id) AS 'currentCustomerCount',
            COALESCE(SUM(filtered.total_amount),0) AS 'currentRevenue',

            (
                SELECT JSON_OBJECT(
                    'apptCount', COUNT(DISTINCT prevAppt.appointment_id),
                    'bookingCount', COUNT(DISTINCT prevBooking.booking_id),
                    'customerCount', COUNT(DISTINCT prevBooking.customer_id),
                    'revenue', COALESCE(SUM(prevBooking.total_amount),0)
                )
                FROM $appt_tablename AS `prevAppt`
                INNER JOIN $booking_tablename AS `prevBooking`
                    ON prevAppt.appointment_id = prevBooking.appointment_id
                WHERE prevAppt.book_status IN ('pending', 'approved')
                    AND prevAppt.datetime_start BETWEEN  %s AND %s
                    AND EXISTS(
                        SELECT prevAssignment.agent_id
                        FROM $assg_tablename AS `prevAssignment`
                        WHERE prevAppt.appointment_id = prevAssignment.appointment_id
                        AND prevAssignment.agent_id = agent.agent_id
                    )
            ) AS 'previous'

        FROM (
            SELECT appt.appointment_id, appt.service_id, appt.book_status, appt.datetime_start,
                booking.booking_id, booking.customer_id, booking.total_amount,
                (
                SELECT assignment.agent_id
                FROM $assg_tablename AS `assignment`
                WHERE appt.appointment_id = assignment.appointment_id
                LIMIT 1
                ) AS `agent_id`
            FROM $appt_tablename AS `appt`
            INNER JOIN $booking_tablename AS `booking`
                ON appt.appointment_id = booking.appointment_id
            WHERE appt.book_status IN ('pending', 'approved')
            AND appt.datetime_start BETWEEN %s AND %s
        ) AS filtered
        INNER JOIN $agent_tablename AS `agent`
            ON filtered.agent_id = agent.agent_id
        GROUP BY agent.agent_id
        ORDER BY currentRevenue DESC";
        $args  = [ $prev_from_datetime, $prev_to_datetime, $from_datetime, $to_datetime ];

        $prepared_query   = $wpdb->prepare( $query, $args );
        $leaderboard_rows = $wpdb->get_results( $prepared_query, ARRAY_A );
        return $this->cast_leaderboard_report( $leaderboard_rows );
    }

    public function query_leaderboard_services( $from_datetime, $to_datetime, $prev_from_datetime, $prev_to_datetime ) {
        global $wpdb;
        $appt_tablename    = AppointmentModel::get_tablename();
        $booking_tablename = BookingModel::get_tablename();
        $assg_tablename    = AssignmentModel::get_tablename();
        $service_tablename = ServiceModel::get_tablename();

        $query =
        "SELECT service.service_id as 'id', service.name as 'name',
            COUNT(DISTINCT filtered.appointment_id) AS 'currentApptCount',
            COUNT(DISTINCT filtered.booking_id) AS 'currentBookingCount',
            COUNT(DISTINCT filtered.customer_id) AS 'currentCustomerCount',
            COALESCE(SUM(filtered.total_amount),0) AS 'currentRevenue',

            (
                SELECT JSON_OBJECT(
                    'apptCount', COUNT(DISTINCT prevAppt.appointment_id),
                    'bookingCount', COUNT(DISTINCT prevBooking.booking_id),
                    'customerCount', COUNT(DISTINCT prevBooking.customer_id),
                    'revenue', COALESCE(SUM(prevBooking.total_amount),0)
                )
                FROM $appt_tablename AS `prevAppt`
                INNER JOIN $booking_tablename AS `prevBooking`
                    ON prevAppt.appointment_id = prevBooking.appointment_id
                WHERE prevAppt.book_status IN ('pending', 'approved')
                    AND prevAppt.datetime_start BETWEEN  %s AND %s
                    AND prevAppt.service_id = service.service_id
            ) AS 'previous'

        FROM (
            SELECT appt.appointment_id, appt.service_id, appt.book_status, appt.datetime_start,
                booking.booking_id, booking.customer_id, booking.total_amount,
                (
                SELECT assignment.agent_id
                FROM $assg_tablename AS `assignment`
                WHERE appt.appointment_id = assignment.appointment_id
                LIMIT 1
                ) AS `agent_id`
            FROM $appt_tablename AS `appt`
            INNER JOIN $booking_tablename AS `booking`
                ON appt.appointment_id = booking.appointment_id
            WHERE appt.book_status IN ('pending', 'approved')
            AND appt.datetime_start BETWEEN %s AND %s
        ) AS filtered
        INNER JOIN $service_tablename AS `service`
            ON filtered.service_id = service.service_id
        GROUP BY service.service_id
        ORDER BY currentRevenue DESC";
        $args  = [ $prev_from_datetime, $prev_to_datetime, $from_datetime, $to_datetime ];

        $prepared_query   = $wpdb->prepare( $query, $args );
        $leaderboard_rows = $wpdb->get_results( $prepared_query, ARRAY_A );
        return $this->cast_leaderboard_report( $leaderboard_rows );
    }

    private function cast_performance_report( $report ) {
        $report['apptCount']        = (int) $report['apptCount'];
        $report['bookingCount']     = (int) $report['bookingCount'];
        $report['customerCount']    = (int) $report['customerCount'];
        $report['revenue']          = floatval( $report['revenue'] );
        $report['newCustomerCount'] = (int) $report['newCustomerCount'];
        return $report;
    }

    private function cast_performance_intervals( $intervals, $from_datetime, $to_datetime, $interval_step ) {
        foreach ( $intervals as $index => $interval ) {
            if ( 'hour' === $interval_step ) {
                $intervals[ $index ]['intervalLabel'] = (int) $interval['intervalLabel'];
            }
            $intervals[ $index ]['subtotalApptCount']     = (int) $interval['subtotalApptCount'];
            $intervals[ $index ]['subtotalBookingCount']  = (int) $interval['subtotalBookingCount'];
            $intervals[ $index ]['subtotalCustomerCount'] = (int) $interval['subtotalCustomerCount'];
            $intervals[ $index ]['subtotalRevenue']       = floatval( $interval['subtotalRevenue'] );
        }

        $filled_intervals = [];
        if ( 'hour' === $interval_step ) {
            // fill 24 hours
            for ( $i = 0; $i < 24; $i++ ) {
                $interval_label = $i;
                $found          = false;
                foreach ( $intervals as $interval ) {
                    if ( $interval_label === $interval['intervalLabel'] ) {
                        $filled_intervals[] = $interval;
                        $found              = true;
                        break;
                    }
                }
                if ( ! $found ) {
                    $filled_intervals[] = [
                        'intervalLabel'         => $interval_label,
                        'subtotalApptCount'     => 0,
                        'subtotalBookingCount'  => 0,
                        'subtotalCustomerCount' => 0,
                        'subtotalRevenue'       => 0.0,
                    ];
                }
            }//end for
        } else {
            // fill each date
            $from_date    = new \DateTime( $from_datetime );
            $to_date      = new \DateTime( $to_datetime );
            $day_interval = new \DateInterval( 'P1D' );
            $period       = new \DatePeriod( $from_date, $day_interval, $to_date );
            foreach ( $period as $date ) {
                $interval_label = $date->format( 'Y-m-d' );
                $found          = false;
                foreach ( $intervals as $interval ) {
                    if ( $interval_label === $interval['intervalLabel'] ) {
                        $filled_intervals[] = $interval;
                        $found              = true;
                        break;
                    }
                }
                if ( ! $found ) {
                    $filled_intervals[] = [
                        'intervalLabel'         => $interval_label,
                        'subtotalApptCount'     => 0,
                        'subtotalBookingCount'  => 0,
                        'subtotalCustomerCount' => 0,
                        'subtotalRevenue'       => 0.0,
                    ];
                }
            }//end foreach
        }//end if

        return $filled_intervals;
    }

    private function cast_leaderboard_report( $leaderboard_rows ) {
        foreach ( $leaderboard_rows as $index => $leaderboard_row ) {
            $leaderboard_rows[ $index ]['id']                   = (int) $leaderboard_row['id'];
            $leaderboard_rows[ $index ]['currentApptCount']     = (int) $leaderboard_row['currentApptCount'];
            $leaderboard_rows[ $index ]['currentBookingCount']  = (int) $leaderboard_row['currentBookingCount'];
            $leaderboard_rows[ $index ]['currentCustomerCount'] = (int) $leaderboard_row['currentCustomerCount'];
            $leaderboard_rows[ $index ]['currentRevenue']       = floatval( $leaderboard_row['currentRevenue'] );
            $leaderboard_rows[ $index ]['previous']             = json_decode( $leaderboard_row['previous'], true );
        }

        return $leaderboard_rows;
    }
}
