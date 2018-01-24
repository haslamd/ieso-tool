<?php
/*
 * This file is part of ieso-tool - the ieso query and download tool.
 *
 * (c) LivITy Consultinbg Ltd, Enbridge Inc., Kevin Edwards
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace LivITy\IESO;
use Dotenv\Dotenv;
use Monolog\Logger as Monologger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

Class Logger
{
    protected $log;
    protected $dateFormat;

    public function __construct(Config $config, String $name = 'ieso')
    {
        if (file_exists($file = \Env::get('IESO_ENBRIDGE_PATH') . '/' . $name . '.log')) {
        	$filesize = filesize($file);

        	if ($filesize > $this->stringSizeToBytes('10 MB')) {
                $this->log()->info('Deleting log file that is over 10MB.');
                unlink($file);
        	}
        }

        // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        // the default date format is "Y-m-d H:i:s", popular date format is "Y n j, g:i a";
        $output = \Env::get('IESO_LOG_OUTPUT') ?: "[%datetime%] %level_name% : %message%\n";
        $logFormat = \Env::get('IESO_LOG_FORMAT') ?: 'Y-m-d H:i:s';
        $logHeader = 'IESO';
        $this->dateFormat = 'Y-m-d';

        $formatter = new LineFormatter($output . "\n", $logFormat);
        //$stream = new StreamHandler(realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/' . $name . '_' . date($this->dateFormat) . '.log', Monologger::INFO);
        $stream = new StreamHandler(realpath(\Env::get('IESO_ENBRIDGE_PATH')) . '/' . $name . '.log', Monologger::INFO);
        $stream->setFormatter($formatter);
        $log = new Monologger($logHeader);
        $log->pushHandler($stream);

        $this->log = $log;
        return $this;
    }

    public function get_logger()
    {
        return $this->log;
    }

    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    private function stringSizeToBytes($size)
    {

		$unit = strtolower($size);
		$unit = preg_replace('/[^a-z]/', '', $unit);

		$value = intval(preg_replace('/[^0-9]/', '', $size));

		$units = array('b'=>0, 'kb'=>1, 'mb'=>2, 'gb'=>3, 'tb'=>4);
		$exponent = isset($units[$unit]) ? $units[$unit] : 0;

		return ($value * pow(1024, $exponent));
	}

}
