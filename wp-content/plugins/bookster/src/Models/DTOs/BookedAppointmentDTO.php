<?php
namespace Bookster\Models\DTOs;

use Bookster\Models\AppointmentModel;

/**
 * Booked Appointment DTO.
 * Hide several columns.
 */
class BookedAppointmentDTO extends AppointmentModel {

    const TABLE = 'bookster_appointments';

    protected $properties = [
        'appointment_id',
        'service_id',
        'location_id',

        'book_status',
        'datetime_start',
        'datetime_end',

        'utc_datetime_start',
        'abs_min_start',
        'abs_min_end',
        'buffer_before',
        'buffer_after',
        'busy_abs_min_start',
        'busy_abs_min_end',
        'busy_datetime_start',
        'busy_datetime_end',

        'agent_ids',
    ];
}
