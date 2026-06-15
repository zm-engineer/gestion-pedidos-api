<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'], //La regla exists:products,id garantiza que el producto exista en la BD; si no, responde 422 automáticamente.
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Validación de stock previa a la transacción del controlador.
     *
     * Se ejecuta tras las reglas básicas; si algún producto no tiene stock
     * suficiente, devuelve 422 antes de que el controlador abra la transacción.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('items', []) as $index => $item) {
                $product = Product::find($item['product_id'] ?? null);

                if ($product && $product->stock < ($item['quantity'] ?? 0)) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Stock insuficiente para «{$product->name}»: disponible {$product->stock}."
                    );
                }
            }
        });
    }
}
