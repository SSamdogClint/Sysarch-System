<?php
// controllers/reservation/reservation_helpers.php

function autoCancelLateReservations(mysqli $conn): void
{
    /*
      Auto-cancel approved reservations if the student is 15 minutes late.

      Example:
      Reservation start: 02:00 PM
      Grace period: 15 minutes
      Auto cancel: 02:16 PM onward

      It only affects approved reservations.
      If the reservation is already done, rejected, or cancelled, it will not be touched.
    */

    $stmt = $conn->prepare("
        UPDATE lab_reservations
        SET status = 'cancelled'
        WHERE status = 'approved'
          AND NOW() > DATE_ADD(
            TIMESTAMP(reservation_date, reservation_time),
            INTERVAL 1 MINUTE
          )
    ");

    $stmt->execute();
    $stmt->close();
}