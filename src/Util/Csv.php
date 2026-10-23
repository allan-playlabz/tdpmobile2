<?php

namespace App\Util;

use Cake\Log\Log;
use Exception;

/**
 * Csv class
 * utility class for writing to .csv file
 */
class Csv
{
    /**
     * Writes array to csv file and returns the full filename
     *
     * @param array $input_array
     * @param string $output_file_name
     * @param string $delimiter
     * @param string|null $agent_user_id
     * @return void
     */
    public static function writeReport($input_array, $output_file_name, $delimiter, $agent_user_id = null)
    {
        $tmp = TMP . 'reports';
        if ($agent_user_id) {
            $tmp = TMP . 'reports' . DS . $agent_user_id;
        }
        $dir = new \App\Filesystem\Folder($tmp , true, 0777);
        $save_file_name = $output_file_name;
        $csv = fopen($tmp . DS . $save_file_name, 'w');
        foreach ($input_array as $line) {
            fputcsv($csv, $line, $delimiter);
        }
        fclose($csv);

        return $tmp . DS . $save_file_name;
    }

    /**
     * Writes array to csv file and returns the full filename
     *
     * @param array $input_array
     * @param string $output_file_name
     * @param string $delimiter
     * @param string|null $agent_user_id
     * @return void
     */
    public static function appendToReport($input_array, $output_file_name, $delimiter, $agent_user_id = null)
    {
        $tmp = TMP . 'reports';
        if ($agent_user_id) {
            $tmp = TMP . 'reports' . DS . $agent_user_id;
        }
        $dir = new \App\Filesystem\Folder($tmp , true, 0777);
        $save_file_name = $output_file_name;
        $csv = fopen($tmp . DS . $save_file_name, 'a');
        foreach ($input_array as $line) {
            fputcsv($csv, $line, $delimiter);
        }
        fclose($csv);

        return $tmp . DS . $save_file_name;
    }

    /**
     * Removes .temp file extension from report filename
     *
     * @param string $output_file_name
     * @param string|null $agent_user_id
     * @return void
     */
    public static function finishReport($output_file_name, $agent_user_id = null)
    {
        $tmp = TMP . 'reports';
        if ($agent_user_id) {
            $tmp = TMP . 'reports' . DS . $agent_user_id;
        }
        $dir = new \App\Filesystem\Folder($tmp , true, 0777);
        $old_filename = $tmp . DS . $output_file_name;
        // $old_filename = '/tmp/reports/' . $agent_user_id . '/' . $output_file_name;
        $new_filename = str_replace('.tmp', '', $tmp . DS .$output_file_name);
        rename ($old_filename, $new_filename);

        return $new_filename;
    }
}
