<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $filters = $this->validatedFilters($request);
        [$from, $to, $label] = $reports->resolvePeriod($filters['period'] ?? 'this_month', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        return view('reports.index', ['report' => $reports->data($from, $to), 'periodLabel' => $label, 'from' => $from, 'to' => $to]);
    }

    public function export(Request $request, ReportService $reports): StreamedResponse
    {
        $filters = $this->validatedFilters($request, true);
        [$from, $to] = $reports->resolvePeriod($filters['period'] ?? 'this_month', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $type = $filters['type'];
        $query = $reports->exportQuery($type, $from, $to);
        $headers = [
            'sales' => ['N° vente', 'Date', 'Client', 'Total', 'Payé', 'Crédit', 'Statut paiement', 'Marge estimée'],
            'purchases' => ['N° achat', 'Date', 'Fournisseur', 'Total', 'Payé', 'À payer', 'Statut paiement'],
            'stock' => ['SKU', 'Produit', 'Catégorie', 'Stock', 'Stock minimum', 'Prix achat', 'Prix vente', 'Valeur stock', 'Statut'],
            'expenses' => ['Date', 'Catégorie', 'Description', 'Montant', 'Moyen de paiement', 'Utilisateur'],
            'movements' => ['Date', 'SKU', 'Produit', 'Type', 'Quantité', 'Stock avant', 'Stock après', 'Motif', 'Utilisateur'],
        ][$type];

        return response()->streamDownload(function () use ($query, $headers) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers, ';', '"', '\\');
            foreach ($query->cursor() as $record) {
                $row = array_values($record->getAttributes());
                fputcsv($stream, array_map(fn ($value) => $this->safeCell($value), $row), ';', '"', '\\');
            }
            fclose($stream);
        }, 'rapport-'.$type.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedFilters(Request $request, bool $withType = false): array
    {
        $rules = [
            'period' => ['nullable', 'in:'.implode(',', ReportService::PERIODS)],
            'date_from' => ['required_if:period,custom', 'nullable', 'date'],
            'date_to' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:date_from'],
        ];
        if ($withType) $rules['type'] = ['required', 'in:'.implode(',', ReportService::EXPORTS)];
        return $request->validate($rules, [
            'period.in' => 'Sélectionnez une période de rapport valide.',
            'date_from.required_if' => 'La date de début est obligatoire pour une période personnalisée.',
            'date_to.required_if' => 'La date de fin est obligatoire pour une période personnalisée.',
            'date_to.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
            'type.in' => 'Sélectionnez un type de rapport valide.',
        ]);
    }

    private function safeCell(mixed $value): string
    {
        $text = (string) $value;
        if (preg_match('/^[\s\x00-\x1F]*[=+@]/u', $text)) return "'".$text;
        if (preg_match('/^[\s\x00-\x1F]*-/u', $text) && ! preg_match('/^-?\d+(?:\.\d+)?$/', $text)) return "'".$text;
        return $text;
    }
}
