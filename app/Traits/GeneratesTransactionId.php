<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait GeneratesTransactionId
{
    /**
     * Generate a unique 16-character uppercase alphanumeric transaction ID.
     *
     * @param string $table The database table to check.
     * @param string $column The column to check for uniqueness.
     * @return string
     */
    public function generateUniqueTransactionId(string $table, string $column = 'transaction_id'): string
    {
        do {
            $id = $this->generateTransactionId();
        } while (DB::table($table)->where($column, $id)->exists());

        return $id;
    }

    /**
     * Generate a 16-character uppercase alphanumeric transaction ID.
     *
     * @return string
     */
    protected function generateTransactionId(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $id = '';
        for ($i = 0; $i < 16; $i++) {
            $id .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $id;
    }
}
