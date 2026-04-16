<?php
/**
 * core/ics_generator.php
 *
 * Generates valid iCalendar (.ics) content from an array of event records.
 *
 * The output conforms to RFC 5545 and is compatible with:
 *   - Google Calendar
 *   - Apple Calendar (iCal)
 *   - Microsoft Outlook
 *
 * Usage
 * -----
 *   $gen     = new IcsGenerator();
 *   $icsText = $gen->generate($events, 'My Calendar');
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

class IcsGenerator
{
    /**
     * Generates a VCALENDAR string from an array of event rows.
     *
     * @param array<int, array<string, mixed>> $events     Rows from cal_events.
     * @param string                           $calName    Calendar display name.
     * @param string                           $calDesc    Calendar description.
    * @return string  Complete .ics content.
    * @author Snat
    * @link https://terra.me.uk
     */
    public function generate(array $events, string $calName = 'Calendar', string $calDesc = ''): string
    {
        $lines = [];

        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//Snat Portfolio//Calendar//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:' . $this->escapeText($calName);
        if ($calDesc !== '') {
            $lines[] = 'X-WR-CALDESC:' . $this->escapeText($calDesc);
        }

        foreach ($events as $event) {
            $lines = array_merge($lines, $this->buildVEvent($event));
        }

        $lines[] = 'END:VCALENDAR';

        // RFC 5545 requires CRLF line endings
        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Builds the VEVENT lines for a single event row.
     *
     * @param array<string, mixed> $event
     * @return array<string>
     */
    private function buildVEvent(array $event): array
    {
        $uid = 'event-' . (int) $event['id'] . '@calendar';

        $lines   = [];
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:'         . $uid;
        $lines[] = 'DTSTAMP:'     . gmdate('Ymd\THis\Z');
        $lines[] = 'DTSTART:'     . $this->formatDatetime($event['start_datetime']);
        $lines[] = 'DTEND:'       . $this->formatDatetime($event['end_datetime']);
        $lines[] = 'SUMMARY:'     . $this->escapeText($event['title'] ?? '');

        if (!empty($event['description'])) {
            $lines[] = $this->foldLine('DESCRIPTION:' . $this->escapeText($event['description']));
        }

        if (!empty($event['location'])) {
            $lines[] = 'LOCATION:' . $this->escapeText($event['location']);
        }

        if (!empty($event['created_at'])) {
            $lines[] = 'CREATED:' . $this->formatDatetime($event['created_at']);
        }

        if (!empty($event['updated_at'])) {
            $lines[] = 'LAST-MODIFIED:' . $this->formatDatetime($event['updated_at']);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * Formats a database datetime string ("YYYY-MM-DD HH:MM:SS") as an
     * iCalendar UTC datetime ("YYYYMMDDTHHmmssZ").
     *
     * @param string $dt
    * @return string
    * @author Snat
    * @link https://terra.me.uk
     */
    private function formatDatetime(string $dt): string
    {
        try {
            $dateObj = new DateTime($dt, new DateTimeZone('UTC'));
            return $dateObj->format('Ymd\THis\Z');
        } catch (Exception) {
            return gmdate('Ymd\THis\Z');
        }
    }

    /**
     * Escapes special characters in iCalendar text values.
     *
     * @param string $text
    * @return string
    * @author Snat
    * @link https://terra.me.uk
     */
    private function escapeText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace([',', ';', "\r\n", "\r", "\n"],
                            ['\\,', '\\;', '\\n', '\\n', '\\n'],
                            $text);
        return $text;
    }

    /**
     * Folds a long content line per RFC 5545 §3.1 (max 75 octets).
     *
     * @param string $line
    * @return string  Line with CRLF+SPACE fold insertions.
    * @author Snat
    * @link https://terra.me.uk
     */
    private function foldLine(string $line): string
    {
        $result = '';
        $bytes  = 0;
        $len    = strlen($line);

        for ($i = 0; $i < $len; $i++) {
            $char = $line[$i];
            if ($bytes >= 75) {
                $result .= "\r\n ";
                $bytes   = 1;
            }
            $result .= $char;
            $bytes++;
        }

        return $result;
    }
}
