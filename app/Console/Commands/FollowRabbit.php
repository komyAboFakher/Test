<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FollowRabbit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbit:follow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'follow the white rabbit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('');
        $this->line('/*');
        $this->line(' *      ██╗  ██╗ ██████╗ ███╗   ███╗██╗   ██╗');
        $this->line(' *      ██║ ██╔╝██╔═══██╗████╗ ████║╚██╗ ██╔╝');
        $this->line(' *      █████╔╝ ██║   ██║██╔████╔██║ ╚████╔╝ ');
        $this->line(' *      ██╔═██╗ ██║   ██║██║╚██╔╝██║  ╚██╔╝  ');
        $this->line(' *      ██║  ██╗╚██████╔╝██║ ╚═╝ ██║   ██║   ');
        $this->line(' *      ╚═╝  ╚═╝ ╚═════╝ ╚═╝     ╚═╝   ╚═╝   ');
        $this->line(' *');
        $this->line(' *  "THE WHITE RABBIT IS YOUR DESTINY, FOLLOW IT"');
        $this->line(' *        Crafted by Komy A. Fakher — ITE');
        $this->line(' */');
        $this->line('');

        // Optional: Add a mysterious action
        $this->info('Initializing rabbit trail...');
        sleep(1);
        $this->comment('Decrypting destiny...');
        sleep(1);
        $this->warn('Reality refactored.');
    }
}
