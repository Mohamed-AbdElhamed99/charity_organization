<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\BeneficiarySupport;
use App\Models\TurkeyRequest;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MergeDuplicateTurkeyRequestsCommand extends Command
{
    protected $signature = 'turkey:merge-duplicates
        {--dry-run : Show what would be merged without changing anything}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Merge pending Free Halal Turkey requests from the same person (same phone or email) into one request with a quantity';

    public function handle(): int
    {
        $groups = $this->duplicateGroups();

        if ($groups === []) {
            $this->info('No duplicate pending requests found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Keep id', 'Name', 'Phone', 'Requests merged', 'New quantity', 'Code to use', 'Codes removed'],
            array_map(fn (Collection $group): array => $this->describe($group), $groups),
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Merge these requests? Removed codes become invalid.')) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        foreach ($groups as $group) {
            DB::transaction(fn () => $this->merge($group));
        }

        $this->info(count($groups).' duplicate group(s) merged.');

        return self::SUCCESS;
    }

    /**
     * Pending requests linked directly or transitively by the same phone (digits) or email.
     *
     * @return list<Collection<int, TurkeyRequest>>
     */
    private function duplicateGroups(): array
    {
        $requests = TurkeyRequest::query()->pending()->orderBy('id')->get();

        $parent = [];
        $find = function (int $id) use (&$parent, &$find): int {
            if ($parent[$id] !== $id) {
                $parent[$id] = $find($parent[$id]);
            }

            return $parent[$id];
        };

        $owners = [];

        foreach ($requests as $request) {
            $parent[$request->id] = $request->id;

            foreach ($this->identityKeys($request) as $key) {
                if (isset($owners[$key])) {
                    $parent[$find($request->id)] = $find($owners[$key]);
                } else {
                    $owners[$key] = $request->id;
                }
            }
        }

        $grouped = $requests->groupBy(fn (TurkeyRequest $request) => $find($request->id));

        return $grouped
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function identityKeys(TurkeyRequest $request): array
    {
        $keys = [];
        $phone = preg_replace('/\D+/', '', $request->phone) ?? '';
        $email = strtolower(trim($request->email));

        if ($phone !== '') {
            $keys[] = 'phone:'.$phone;
        }

        if ($email !== '') {
            $keys[] = 'email:'.$email;
        }

        return $keys;
    }

    /**
     * @param  Collection<int, TurkeyRequest>  $group
     * @return array<int, string|int>
     */
    private function describe(Collection $group): array
    {
        $kept = $group->first();

        return [
            $kept->id,
            $kept->full_name,
            $kept->phone,
            $group->count(),
            $group->sum('quantity'),
            $kept->code,
            $group->slice(1)->pluck('code')->implode(', '),
        ];
    }

    /**
     * @param  Collection<int, TurkeyRequest>  $group
     */
    private function merge(Collection $group): void
    {
        $kept = $group->first();
        $duplicates = $group->slice(1);

        $kept->update(['quantity' => $group->sum('quantity')]);

        TurkeyRequest::query()->whereIn('id', $duplicates->pluck('id'))->delete();

        $duplicates
            ->pluck('beneficiary_id')
            ->unique()
            ->reject(fn (int $beneficiaryId) => $beneficiaryId === $kept->beneficiary_id)
            ->each(function (int $beneficiaryId): void {
                $isUnused = ! TurkeyRequest::query()->where('beneficiary_id', $beneficiaryId)->exists()
                    && ! BeneficiarySupport::query()->where('beneficiary_id', $beneficiaryId)->exists();

                if ($isUnused) {
                    Beneficiary::query()->whereKey($beneficiaryId)->delete();
                }
            });
    }
}
