<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (Schema::hasColumn('users', 'username') && User::where('username', 'client')->exists()) {
            return;
        }

        $dumpPath = base_path('eventintel (1).sql');

        if (! is_file($dumpPath)) {
            throw new \RuntimeException('Legacy database dump not found.');
        }

        $sql = file_get_contents($dumpPath);
        $sql = preg_replace('/^\s*(DROP|CREATE) DATABASE.*?;\s*$/mi', '', $sql);
        $sql = preg_replace('/^\s*USE\s+`?eventintel`?\s*;\s*$/mi', '', $sql);

        preg_match_all('/CREATE TABLE `([^`]+)`/i', $sql, $matches);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_reverse($matches[1]) as $table) {
            Schema::dropIfExists($table);
        }

        foreach ($this->statements($sql) as $statement) {
            $statement = trim($statement);

            if ($statement !== '') {
                DB::unprepared($statement);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function statements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $escaped = false;

        for ($index = 0, $length = strlen($sql); $index < $length; $index++) {
            $character = $sql[$index];

            if ($quote !== null) {
                $buffer .= $character;

                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
                $buffer .= $character;
            } elseif ($character === ';') {
                $statements[] = $buffer;
                $buffer = '';
            } else {
                $buffer .= $character;
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}
