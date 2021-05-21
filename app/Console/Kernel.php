<?php

namespace App\Console;

use Carbon\Carbon;
use App\Models\Pair;
use App\Models\Rate;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(function(){
            $pairs = Pair::where([
                ['is_active', true],
                ['api_class', 'CurrencyLayerApi']
            ])->get();

            foreach ($pairs as $pair) {
                $source = $pair->base->symbol;
                $currency = $pair->quote->symbol;
                $response = Http::get(config('services.exchange_api.currencylayer.url') . '?access_key=' . config('services.exchange_api.currencylayer.key') . '&currencies=' . $currency . '&source=' . $source . '&format=1');
                if($response->successful())
                {
                    $body = $response->json();
                    if($body["success"]) {
                        Rate::create([
                            'base_currency_id' => $pair->base->id,
                            'quote_currency_id' => $pair->quote->id,
                            'pair_id' => $pair->id,
                            'pair_name' => $pair->name,
                            'quote' => $body['quotes'][$source . $currency],
                            'api_timestamp' => Carbon::createFromTimestamp($body['timestamp']),
                        ]);
                    }
                }
            }
        })->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
