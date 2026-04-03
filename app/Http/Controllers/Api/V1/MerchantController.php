<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;

class MerchantController extends Controller
{
    /**
     * Return a JSON list of active merchants (for dropdowns).
     */
    public function index(): JsonResponse
    {
        $merchants = Merchant::where('is_active', true)
            ->select('id', 'name', 'type')
            ->orderBy('name')
            ->get();

        return response()->json($merchants);
    }
}
