<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class CheckTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica el estado de los tokens en la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalTokens = PersonalAccessToken::count();
        $expiredTokens = PersonalAccessToken::where('expires_at', '<', now())->count();
        $activeTokens = PersonalAccessToken::where('expires_at', '>', now())->count();
        $tokensWithoutExpiry = PersonalAccessToken::whereNull('expires_at')->count();

        $this->info("=== ESTADO DE TOKENS ===");
        $this->line("Total de tokens: {$totalTokens}");
        $this->line("Tokens activos: {$activeTokens}");
        $this->line("Tokens expirados: {$expiredTokens}");
        $this->line("Tokens sin expiración: {$tokensWithoutExpiry}");
        
        if ($expiredTokens > 0) {
            $this->warn("⚠️  Hay {$expiredTokens} tokens expirados que deberían limpiarse");
        } else {
            $this->info("✅ No hay tokens expirados");
        }

        return Command::SUCCESS;
    }
}
