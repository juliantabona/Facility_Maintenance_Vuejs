<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Document as DocumentResource;
use App\Http\Resources\Documents as DocumentsResource;

class Quotation extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [

            'id' => $this->id,
            'number' => $this->number,
            'currency_type' => $this->currency_type,
            'created_date' => $this->created_date,
            'expiry_date' => $this->expiry_date,
            'items' => $this->items,
            'taxes' => $this->taxes,
            'discounts' => $this->discounts,
            'coupons' => $this->coupons,
            'sub_total' => $this->sub_total,
            'item_tax_total' => $this->item_tax_total,
            'global_tax_total' => $this->global_tax_total,
            'grand_tax_total' => $this->grand_tax_total,
            'item_discount_total' => $this->item_discount_total,
            'global_discount_total' => $this->global_discount_total,
            'grand_discount_total' => $this->grand_discount_total,
            'shipping_total' => $this->shipping_total,
            'grand_total' => $this->grand_total,
            'reference_id' => $this->reference_id,
            'reference_ip_address' => $this->reference_ip_address,
            'reference_user_agent' => $this->reference_user_agent,
            'customer_id' => $this->customer_id,
            'customer_type' => $this->customer_type,
            'billing_info' => $this->billing_info,
            'shipping_info' => $this->shipping_info,
            'merchant_id' => $this->merchant_id,
            'merchant_type' => $this->merchant_type,
            'merchant_info' => $this->merchant_info,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            /*  Resource Links */
            '_links' => [
                
                'curies' => [
                    [ 'name' => 'oq', 'href' => 'https://oqcloud.co.bw/docs/rels/{rel}', 'templated' => true ]
                ],

                //  Link to current resource
                'self' => [ 
                    'href' => url()->full(),
                    'title' => 'This quotation'
                ]
            ],

            /*  Embedded Resources */
            '_embedded' => [
                    
              
            ]
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withResponse($request, $response)
    {
        $response->header('Content-Type', 'application/hal+json');
    }

}