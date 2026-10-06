<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MejaController extends Controller
{
    public function index()
    {
        return Meja::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nomor_meja' => 'required|string|max:50|unique:meja,nomor_meja',
        ]);

        $data['status_meja'] = 'kosong';

        // qr_code digenerate server dari id meja, bukan input user.
        // ponytail: isi URL publik /menu?meja={id} bila domain final sudah fix.
        $meja = Meja::create($data);
        $meja->update(['qr_code' => 'table=' . $meja->id]);

        return $meja->fresh();
    }

    public function show(Meja $meja)
    {
        return $meja;
    }

    public function update(Request $request, Meja $meja)
    {
        $data = $request->validate([
            'nomor_meja' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('meja', 'nomor_meja')->ignore($meja->id),
            ],
            'qr_code' => 'sometimes|nullable|string|max:255',
            'status_meja' => 'sometimes|in:kosong,terisi',
        ]);

        $meja->update($data);
        return $meja;
    }

    public function destroy(Meja $meja)
    {
        $meja->delete();
        return response()->json(['message' => 'Meja dihapus']);
    }
}