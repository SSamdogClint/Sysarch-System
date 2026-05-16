<?php
// controllers/reservation/reservation_helpers.php

require_once __DIR__ . '/../notifications/notification_helpers.php';

function autoCancelLateReservations(mysqli $conn): void
{
    /*
      Auto-cancel approved reservations if the student is 15 minutes late.

      This function also creates a student notification when a reservation is
      cancelled because of late arrival.
    */

    $lateReservations = [];

    $result = $conn->query("
        SELECT
            id,
            student_id,
            purpose,
            lab,
            pc_number,
            reservation_date,
            reservation_time,
            COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) AS reservation_end_time
        FROM lab_reservations
        WHERE status = 'approved'
          AND NOW() > DATE_ADD(
            TIMESTAMP(reservation_date, reservation_time),
            INTERVAL 15 MINUTE
          )
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lateReservations[] = $row;
        }
        $result->free();
    }

    if (empty($lateReservations)) {
        return;
    }

    $update = $conn->prepare("
        UPDATE lab_reservations
        SET status = 'cancelled'
        WHERE id = ? AND status = 'approved'
    ");

    if (!$update) {
        return;
    }

    foreach ($lateReservations as $reservation) {
        $reservationId = (int)$reservation['id'];
        $studentId = (int)$reservation['student_id'];

        $update->bind_param('i', $reservationId);
        $update->execute();

        // Notify only if the row was actually changed from approved to cancelled.
        if ($update->affected_rows > 0) {
            $dateLabel = date('M d, Y', strtotime($reservation['reservation_date']));
            $timeLabel = date('h:i A', strtotime($reservation['reservation_time']));
            $pcLabel = 'PC ' . (int)$reservation['pc_number'];

            createStudentNotification(
                $conn,
                $studentId,
                'reservation_late_cancelled',
                'Reservation Cancelled Due to Late Arrival',
                'Your approved reservation for ' . $reservation['lab'] . ' ' . $pcLabel .
                ' on ' . $dateLabel . ' at ' . $timeLabel .
                ' was automatically cancelled because you were more than 15 minutes late.'
            );
        }
    }

    $update->close();
}
