<?php

namespace PROLANCEE\Support\Console\Init;

use Illuminate\Console\Command;
use PROLANCEE\Support\Classes\Database\DBEncrypter;
use Throwable;

class DBEncrypt extends Command
{
    protected $signature = 'prolancee:db:encryption 
                           {--force : Force re-encryption even if already encrypted}';

    protected $description = 'Sync database and Redis encryption settings';

    public function handle(): int
    {
        $this->line('Starting Database & Redis initialization process...');

        $force = $this->option('force');
        $this->line('Force re-encryption: ' . ($force ? 'YES' : 'NO'));

        try {
            DBEncrypter::setTableColumnEncryption($force);
            DBEncrypter::setDatabaseAndRedisEncryption($force);

            $this->info('Database & Redis encryption synced successfully.');
            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Encryption process failed.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
