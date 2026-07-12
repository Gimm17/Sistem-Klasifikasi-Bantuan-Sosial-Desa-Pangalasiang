<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AtributKlasifikasiResource;
use App\Models\AtributKlasifikasi;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * List atribut klasifikasi (read-only). 4 atribut baku terkunci sesuai proposal;
 * kategori/bin diatur terpisah lewat KategoriAtributController.
 *  - admin & approver: lihat.
 */
class AtributKlasifikasiController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AtributKlasifikasiResource::collection(
            AtributKlasifikasi::with('kategori')->orderBy('id')->get()
        );
    }

    public function show(AtributKlasifikasi $atributKlasifikasi): AtributKlasifikasiResource
    {
        return new AtributKlasifikasiResource($atributKlasifikasi->load('kategori'));
    }
}
