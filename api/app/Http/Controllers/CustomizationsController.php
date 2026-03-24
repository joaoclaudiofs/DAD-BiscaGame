<?php

namespace App\Http\Controllers;

use App\Models\Customization;
use App\Models\UserCustomization;
use Illuminate\Http\Request;

class CustomizationsController extends Controller
{
    //não foi implementado 
    public function index(Request $request, string $type)
    {
        $query = Customization::query()->where('type', $type);
        return response()->json($query->get());
    }

    public function hasCustomization(Request $request, Customization $customization)
    {
        $userId = $request->user()->id;
        $exists = (UserCustomization::where('user_id', $userId)
            ->where('customization_id', $customization->id)
            ->exists()) ||
            $customization->price == 0;

        return response()->json([
            'owned' => $exists,
        ]);
    }
}
