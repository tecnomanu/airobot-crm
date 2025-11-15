<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateWebhookToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:generate-token 
                            {--show : Mostrar el token en pantalla}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un token seguro para validación de webhooks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = bin2hex(random_bytes(32));
        
        $this->info('🔐 Token de Webhook Generado');
        $this->newLine();
        
        if ($this->option('show')) {
            $this->line("Token: <fg=yellow>{$token}</>");
            $this->newLine();
        }
        
        $this->line('Agrega esto a tu archivo .env:');
        $this->newLine();
        $this->line("<fg=green>WEBHOOK_TOKEN={$token}</>");
        $this->newLine();
        
        $this->info('💡 Configuración adicional (opcional):');
        $this->line('<fg=gray>WEBHOOK_VALIDATION_ENABLED=true</>');
        $this->line('<fg=gray>WEBHOOK_VALIDATION_METHOD=token  # token o hmac</>');
        $this->newLine();
        
        $this->warn('⚠️  Guarda este token en un lugar seguro!');
        $this->warn('   Necesitarás enviarlo en el header X-Webhook-Token de cada request.');
        
        return Command::SUCCESS;
    }
}

