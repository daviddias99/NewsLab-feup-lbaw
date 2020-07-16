<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use Exception;

class periodictask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'periodic:task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        error_log("Periodic command ran.");
        PostController::makePublicAfterTime();

        try{
            UserController::unbanAfterTime();
        }
        catch(Exception $e){
            return;
        }
    }
}
