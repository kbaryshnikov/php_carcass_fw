<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Field;

/**
 * Date field
 *
 * @package Carcass\Field
 */
class Date extends Base {

    protected $min_date;
    protected $max_date;
    protected $allow_empty;
    protected $reverse_year_order = false;

    protected $month_names = [];

    const NUMBER_OF_MONTHS = 12;
    const NUMBER_OF_DAYS = 31;

    /**
     * @param int $min_date
     * @param int $max_date
     * @param $default_value
     * @param bool $allow_empty
     */
    public function __construct($min_date = 0, $max_date = 2147483647, $default_value = null, $allow_empty = false) {
        parent::__construct($default_value);
        foreach (['min_date', 'max_date'] as $d) {
            $this->$d = is_integer($$d) ? $$d : strtotime($$d.' +0000');
        }
        if ($this->min_date > $this->max_date) {
            list($this->min_date, $this->max_date) = [$this->max_date, $this->min_date];
            $this->reverse_year_order = true;
        }
        $this->allow_empty = (bool)$allow_empty;
    }

    /**
     * @return mixed
     */
    public function getMinValue() {
        return $this->min_date;
    }

    /**
     * @return mixed
     */
    public function getMaxValue() {
        return $this->max_date;
    }

    /**
     * @param array $month_names
     * @return $this
     */
    public function setMonthNames(array $month_names) {
        $this->month_names = $month_names;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        if (is_array($value)) {
            if (array_key_exists('day', $value)
                && array_key_exists('month', $value)
                && array_key_exists('year', $value)
                && checkdate((int)$value['month'], (int)$value['day'], (int)$value['year'])
            ) {
                $this->value = gmmktime(0, 0, 0, $value['month'], $value['day'], $value['year']);
            } else {
                $this->value = self::INVALID_VALUE;
            }
        } else if ($this->isTimestamp($value)) {
            $this->value = $value;
        } else if (!is_null($value)) {
            $this->value = strtotime($value);
            if (false === $this->value) $this->value = self::INVALID_VALUE;
        } else {
            $this->value = null;
        }
        return $this;
    }

    /**
     * @return array
     */
    protected function getInitialResultArray() {
        return $this->allow_empty
            ? [ ['key' => '', 'value' => ''] ]
            : [];
    }

    /**
     * @return array
     */
    protected function getYearSet() {
        $start_year = gmdate('Y', $this->min_date);
        $end_year = gmdate('Y', $this->max_date);
        $selected_year = null === $this->value ? -1 : gmdate('Y', $this->value);
        $result = $this->getInitialResultArray();
        for ($year = $start_year; $year <= $end_year; $year ++) {
            $result[] = [
                'key' => $year,
                'value' => $year,
                'selected' => $year == $selected_year,
            ];
        }
        if ($this->reverse_year_order) {
            return array_reverse($result);
        }
        return $result;
    }

    /**
     * @return array
     */
    protected function getMonthSet() {
        $result = $this->getInitialResultArray();
        $selected_month = null === $this->value ? -1 : gmdate('m', $this->value);
        for ($month = 1; $month <= self::NUMBER_OF_MONTHS; $month++) {
            $result[] = [
                'key' => $month,
                'value' => array_key_exists($month, $this->month_names)
                    ? $this->month_names[$month] : gmdate('M',strtotime("2000-$month-01 +0000")),
                'selected' => $month == $selected_month,
            ];
        }
        return $result;
    }

    /**
     * @return array
     */
    protected function getDaySet() {
        $result = $this->getInitialResultArray();
        $selected_day = null === $this->value ? -1 : gmdate('d', $this->value);
        for ($day = 1; $day <= self::NUMBER_OF_DAYS; $day++) {
            $result[] = [
                'key' => $day,
                'value' => $day,
                'selected' => $day == $selected_day,
            ];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function exportArray() {
        $set = parent::exportArray();

        $set['Years']   = $this->getYearSet();
        $set['Months']  = $this->getMonthSet();
        $set['Days']    = $this->getDaySet();

        return $set;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isTimestamp($value) {
        return is_integer($value) || ctype_digit( substr($value, 0, 1) == '-' ? substr($value, 1) : $value );
    }

}
