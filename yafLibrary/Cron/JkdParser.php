<?php

namespace Cron;

class JkdParser
{
    /**
     *  解析crontab的定时格式，与linux一致只支持到分钟.
     *
     * @param string $crontabString :
     *                              0    1    2    3    4    5
     *                              *    *    *    *    *    *
     *                              -    -    -    -    -    -
     *                              |    |    |    |    |    |
     *                              |    |    |    |    |    +----- day of week (0 - 6) (Sunday=0)
     *                              |    |    |    |    +----- month (1 - 12)
     *                              |    |    |    +------- day of month (1 - 31)
     *                              |    |    +--------- hour (0 - 23)
     *                              |    +----------- min (0 - 59)
     *                              +----------- second (0 - 59)
     * @param null|int $startTime
     * @return []
     * @throws \Exception
     */
    public function parse(string $crontabString)
    {
        if (!$this->isValid($crontabString)) {
            throw new \Exception('Invalid cron string: ' . $crontabString);
        }
        $date = $this->parseDate($crontabString);
        return $date;
    }


    /**
     * 检查是否满足
     *
     * @param $startTime
     * @param $date
     * @return bool|void
     */
    public function check($startTime, $date)
    {
        if (in_array((int)date('s', $startTime), $date['second'])
            && in_array((int)date('i', $startTime), $date['minutes'])
            && in_array((int)date('G', $startTime), $date['hours'])
            && in_array((int)date('j', $startTime), $date['day'])
            && in_array((int)date('w', $startTime), $date['week'])
            && in_array((int)date('n', $startTime), $date['month'])
        ) {
            return true;
        }
        return false;
    }


    public function isValid(string $crontabString): bool
    {
        if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($crontabString))) {
            if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($crontabString))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse each segment of crontab string.
     */
    protected function parseSegment(string $string, int $min, int $max, int $start = null)
    {
        if ($start === null || $start < $min) {
            $start = $min;
        }
        $result = [];
        if ($string === '*') {
            for ($i = $start; $i <= $max; ++$i) {
                $result[] = $i;
            }
        } elseif (strpos($string, ',') !== false) {
            $exploded = explode(',', $string);
            foreach ($exploded as $value) {
                if (strpos($value, '/') !== false || strpos($string, '-') !== false) {
                    $result = array_merge($result, $this->parseSegment($value, $min, $max, $start));
                    continue;
                }

                if (trim($value) === '' || !$this->between((int)$value, (int)($min > $start ? $min : $start), (int)$max)) {
                    continue;
                }
                $result[] = (int)$value;
            }
        } elseif (strpos($string, '/') !== false) {
            $exploded = explode('/', $string);
            if (strpos($exploded[0], '-') !== false) {
                [$nMin, $nMax] = explode('-', $exploded[0]);
                $nMin > $min && $min = (int)$nMin;
                $nMax < $max && $max = (int)$nMax;
            }
            // If the value of start is larger than the value of min, the value of start should equal with the value of min.
            $start < $min && $start = $min;
            $step = (int)$exploded[1];
            if ($step <= 0) {
                $step = 1; // 防止 */0 导致死循环
            }
            for ($i = $start; $i <= $max;) {
                $result[] = $i;
                $i += $step;
            }
        } elseif (strpos($string, '-') !== false) {
            $result = array_merge($result, $this->parseSegment($string . '/1', $min, $max, $start));
        } elseif ($this->between((int)$string, $min > $start ? $min : $start, $max)) {
            $result[] = (int)$string;
        }
        return $result;
    }

    /**
     * Determire if the $value is between in $min and $max ?
     */
    private function between(int $value, int $min, int $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    private function parseDate(string $crontabString): array
    {
        $cron = preg_split('/[\\s]+/i', trim($crontabString));
        if (count($cron) == 6) {
            $date = [
                'second' => $this->parseSegment($cron[0], 0, 59),
                'minutes' => $this->parseSegment($cron[1], 0, 59),
                'hours' => $this->parseSegment($cron[2], 0, 23),
                'day' => $this->parseSegment($cron[3], 1, 31),
                'month' => $this->parseSegment($cron[4], 1, 12),
                'week' => $this->parseSegment($cron[5], 0, 6),
            ];
        } else {
            $date = [
                'second' => [ 1 => 0 ],
                'minutes' => $this->parseSegment($cron[0], 0, 59),
                'hours' => $this->parseSegment($cron[1], 0, 23),
                'day' => $this->parseSegment($cron[2], 1, 31),
                'month' => $this->parseSegment($cron[3], 1, 12),
                'week' => $this->parseSegment($cron[4], 0, 6),
            ];
        }
        return $date;
    }
}
