<?php
/**
 * db/demo_seed.php
 *
 * Seeds the database with sample events for demonstration purposes.
 * Called automatically when a DEMO file exists in ROOT_PATH.
 *
 * IMPORTANT: Remove the DEMO file before going live.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Inserts sample demo events if none exist yet.
 *
 * @param PDO $pdo
 * @return void
 */
function seedDemoCalendar(PDO $pdo): void
{
    // Only seed once
    $count = (int) $pdo->query("SELECT COUNT(*) FROM cal_events")->fetchColumn();
    if ($count > 0) {
        return;
    }

    $now   = new DateTime();
    $year  = (int) $now->format('Y');
    $month = (int) $now->format('n');

    /**
     * Helper to build a datetime string for a day offset from today.
     *
     * @param int $dayOffset   Days from today (negative = past).
     * @param int $hour        Hour (0–23).
     * @param int $minute      Minute (0–59).
     * @return string  "YYYY-MM-DD HH:MM:SS"
     */
    $dt = function (int $dayOffset, int $hour = 9, int $minute = 0) use ($now): string {
        $d = clone $now;
        $d->modify("{$dayOffset} days");
        $d->setTime($hour, $minute, 0);
        return $d->format('Y-m-d H:i:s');
    };

    $events = [
        [
            'title'          => 'Team Weekly Standup',
            'description'    => 'Regular weekly team sync to review progress and blockers.',
            'start_datetime' => $dt(1, 10, 0),
            'end_datetime'   => $dt(1, 10, 30),
            'location'       => 'Zoom',
            'is_public'      => 1,
        ],
        [
            'title'          => 'Project Kick-off Meeting',
            'description'    => 'Kick-off session for the new portfolio calendar project.',
            'start_datetime' => $dt(3, 14, 0),
            'end_datetime'   => $dt(3, 15, 30),
            'location'       => 'Conference Room B',
            'is_public'      => 1,
        ],
        [
            'title'          => 'Design Review',
            'description'    => "Review UI mockups with the design team.\nBring printed wireframes.",
            'start_datetime' => $dt(5, 11, 0),
            'end_datetime'   => $dt(5, 12, 0),
            'location'       => 'Design Studio',
            'is_public'      => 1,
        ],
        [
            'title'          => 'Client Demo',
            'description'    => 'Live demo of the calendar module for the client stakeholders.',
            'start_datetime' => $dt(7, 15, 0),
            'end_datetime'   => $dt(7, 16, 0),
            'location'       => 'Online (Teams)',
            'is_public'      => 0,
        ],
        [
            'title'          => 'Quarterly Planning',
            'description'    => 'All-hands planning session for Q3 roadmap.',
            'start_datetime' => $dt(10, 9, 0),
            'end_datetime'   => $dt(10, 17, 0),
            'location'       => 'Main Office',
            'is_public'      => 1,
        ],
        [
            'title'          => 'Code Review Session',
            'description'    => 'Peer review of the calendar module pull requests.',
            'start_datetime' => $dt(12, 14, 30),
            'end_datetime'   => $dt(12, 15, 30),
            'location'       => '',
            'is_public'      => 1,
        ],
        [
            'title'          => 'Deployment Day',
            'description'    => 'Production deployment of v2.0. All hands on deck.',
            'start_datetime' => $dt(15, 10, 0),
            'end_datetime'   => $dt(15, 18, 0),
            'location'       => 'Remote',
            'is_public'      => 1,
        ],
        [
            'title'          => 'Team Lunch',
            'description'    => 'Monthly team lunch outing.',
            'start_datetime' => $dt(18, 12, 30),
            'end_datetime'   => $dt(18, 14, 0),
            'location'       => 'The Anchor, High Street',
            'is_public'      => 1,
        ],
        [
            'title'          => 'Sprint Retrospective',
            'description'    => 'End-of-sprint retrospective. Review what went well and areas to improve.',
            'start_datetime' => $dt(20, 16, 0),
            'end_datetime'   => $dt(20, 17, 0),
            'location'       => 'Zoom',
            'is_public'      => 1,
        ],
        [
            'title'          => 'Annual Conference',
            'description'    => 'WebDevConf 2026 – talks, workshops, and networking.',
            'start_datetime' => $dt(25, 9, 0),
            'end_datetime'   => $dt(27, 18, 0),
            'location'       => 'ExCeL London',
            'is_public'      => 1,
        ],
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO cal_events
             (title, description, start_datetime, end_datetime, location, is_public)
         VALUES
             (:title, :description, :start_datetime, :end_datetime, :location, :is_public)"
    );

    foreach ($events as $ev) {
        $stmt->execute([
            ':title'          => $ev['title'],
            ':description'    => $ev['description'],
            ':start_datetime' => $ev['start_datetime'],
            ':end_datetime'   => $ev['end_datetime'],
            ':location'       => $ev['location'],
            ':is_public'      => $ev['is_public'],
        ]);
    }
}
