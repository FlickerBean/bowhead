<?php

namespace Bowhead\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Psy\Command\Command;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\A_SetupCommand::class,
        Commands\BitfinexWebsocketCommand::class,
        Commands\CoinbaseWebsocketCommand::class,
        Commands\DataRunnerCcxtCommand::class,
        Commands\DataRunnerCoinigyCommand::class,
        Commands\ExampleUsageCommand::class,
        Commands\ExampleCommand::class,
        Commands\Forecast::class,
        Commands\GetHistoricalCommand::class,
        //Commands\ExampleForexStrategyCommand::class,
        //Commands\BitfinexWebsocketETHCommand::class,
        //Commands\WebsocketCoinbaseTestCommand::class,
        //Commands\OandaStreamCommand::class,
        Commands\SignalsExampleCommand::class,
        Commands\TestStrategiesCommand::class,
        Commands\GdaxScalperCommand::class,
        Commands\TestCandlesCommand::class,
        Commands\TestTrendsCommand::class,
        Commands\RandomWalkCommand::class,
        //Commands\FxStreamCommand::class,
        //Commands\KrakenStreamCommand::class,
        Commands\ImportHistoryData::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        // @TODO withoutOverlapping will expire after 24 hours by default...
        // make sure we don't end up with multiple instances.
        // https://laravel.com/docs/5.8/scheduling

        //$schedule->command('bowhead:fx_stream')->withoutOverlapping()->everyMinute();

        // @NOTE it's okay to run both here because each command will check the
        // config and exit accordingly
        $schedule->command('bowhead:datarunner_coinigy')->withoutOverlapping()->everyMinute();
        $schedule->command('bowhead:datarunner_ccxt')->withoutOverlapping()->everyMinute();
    }

    /**
     * Register the Closure based commands for the application.
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
