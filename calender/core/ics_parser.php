<?php
/**
 * core/ics_parser.php
 *
 * Parses iCalendar (.ics) file content into an array of event data
 * ready for insertion into the database.
 *
 * Usage
 * -----
 *   $parser = new IcsParser();
 *   $events = $parser->parse($icsString);
 *
 * Supported VEVENT properties
 * ---------------------------
 *   SUMMARY, DTSTART, DTEND, DESCRIPTION, LOCATION
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

class IcsParser
{
    /**
     * Parses an iCalendar string and returns an array of event arrays.
     *
     * Each returned event has the keys:
     *   title, description, start_datetime, end_datetime, location
     *
     * @param string $icsContent  Raw .ics file content.
     * @return array<int, array<string, string>>
     */
    public function parse(string $icsContent): array
    {
        // Unfold folded lines per RFC 5545 (CRLF + whitespace continuation)
        $content = preg_replace("/\r\n[ \t]/", '', $icsContent);
        $content = preg_replace("/\r/", '', $content);

        $events  = [];
        $inEvent = false;
        $current = [];

        foreach (explode("\n", $content) as $line) {
            $line = rtrim($line);

            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $current = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                $inEvent = false;
                $event   = $this->buildEvent($current);
                if ($event !== null) {
                    $events[] = $event;
                }
                continue;
            }

            if (!$inEvent) {
                continue;
            }

            // Split into property name (+ params) and value
            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue;
            }

            $prop  = strtoupper(substr($line, 0, $colonPos));
            $value = substr($line, $colonPos + 1);

            // Strip parameter qualifiers (e.g. DTSTART;TZID=...: → DTSTART)
            $semiPos = strpos($prop, ';');
            $baseKey = $semiPos !== false ? substr($prop, 0, $semiPos) : $prop;

            $current[$baseKey] = $value;
        }

        return $events;
    }

     /**
      * Converts a raw VEVENT property array into a normalised event array.
      *
      * Returns null if mandatory fields (SUMMARY or DTSTART) are missing.
      *
      * @param array<string, string> $raw
      * @return array<string, string>|null
      */
    private function buildEvent(array $raw): ?array
    {
        $title = trim($raw['SUMMARY'] ?? '');
        $dtStart = trim($raw['DTSTART'] ?? '');

        if ($title === '' || $dtStart === '') {
            return null;
        }

        $dtEnd  = trim($raw['DTEND'] ?? $dtStart);

        return [
            'title'          => $this->unescapeText($title),
            'description'    => $this->unescapeText($raw['DESCRIPTION'] ?? ''),
            'start_datetime' => $this->parseIcsDatetime($dtStart),
            'end_datetime'   => $this->parseIcsDatetime($dtEnd),
            'location'       => $this->unescapeText($raw['LOCATION'] ?? ''),
        ];
    }

    /**
     * Converts an iCalendar datetime string to an SQLite-compatible
     * "YYYY-MM-DD HH:MM:SS" string.
     *
     * Supports formats:
     *   - 20260415T100000Z  (UTC)
     *   - 20260415T100000   (floating / local)
     *   - 20260415          (all-day date)
     *
     * @param string $icsDate
     * @return string  "YYYY-MM-DD HH:MM:SS"
     */
    private function parseIcsDatetime(string $icsDate): string
    {
        // Strip trailing Z (UTC marker) for parsing
        $d = rtrim($icsDate, 'Z');

        if (strlen($d) === 8) {
            // All-day date: YYYYMMDD
            return substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2) . ' 00:00:00';
        }

        if (strlen($d) >= 15 && $d[8] === 'T') {
            // Date-time: YYYYMMDDTHHmmss
            $date = substr($d, 0, 8);
            $time = substr($d, 9, 6);
            return substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                 . ' ' . substr($time, 0, 2) . ':' . substr($time, 2, 2) . ':' . substr($time, 4, 2);
        }

        // Fallback: return original string for DB to handle if possible
        return $icsDate;
    }

     /**
      * Unescapes iCalendar text value escapes.
      *
      * @param string $text
      * @return string
      */
    private function unescapeText(string $text): string
    {
        $text = str_replace(['\\,', '\\;', '\\n', '\\N', '\\\\'],
                            [',',   ';',   "\n",  "\n",  '\\'],
                            $text);
        return trim($text);
    }
}
