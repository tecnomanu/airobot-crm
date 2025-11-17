<?php

namespace App\Console\Commands;

use App\Jobs\Lead\CheckPendingIntentsJob;
use Illuminate\Console\Command;

class ProcessPendingIntentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:check-pending-intents {--timeout=24 : Timeout en horas para considerar no respuesta}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar leads con intenciones pendientes y marcar como "no responde" si exceden el timeout';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');

        $this->info("Procesando intenciones pendientes (timeout: {$timeout}h)...");

        CheckPendingIntentsJob::dispatch($timeout);

        $this->info('Job despachado exitosamente.');

        return Command::SUCCESS;
    }
}
