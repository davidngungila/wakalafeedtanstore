<?php

namespace App\Console\Commands;

use App\Services\TransactionJournalService;
use Illuminate\Console\Command;

class BackfillTransactionJournals extends Command
{
    protected $signature = 'finance:backfill {--limit= : Limit number of transactions to process}';

    protected $description = 'Backfill journal entries for existing transactions so Finance (GL/IS/BS) reflects all history';

    public function handle(TransactionJournalService $journals): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $this->info('Backfilling journals for existing transactions…');

        $result = $journals->backfillMissing($limit);

        $this->info(sprintf(
            'Done: %d posted, %d reversed, %d skipped.',
            $result['posted'],
            $result['reversed'],
            $result['skipped']
        ));

        return self::SUCCESS;
    }
}
