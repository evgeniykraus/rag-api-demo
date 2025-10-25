<?php

namespace App\Jobs;

use App\Models\Proposal;
use App\Services\ProposalService;
use Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class AnalyzeProposalJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Proposal $proposal
    ) {}

    /**
     * Execute the job.
     * @param ProposalService $proposalService
     * @return void
     * @throws Throwable
     */
    public function handle(ProposalService $proposalService): void
    {
        $lockKey = "proposal:analyzing:{$this->proposal->id}";

        // Пытаемся установить блокировку на 10 минут
        $lock = Cache::lock($lockKey, 600);

        if ($lock->get()) {
            try {
                // Выполняем анализ
                $proposalService->analyzeProposal($this->proposal);
            } finally {
                // Освобождаем блокировку
                $lock->release();
            }
        } else {
            // Уже выполняется другой процесс анализа
            info("Proposal {$this->proposal->id} is already being analyzed, skipping.");
        }
    }

    /**
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $lockKey = "proposal:analyzing:{$this->proposal->id}";
        Cache::lock($lockKey)->release();

        report($exception);
    }
}
