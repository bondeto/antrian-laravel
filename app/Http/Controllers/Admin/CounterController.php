<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\Floor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CounterController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Counters/Index', [
            'counters' => Counter::with('floor')->get(),
            'floors' => Floor::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255',
        ]);

        Counter::create($request->all());
        return redirect()->back()->with('success', 'Loket berhasil ditambahkan.');
    }

    public function update(Request $request, Counter $counter)
    {
        $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255',
        ]);

        $counter->update($request->all());
        return redirect()->back()->with('success', 'Loket berhasil diperbarui.');
    }

    public function destroy(Counter $counter)
    {
        $counter->delete();
        return redirect()->back()->with('success', 'Loket berhasil dihapus.');
    }
}
