<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class CleanExpiredTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina todos los tokens expirados de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedCount = PersonalAccessToken::where('expires_at', '<', now())->delete();
        
        $this->info("Se eliminaron {$deletedCount} tokens expirados.");
        
        return Command::SUCCESS;
    }
}

