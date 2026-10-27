<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Permission extends Model
{
    protected string $table = 'permissions';
    protected bool $useSoftDelete = false;

    public function getGroupedByModule(): array
    {
        $all = $this->findAll([], 'module ASC, libelle ASC');
        $grouped = [];
        foreach ($all as $p) {
            $grouped[$p['module']][] = $p;
        }
        return $grouped;
    }
}
