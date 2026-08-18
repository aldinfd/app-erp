<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    /**
     * Nilai `type` yang sah — sama dengan daftar di schema-database.md §4.6.
     *
     * @var list<string>
     */
    private const ACCOUNT_TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    public function index(Request $request): Response
    {
        $q = $request->query('q');
        $type = $request->query('type');

        $accounts = ChartOfAccount::query()
            ->with('parent:id,code,name')
            ->when($q, fn ($query) => $query
                ->where(fn ($where) => $where
                    ->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")))
            ->when(in_array($type, self::ACCOUNT_TYPES), fn ($query) => $query->where('type', $type))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('master/chart-of-accounts/index', [
            'accounts' => $accounts,
            'types' => self::ACCOUNT_TYPES,
            'filters' => $request->only(['q', 'type']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('master/chart-of-accounts/create', [
            'accounts' => ChartOfAccount::query()->orderBy('code')->get(['id', 'code', 'name']),
            'types' => self::ACCOUNT_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:chart_of_accounts,code'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:'.implode(',', self::ACCOUNT_TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
            'is_postable' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        ChartOfAccount::create($validated);

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun berhasil disimpan.');
    }

    public function edit(ChartOfAccount $chartOfAccount): Response
    {
        return Inertia::render('master/chart-of-accounts/edit', [
            'account' => $chartOfAccount,
            'accounts' => ChartOfAccount::query()->orderBy('code')->get(['id', 'code', 'name']),
            'types' => self::ACCOUNT_TYPES,
        ]);
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:chart_of_accounts,code,'.$chartOfAccount->id],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:'.implode(',', self::ACCOUNT_TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id', 'not_in:'.$chartOfAccount->id],
            'is_postable' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $chartOfAccount->update($validated);

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        if ($chartOfAccount->children()->exists()) {
            return redirect()->route('chart-of-accounts.index')->with('error', 'Akun masih memiliki sub-akun, tidak bisa dihapus.');
        }

        $chartOfAccount->delete();

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun berhasil dihapus.');
    }
}
