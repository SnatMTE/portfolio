<?php
/**
 * core/calendar_helper.php
 *
 * Utility functions for building the monthly calendar grid.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

/**
 * Builds the data structure for a monthly calendar grid.
 *
 * Returns a 2-D array of weeks, each being an array of 7 day cells.
 * Each cell is an array with keys:
 *   - day        (int|null)  Day number, null for padding cells
 *   - date       (string)    "YYYY-MM-DD", empty for padding cells
 *   - isToday    (bool)
 *   - isOtherMonth (bool)
 *   - events     (array)     Events starting on this day
 *
 * The grid always starts on Monday.
 *
 * @param int                              $year
 * @param int                              $month   1–12
 * @param array<int, array<string, mixed>> $events  Rows from cal_events (for this month)
 * @return array<int, array<int, array<string, mixed>>>>
 * @author Snat
 * @link https://terra.me.uk
 */
function buildMonthGrid(int $year, int $month, array $events): array
{
    $today     = date('Y-m-d');
    $firstDay  = (int) date('N', mktime(0, 0, 0, $month, 1, $year)); // 1=Mon … 7=Sun
    $daysInMon = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

    // Index events by start date for fast per-day lookup
    $byDate = [];
    foreach ($events as $event) {
        $eventDate = substr($event['start_datetime'], 0, 10); // "YYYY-MM-DD"
        $byDate[$eventDate][] = $event;
    }

    $grid    = [];
    $week    = [];
    $dayNum  = 1;

    // Add leading padding cells so the grid always starts on Monday
    $padding = $firstDay - 1;
    for ($p = 0; $p < $padding; $p++) {
        $week[] = makeEmptyCell();
    }

    while ($dayNum <= $daysInMon) {
        $dateStr  = sprintf('%04d-%02d-%02d', $year, $month, $dayNum);
        $week[]   = [
            'day'          => $dayNum,
            'date'         => $dateStr,
            'isToday'      => ($dateStr === $today),
            'isOtherMonth' => false,
            'events'       => $byDate[$dateStr] ?? [],
        ];

        if (count($week) === 7) {
            $grid[] = $week;
            $week   = [];
        }

        $dayNum++;
    }

    // Pad final week so every row contains seven cells
    if (!empty($week)) {
        while (count($week) < 7) {
            $week[] = makeEmptyCell();
        }
        $grid[] = $week;
    }

    return $grid;
}

/**
 * Returns an empty (padding) cell for the grid.
 *
 * @return array<string, mixed>
 * @author Snat
 * @link https://terra.me.uk
 */
function makeEmptyCell(): array
{
    return [
        'day'          => null,
        'date'         => '',
        'isToday'      => false,
        'isOtherMonth' => true,
        'events'       => [],
    ];
}

/**
 * Returns the name of the given month.
 *
 * @param int $month  1–12
 * @return string  e.g. "April"
 * @author Snat
 * @link https://terra.me.uk
 */
function monthName(int $month): string
{
    return date('F', mktime(0, 0, 0, $month, 1, 2000));
}

/**
 * Calculates the previous month's year and month number.
 *
 * @param int $year
 * @param int $month
 * @return array{year:int, month:int}
 * @author Snat
 * @link https://terra.me.uk
 */
function prevMonth(int $year, int $month): array
{
    $month--;
    if ($month < 1) {
        $month = 12;
        $year--;
    }
    return ['year' => $year, 'month' => $month];
}

/**
 * Calculates the next month's year and month number.
 *
 * @param int $year
 * @param int $month
 * @return array{year:int, month:int}
 * @author Snat
 * @link https://terra.me.uk
 */
function nextMonth(int $year, int $month): array
{
    $month++;
    if ($month > 12) {
        $month = 1;
        $year++;
    }
    return ['year' => $year, 'month' => $month];
}
