<?php

/**
 * Holds various utility functions that can be used anywhere and not specific to any particular back end.
 *
 * All functions in this class should be called statically.
 */
class Common
{
    /**
     * Returns after which time we should check if the configuration file has changed
     *
     * @param $interval int  The interval in seconds we should check for changes
     * @return int  The unix timestamp after which we have to check to configuration file again
     */
    public static function configCheckTime($interval) {
        return time()+$interval;
    }

    /**
     * Checks to see if the file has changed, if so returns the new config, otherwise returns false.
     * @param $file
     * @param $hash
     * @return bool
     */
    public static function checkConfigFile($file, $hash) {
        if ($hash != md5_file($file)) {
            $config = false;
            require($file);
            return $config;
        } else {
            return false;
        }
    }
}
