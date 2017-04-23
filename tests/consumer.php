#!/opt/remi/php71/root/usr/bin/php -q
<?php
    // Get the script directory and load the configuration relative to that
    $scriptDir = dirname(__FILE__);
    $configFile = $scriptDir.'/config.php';

    // Load up the Common class
    require_once($scriptDir.'/Common.php');

    // This will get the configuration loaded on the first loop
    $configCheckTime = 0;
    $configFileHash = '';

    while (true) {
        // Check if we need to check the config yet
        if(time() > $configCheckTime) {
            if($configCheck = Common::checkConfigFile($configFile, $configFileHash)) {
                echo 'Configuration has changed, reloading all the things'.PHP_EOL;
                $config = $configCheck;
                var_export($config);
                // Check if the state directory is writeable, if not exit, we can't work like this
                if(!is_writable($config['state_dir'])) {
                    echo 'Can not write to the state_dir, can not start!!!'.PHP_EOL;
                    exit(255);
                }
                /**
                 * Load up the message queuing software based on the configuration, should allow swapping out the backend
                 * without changing much.
                 */
                // Check if the class exists, if not load the required file
                if(!class_exists($config['service'])) {
                    require($scriptDir . '/' . $config['service'] . '.php');
                }
                // Create the service object that will get us the yummy messages
                $service = new $config['service']($config);
                // When we need to next check the config for changes
                $configCheckTime = Common::configCheckTime($config['config_check_interval']);
                // The current config file hash
                $configFileHash = md5_file($scriptDir.'/config.php');
            } else {
                $configCheckTime = Common::configCheckTime($config['config_check_interval']);
                echo 'Configuration hasn\'t changed'.PHP_EOL;
            }
        } else {
            echo 'Not time to check the config yet'.PHP_EOL;
        }
        // Get all messages that are currently waiting
        while(true) {
            $message = $service->getMessage();
            if ($message === null) {
                echo 'There were no messages to process' . PHP_EOL;
                break;
            } elseif ($message === false) {
                echo 'We encountered an error trying to get a message from the queue: '.$service->error . PHP_EOL;
                break;
            } else {
                echo 'Retrieved message: '.$message.PHP_EOL;
                // todo: process the message

                // When processing is successful mark message as complete so we can commit the offset
                $service->lastMessageComplete();
            }
        }
        echo 'Sleeping for '.$config['queue_check_interval'].' seconds'.PHP_EOL;
        sleep($config['queue_check_interval']);
    }
