<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function index()
    {
        return Menu::with('kategori')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_kategori' => 'required|exists:kategori,id',
            'nama_menu' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'required|numeric|min:0',
            'status_tersedia' => 'nullable|boolean',
            'gambar' => 'nullable|image|max:2048', // max 2MB
        ]);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('menu', 'public');
        }

        $data['status_tersedia'] = $data['status_tersedia'] ?? true;

        return Menu::create($data)->load('kategori');
    }

    public function show(Menu $menu)
    {
        return $menu->load('kategori');
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'id_kategori' => 'sometimes|exists:kategori,id',
            'nama_menu' => 'sometimes|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'sometimes|numeric|min:0',
            'status_tersedia' => 'sometimes|boolean',
            'gambar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('gambar')) {
            // Hapus foto lama biar tidak menumpuk file yang tidak terpakai
            if ($menu->gambar) {
                Storage::disk('public')->delete($menu->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('menu', 'public');
        }

        $menu->update($data);
        return $menu->load('kategori');
    }

    public function destroy(Menu $menu)
    {
        if ($menu->gambar) {
            Storage::disk('public')->delete($menu->gambar);
        }
        $menu->delete();
        return response()->json(['message' => 'Menu dihapus']);
    }
}