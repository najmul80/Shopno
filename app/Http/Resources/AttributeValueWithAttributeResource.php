<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueWithAttributeResource extends JsonResource // Resource for AttributeValue model
{
    public function toArray(Request $request): array
    {
        return [
            'attribute_id' => $this->attribute->id, // From eager loaded attribute
            'attribute_name' => $this->attribute->display_name ?? $this->attribute->name,
            'value_id' => $this->id,
            'value' => $this->display_value ?? $this->value,
        ];
    }
}