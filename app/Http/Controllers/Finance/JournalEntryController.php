<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class JournalEntryController extends Controller
{
    public function __construct(
        private readonly JournalService $journalService,
    ) {}

    /**
     * Jurnal umum: daftar entry + lines, search nomor/deskripsi, filter
     * rentang tanggal & sumber jurnal.
     */
    public function index(Request $request): Response
    {
        $q = $request->query('q');
        $from = $request->query('from');
        $to = $request->query('to');
        $source = $request->query('source');

        $entries = JournalEntry::query()
            ->with(['lines.account:id,code,name', 'poster:id,name'])
            ->when($q, fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('entry_number', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            }))
            ->when($from, fn ($query) => $query->where('entry_date', '>=', $from))
            ->when($to, fn ($query) => $query->where('entry_date', '<=', $to))
            ->when(in_array($source, JournalEntry::SOURCES, true), fn ($query) => $query->where('source', $source))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('finance/journal-entries/index', [
            'entries' => $entries,
            'sources' => JournalEntry::SOURCES,
            'filters' => $request->only(['q', 'from', 'to', 'source']),
        ]);
    }

    /**
     * Form jurnal manual (koreksi/penyesuaian) — hanya akun postable aktif.
     */
    public function create(): Response
    {
        $accounts = ChartOfAccount::query()
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('finance/journal-entries/create', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Simpan jurnal manual lewat JournalService (validasi balance/postable
     * tetap di satu pintu); user tercatat sebagai posted_by.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $entry = $this->journalService->post(
                source: JournalEntry::SOURCE_MANUAL,
                entryDate: $validated['entry_date'],
                description: $validated['description'],
                lines: $validated['lines'],
                postedBy: $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('journal-entries.create')
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('journal-entries.show', $entry)
            ->with('success', "Jurnal manual {$entry->entry_number} berhasil disimpan.");
    }

    /**
     * Detail satu entry beserta lines-nya.
     */
    public function show(JournalEntry $journalEntry): Response
    {
        $journalEntry->load(['lines.account:id,code,name', 'poster:id,name']);

        return Inertia::render('finance/journal-entries/show', [
            'entry' => $journalEntry,
        ]);
    }
}
