<?php

namespace App\progest\repositories;

use App\Empenho;
use App\Material;
use App\SubMaterial;
use App\Entrada;
use App\Saldo;

class EntradaRepository {

    protected $materialRepository;
    protected $relatorioRepository;

    public function __construct(MaterialRepository $materialRepository, RelatorioRepository $relatorioRepository) {
        $this->materialRepository = $materialRepository;
        $this->relatorioRepository = $relatorioRepository;
    }

    public function index($empenho = null) {
        if ($empenho == null) {
            return Entrada::orderBy('created_at', 'desc')->paginate(50);
        } else {
            return Entrada::where('empenho_id', $empenho)->orderBy('created_at', 'desc')->paginate(50);
        }
    }

    public function store($input) {
        $entrada = new Entrada($input['entrada']);

        $empenho = Empenho::find($input['empenho']);
        $entrada->empenho()->associate($empenho);

        $entrada->save();
        $subMateriais = [];
        foreach ($input['subMateriais']['qtds'] as $key => $val) {
            $subMateriais[$key] = ['quant' => $val];
        }

        $entrada->subMateriais()->sync($subMateriais);

        foreach ($subMateriais as $key => $val) {
            $subMaterial = SubMaterial::find($key);
            $subMaterial->qtd_estoque += $val['quant'];
            $subMaterial->save();
            $valor = (round($subMaterial->vl_total/$subMaterial->qtd_solicitada, 2)*$val['quant']);
            $this->relatorioRepository->updateSaldo($subMaterial, $valor);
        }

        return $entrada;
    }

    public function update($id, $input) {
    }

    public function show($id) {
        return Entrada::findOrFail($id);
    }

    public function destroy($id) {
        $entrada = Entrada::find($id);

        foreach ($entrada->subMateriais as $subMaterial) {
            $valor = "-".(round($subMaterial->vl_total/$subMaterial->qtd_solicitada, 2)*$subMaterial->pivot->quant);
            $this->relatorioRepository->updateSaldo($subMaterial, $valor);
            $subMaterial->qtd_estoque -= $subMaterial->pivot->quant;
            $subMaterial->save();
        }
        return $entrada->delete();
    }
}
