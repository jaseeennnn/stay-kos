<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ReleaseStaleLocks extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'kos';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'kos:release-locks';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Lepaskan soft lock kamar yang sudah expired (>5 menit)';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'kos:release-locks';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $db  = \Config\Database::connect();
        $exp = date('Y-m-d H:i:s', time() - 300);

        $db->table('rooms')
            ->where('locked_at <', $exp)
            ->update([
                'locked_by'  => null,
                'locked_at'  => null,
                'lock_token' => null,
            ]);

        CLI::write('Released ' . $db->affectedRows() . ' stale lock(s).', 'green');

        \App\Models\InstallmentModel::markOverdue();
        CLI::write('Overdue installments updated.', 'green');
    }
}
