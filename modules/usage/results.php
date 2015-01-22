<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

/**
 * @file results.php
 * @brief display graph results
 */

require_once 'modules/graphics/plotter.php';

if (isset($_POST['user_date_start'])) {
    $uds = DateTime::createFromFormat('d-m-Y H:i', $_POST['user_date_start']);
    $u_date_start = $uds->format('Y-m-d H:i');
    $user_date_start = $uds->format('d-m-Y H:i');
} else {
    $date_start = new DateTime();
    $date_start->sub(new DateInterval('P30D'));    
    $u_date_start = $date_start->format('Y-m-d H:i');
    $user_date_start = $date_start->format('d-m-Y H:i');       
}
if (isset($_POST['user_date_end'])) {
    $ude = DateTime::createFromFormat('d-m-Y H:i', $_POST['user_date_end']);    
    $u_date_end = $ude->format('Y-m-d H:i');
    $user_date_end = $ude->format('d-m-Y H:i');        
} else {
    $date_end = new DateTime();
    $u_date_end = $date_end->format('Y-m-d H:i');
    $user_date_end = $date_end->format('d-m-Y H:i');        
}


$usage_defaults = array(
    'u_stats_value' => 'visits',
    'u_interval' => 'daily',
    'u_module_id' => -1    
);

foreach ($usage_defaults as $key => $val) {
    if (!isset($_POST[$key])) {
        $$key = $val;
    } else {
        $$key = $_POST[$key];
    }
}

if ($u_module_id != -1) {
    $mod_where = " (module_id = '$u_module_id') ";
} else {
    $mod_where = " (1) ";
}


$date_fmt = '%Y-%m-%d';
$date_where = "(`day` BETWEEN '$u_date_start' AND '$u_date_end') ";
$date_what = "";

switch ($u_interval) {
    case "summary":
        $date_group = ' ';
        $date_what = ' ';
        break;
    case "daily":
        $date_what .= " DATE_FORMAT(`day`, '$date_fmt') AS `date`, ";
        $date_group = " GROUP BY `day` ";
        break;
    case "weekly":
        $date_what .= " DATE_FORMAT(`day` - INTERVAL WEEKDAY(`day`) DAY, '$date_fmt') AS week_start " .
                ", DATE_FORMAT(`day` + INTERVAL (6 - WEEKDAY(`day`)) DAY, '$date_fmt') AS week_end, ";
        $date_group = " GROUP BY WEEK(`day`) ";
        break;
    case "monthly":
        $date_what .= " MONTH(`day`) AS `month`, ";
        $date_group = " GROUP BY MONTH(`day`) ";
        break;
    case "yearly":
        $date_what .= " YEAR(`day`) AS `year`, ";
        $date_group = " GROUP BY YEAR(`day`) ";
        break;
}


$chart = new Plotter(300, 300);

switch ($u_stats_value) {
    case "visits":        
        $result = Database::get()->queryArray("SELECT  $date_what SUM(hits) AS cnt FROM actions_daily
                                            WHERE $date_where
                                            AND $mod_where
                                            AND course_id = ?d
                                           $date_group ORDER BY `day` ASC", $course_id);

        switch ($u_interval) {
            case "summary":
                foreach ($result as $row) {
                    $chart->growWithPoint($langSummary, $row->cnt);
                }
                break;
            case "daily":
                foreach ($result as $row) {
                    $chart->growWithPoint($row->date, $row->cnt);
                }
                break;
            case "weekly":
                foreach ($result as $row) {
                    $chart->growWithPoint($row->week_start . ' - ' . $row->week_end, $row->cnt);
                }
                break;
            case "monthly":
                foreach ($result as $row) {
                    $chart->growWithPoint($langMonths[$row->month], $row->cnt);
                }
                break;
            case "yearly":
                foreach ($result as $row) {
                    $chart->growWithPoint($row->year, $row->cnt);
                }
                break;
        }
        $chart->setTitle("$langVisits");

        break;
    case "duration":
        $result = Database::get()->queryArray("SELECT $date_what SUM(duration) AS tot_dur
                                                FROM actions_daily
                                                WHERE $date_where
                                                AND $mod_where
                                                AND course_id = ?d
                                                $date_group ORDER BY day ASC", $course_id);
        switch ($u_interval) {
            case "summary":
                foreach ($result as $row) {
                    $row->tot_dur = round($row->tot_dur / 60);
                    $chart->growWithPoint($langSummary, $row->tot_dur);
                }
                break;
            case "daily":
                foreach ($result as $row) {
                    $row->tot_dur = round($row->tot_dur / 60);
                    $chart->growWithPoint($row->date, $row->tot_dur);
                }
                break;
            case "weekly":
                foreach ($result as $row) {
                    $row->tot_dur = round($row->tot_dur / 60);
                    $chart->growWithPoint($row->week_start . ' - ' . $row->week_end, $row->tot_dur);
                }
                break;
            case "monthly":
                foreach ($result as $row) {
                    $row->tot_dur = round($row->tot_dur / 60);
                    $chart->growWithPoint($langMonths[$row->month], $row->tot_dur);
                }
                break;
            case "yearly":
                foreach ($result as $row) {
                    $row->tot_dur = round($row->tot_dur / 60);
                    $chart->growWithPoint($row->year, $row->tot_dur);
                }
                break;
        }

        $chart->setTitle("$langDurationVisits");
        $tool_content .= "<div class='alert alert-info'>$langDurationExpl</div>";

        break;
}

$errorMsg = '<div class="alert alert-warning">' . $langNoStatistics . '</div>';
$tool_content .= $chart->plot($errorMsg);
