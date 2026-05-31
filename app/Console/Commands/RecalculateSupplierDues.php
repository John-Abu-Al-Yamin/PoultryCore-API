<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use Illuminate\Console\Command;

class RecalculateSupplierDues extends Command
{
    protected $signature = 'suppliers:recalculate-dues';

    protected $description = 'Recalculate total_dues for all suppliers based on their purchases';

    public function handle(): void
    {
        $count = 0;

        Supplier::chunk(100, function ($suppliers) use (&$count) {
            foreach ($suppliers as $supplier) {
                $supplier->recalculateTotalDues();
                $count++;
            }
        });

        $this->info("Recalculated total_dues for {$count} suppliers.");
    }
}
