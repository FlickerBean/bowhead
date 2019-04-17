<?php

namespace Bowhead\Console\Commands;

use Bowhead\Traits\Signals;
use Bowhead\Traits\OHLC;
use Illuminate\Console\Command;
use Bowhead\Util;
use Illuminate\Support\Facades\DB;
// https://github.com/andreas-glaser/poloniex-php-client

/**
 * Class ExampleCommand.
 */
class SignalsExampleCommand extends Command
{
    use Signals, OHLC;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bowhead:example_signals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forex signals example';

    public function doColor($val)
    {
        if (0 == $val) {
            return 'none';
        }
        if (1 == $val) {
            return 'green';
        }
        if (-1 == $val) {
            return 'magenta';
        }

        return 'none';
    }

    public function handle()
    {
        echo "PRESS 'q' TO QUIT AND CLOSE ALL POSITIONS\n\n\n";
        stream_set_blocking(STDIN, 0);

        while (1) {
            $instruments = ['BTC/USD', 'ETH/BTC', 'LTC/BTC'];

            //			$util        = new Util\BrokersUtil();
            //			$console     = new \Bowhead\Util\Console();
            //			$indicators  = new \Bowhead\Util\Indicators();

            $this->signals(false, false, $instruments);
            $back = $this->signals(1, 2, $instruments);

            foreach ($back as $k => $val) {
                if ('NONE' !== $val) {
                    DB::table('bh_indicators')->insert([
                        ['pair' => $k,
                        'signal' => $val,
                        'inserted' => now(), ],
                        ]);
                } // if

                echo $k.' '.$val."\n";
            } // foreach
            echo "------------------------------------------------\n\n";
            //			print_r($back);

            sleep(5);
        } // while

        return null;
    }

    // handle
}
