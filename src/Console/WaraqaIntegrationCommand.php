<?php

namespace Mawdoo3\Waraqa\Console;

use Mawdoo3\Waraqa\Services\WaraqaIntegration ;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WaraqaIntegrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waraqa:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'listen to waraqa queue to inport articles';

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
        //export all articles data as csv file
        try{
            $this->info('start execute');
            (new WaraqaIntegration)->execute();

        }catch(Exception $ex){
            Log::error(__CLASS__.":".__FUNCTION__.":".$ex->getMessage());
            $this->error($ex->getMessage());
        }
    }


}
