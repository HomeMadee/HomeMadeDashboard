<?php

namespace App\Console;

use App\Models\Food;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

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
        $schedule->command('inspire')
            ->everyMinute();
        $schedule->command('resetRemaining')
            ->everyMinute();
        /*  $schedule->call(function () {
            $foods = Food::all();
            foreach ($foods as $food) {
                $remaining = 0;
                if (isset($food->custom_fields)) {
                    try {
                        if ($food->custom_fields['producible']['value'] === '0') {
                            $remaining = $food->custom_fields['daily_orders']['value'];
                        } else {
                            $days_str = $food->custom_fields['working_days']['value'];
                            $days_arr = explode(',', substr($days_str, 1, strlen($days_str) - 2));
                            $workingHrs =  intval($food->custom_fields['working_hours']['value']);
                            $prepare_time = intval($food->custom_fields['prepare_time']['value']);
                            $hrsPerDay = $workingHrs / count($days_arr);
                            $dailyProducts = $hrsPerDay / $prepare_time;
                            $remaining = $dailyProducts;
                            // $hrsPerDay = $input['working_hours'] / count($input['working_days']);
                            // $input['remaining'] = $dailyProducts;
                        }
                        $food->remaining = $remaining;
                        $food->save();
                    } catch (\Throwable $th) {
                        Log::error('Food id: ' . $food->id . ' >> ' . $th);
                    }
                }
            }
        })->everyMinute()->runInBackground(); */
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
