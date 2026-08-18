<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->query('q');

        $units = Unit::query()
            ->when($q, fn ($query) => $query
                ->where('name', 'like', "%{$q}%")
                ->orWhere('abbreviation', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('master/units/index', [
            'units' => $units,
            'filters' => $request->only(['q']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('master/units/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10', 'unique:units,abbreviation'],
            'allows_fraction' => ['boolean'],
        ]);

        Unit::create($validated);

        return redirect()->route('units.index')->with('success', 'Satuan berhasil disimpan.');
    }

    public function edit(Unit $unit): Response
    {
        return Inertia::render('master/units/edit', [
            'unit' => $unit,
        ]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10', 'unique:units,abbreviation,'.$unit->id],
            'allows_fraction' => ['boolean'],
        ]);

        $unit->update($validated);

        return redirect()->route('units.index')->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->exists()) {
            return redirect()->route('units.index')->with('error', 'Satuan masih dipakai produk, tidak bisa dihapus.');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Satuan berhasil dihapus.');
    }
}
